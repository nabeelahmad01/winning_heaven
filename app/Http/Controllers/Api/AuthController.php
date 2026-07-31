<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function checkEmail(Request $request)
    {
        $email = strtolower(trim((string) $request->query('email', '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['ok' => false, 'exists' => false, 'error' => 'Invalid email'], 422);
        }
        $user = User::query()->where('email', $email)->first();
        return response()->json([
            'ok' => true,
            'exists' => (bool) $user,
            'name' => $user?->name,
        ]);
    }

    public function sendOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'purpose' => 'required|in:register,reset',
            'name' => 'nullable|string|max:120',
        ]);
        $email = strtolower($data['email']);
        $purpose = $data['purpose'];
        $exists = User::query()->where('email', $email)->exists();

        if ($purpose === 'register' && $exists) {
            return response()->json(['ok' => false, 'error' => 'Email already registered. Please login.'], 422);
        }
        if ($purpose === 'reset' && !$exists) {
            return response()->json(['ok' => false, 'error' => 'No account found with this email.'], 422);
        }

        $otp = (string) random_int(100000, 999999);
        $cacheKey = $this->otpKey($purpose, $email);
        Cache::put($cacheKey, [
            'otp' => $otp,
            'name' => $data['name'] ?? null,
            'attempts' => 0,
        ], now()->addMinutes(10));

        $heading = $purpose === 'register' ? 'Verify your email' : 'Password recovery';
        $purposeLabel = $purpose === 'register' ? 'complete signup' : 'reset your password';
        $simulated = false;

        try {
            $mailer = (string) config('mail.default');
            $hasCreds = filled(config('mail.mailers.smtp.username')) && filled(config('mail.mailers.smtp.password'));
            if ($mailer === 'log' || !$hasCreds) {
                $simulated = true;
                \Log::info("[OTP SIMULATOR] {$purpose} code {$otp} → {$email}");
            } else {
                Mail::send('emails.otp', [
                    'otp' => $otp,
                    'name' => $data['name'] ?? null,
                    'heading' => $heading,
                    'purposeLabel' => $purposeLabel,
                ], function ($message) use ($email, $heading) {
                    $message->to($email)->subject("Winning Heaven — {$heading}");
                });
            }
        } catch (\Throwable $e) {
            \Log::warning('OTP mail failed: '.$e->getMessage());
            $simulated = true;
            \Log::info("[OTP FALLBACK] {$purpose} code {$otp} → {$email}");
        }

        $payload = [
            'ok' => true,
            'message' => $simulated
                ? 'SMTP not ready — code logged on server. Check storage/logs or ask HQ.'
                : 'Verification code sent to your email.',
            'simulated' => $simulated,
        ];
        if ($simulated && app()->environment(['local', 'testing'])) {
            $payload['debug_otp'] = $otp;
        }
        return response()->json($payload);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'purpose' => 'required|in:register,reset',
            'otp' => 'required|string|size:6',
        ]);
        $email = strtolower($data['email']);
        $purpose = $data['purpose'];
        $cacheKey = $this->otpKey($purpose, $email);
        $stored = Cache::get($cacheKey);

        if (!$stored || empty($stored['otp'])) {
            return response()->json(['ok' => false, 'error' => 'Code expired. Request a new one.'], 422);
        }
        $attempts = (int) ($stored['attempts'] ?? 0);
        if ($attempts >= 8) {
            Cache::forget($cacheKey);
            return response()->json(['ok' => false, 'error' => 'Too many attempts. Request a new code.'], 422);
        }
        if (!hash_equals((string) $stored['otp'], (string) $data['otp'])) {
            $stored['attempts'] = $attempts + 1;
            Cache::put($cacheKey, $stored, now()->addMinutes(10));
            return response()->json(['ok' => false, 'error' => 'Invalid code.'], 422);
        }

        $token = Str::random(40);
        Cache::put($this->verifiedKey($purpose, $email), $token, now()->addMinutes(15));
        Cache::forget($cacheKey);

        return response()->json(['ok' => true, 'verified_token' => $token]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'referral_code' => 'nullable|string',
            'distributor_id' => 'nullable|string',
            'agent_code' => 'nullable|string',
            'campaign' => 'nullable|string',
            'verified_token' => 'required|string',
        ]);

        $email = strtolower($data['email']);
        if (!$this->consumeVerifiedToken('register', $email, $data['verified_token'])) {
            return response()->json(['ok' => false, 'error' => 'Email not verified. Complete OTP first.'], 422);
        }

        $referredBy = null;
        if (!empty($data['referral_code'])) {
            $ref = User::query()->where('referral_code', strtoupper($data['referral_code']))->first();
            $referredBy = $ref?->email;
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $email,
            'password' => $data['password'],
            'role' => 'user',
            'coins' => 100,
            'referral_code' => strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
            'referred_by' => $referredBy,
            'distributor_id' => $data['distributor_id'] ?? null,
            'agent_code' => $data['agent_code'] ?? null,
            'campaign' => $data['campaign'] ?? 'organic',
            'email_verified_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['ok' => true, 'user' => $this->publicUser($user)]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'verified_token' => 'required|string',
        ]);
        $email = strtolower($data['email']);
        if (!$this->consumeVerifiedToken('reset', $email, $data['verified_token'])) {
            return response()->json(['ok' => false, 'error' => 'OTP not verified or expired.'], 422);
        }
        $user = User::query()->where('email', $email)->first();
        if (!$user) {
            return response()->json(['ok' => false, 'error' => 'Account not found.'], 404);
        }
        $user->password = $data['password'];
        $user->save();

        return response()->json(['ok' => true, 'message' => 'Password updated. You can login now.']);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $email = strtolower($data['email']);
        $user = User::query()->where('email', $email)->first();

        $adminEmail = strtolower((string) config('services.admin.email', 'admin@winningheaven.com'));
        $adminPass = (string) config('services.admin.password', 'admin123');
        if ($email === $adminEmail && $data['password'] === $adminPass) {
            if (!$user) {
                $user = User::create([
                    'name' => 'System Admin',
                    'email' => $adminEmail,
                    'password' => $adminPass,
                    'role' => 'admin',
                    'roles' => ['admin'],
                    'coins' => 0,
                    'referral_code' => 'ADMIN001',
                    'email_verified_at' => now(),
                ]);
            }
            Auth::login($user);
            $request->session()->regenerate();
            return response()->json(['ok' => true, 'user' => $this->publicUser($user)]);
        }

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }

        if ($user->status === 'SUSPENDED' || $user->session_revoked) {
            return response()->json(['ok' => false, 'error' => 'Account suspended'], 403);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['ok' => true, 'user' => $this->publicUser($user)]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['ok' => true]);
    }

    public function me(Request $request)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $user = $request->user();
        if (!$user) {
            return response()->json(['ok' => false], 401);
        }
        return response()->json(['ok' => true, 'user' => $this->publicUser($user)]);
    }

    public function sessionStatus(Request $request)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $user = $request->user();
        if (!$user) {
            return response()->json(['ok' => false, 'revoked' => true], 401);
        }
        return response()->json([
            'ok' => true,
            'revoked' => (bool) $user->session_revoked || $user->status === 'SUSPENDED',
        ]);
    }

    private function otpKey(string $purpose, string $email): string
    {
        return 'wh_otp:'.$purpose.':'.$email;
    }

    private function verifiedKey(string $purpose, string $email): string
    {
        return 'wh_otp_ok:'.$purpose.':'.$email;
    }

    private function consumeVerifiedToken(string $purpose, string $email, string $token): bool
    {
        $key = $this->verifiedKey($purpose, $email);
        $stored = Cache::get($key);
        if (!$stored || !hash_equals((string) $stored, (string) $token)) {
            return false;
        }
        Cache::forget($key);
        return true;
    }

    private function publicUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'roles' => $user->roles,
            'coins' => $user->coins,
            'referralCode' => $user->referral_code,
            'isSubscribed' => $user->is_subscribed,
            'distributorId' => $user->distributor_id,
            'agentCode' => $user->agent_code,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OauthTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    /**
     * Exchange Google ID token (GIS credential) for a session.
     * Client: google.accounts.id.initialize({ client_id, callback }) → POST credential here.
     */
    public function login(Request $request)
    {
        $clientId = (string) config('services.google.client_id', '');
        if ($clientId === '') {
            return response()->json([
                'ok' => false,
                'error' => 'Google sign-in is not configured. Set GOOGLE_CLIENT_ID in .env.',
            ], 503);
        }

        $data = $request->validate([
            'credential' => 'nullable|string',
            'email' => 'nullable|email',
            'name' => 'nullable|string',
            'referral_code' => 'nullable|string',
            'distributor_id' => 'nullable|string',
            'agent_code' => 'nullable|string',
            'campaign' => 'nullable|string',
        ]);

        $email = null;
        $name = null;

        if (!empty($data['credential'])) {
            $payload = $this->verifyIdToken($data['credential'], $clientId);
            if (!$payload) {
                return response()->json(['ok' => false, 'error' => 'Invalid Google credential'], 401);
            }
            $email = strtolower((string) ($payload['email'] ?? ''));
            $name = (string) ($payload['name'] ?? $payload['given_name'] ?? 'Player');
        } else {
            // Dev / ticket fallback when credential already verified client-side
            $email = strtolower((string) ($data['email'] ?? ''));
            $name = (string) ($data['name'] ?? 'Player');
        }

        if ($email === '') {
            return response()->json(['ok' => false, 'error' => 'Google account details missing'], 400);
        }

        $user = User::query()->where('email', $email)->first();
        $isNew = false;

        if ($user && strtoupper((string) ($user->status ?? 'active')) === 'SUSPENDED') {
            return response()->json(['ok' => false, 'error' => 'Your account has been suspended.'], 403);
        }

        if (!$user) {
            $referredBy = null;
            $distributorId = $data['distributor_id'] ?? null;
            $agentCode = $data['agent_code'] ?? null;
            if (!empty($data['referral_code'])) {
                $ref = User::query()->where('referral_code', strtoupper($data['referral_code']))->first();
                if ($ref) {
                    $referredBy = $ref->email;
                    $distributorId = $distributorId ?: $ref->distributor_id;
                    $agentCode = $agentCode ?: $ref->agent_code;
                }
            }
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'role' => 'user',
                'coins' => 100,
                'referral_code' => strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
                'referred_by' => $referredBy,
                'distributor_id' => $distributorId,
                'agent_code' => $agentCode,
                'campaign' => $data['campaign'] ?? 'organic',
            ]);
            $isNew = true;
        }

        Auth::login($user);
        $request->session()->regenerate();

        // Optional one-shot ticket for deep-link clients
        $ticket = Str::random(48);
        OauthTicket::create([
            'sid' => $request->session()->getId(),
            'ticket' => $ticket,
            'status' => 'ready',
            'user_payload' => ['email' => $user->email, 'name' => $user->name],
            'is_new_user' => $isNew,
            'expires_at' => now()->addMinutes(10),
        ]);

        return response()->json([
            'ok' => true,
            'is_new_user' => $isNew,
            'ticket' => $ticket,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'coins' => $user->coins,
                'referral_code' => $user->referral_code,
            ],
        ]);
    }

    public function redeemTicket(Request $request)
    {
        $ticket = (string) $request->query('ticket', $request->input('ticket', ''));
        if ($ticket === '') {
            return response()->json(['ok' => false, 'error' => 'Missing ticket'], 400);
        }
        $row = OauthTicket::query()
            ->where('ticket', $ticket)
            ->where('status', 'ready')
            ->where('expires_at', '>', now())
            ->first();
        if (!$row) {
            return response()->json(['ok' => false, 'error' => 'Login ticket expired or invalid'], 404);
        }
        $email = strtolower((string) ($row->user_payload['email'] ?? ''));
        $user = User::query()->where('email', $email)->first();
        if (!$user) {
            return response()->json(['ok' => false, 'error' => 'User not found'], 404);
        }
        $row->status = 'used';
        $row->completed_at = now();
        $row->save();
        Auth::login($user);
        $request->session()->regenerate();
        return response()->json(['ok' => true, 'user' => ['email' => $user->email, 'name' => $user->name]]);
    }

    private function verifyIdToken(string $credential, string $clientId): ?array
    {
        try {
            $res = Http::timeout(8)->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $credential,
            ]);
            if (!$res->ok()) {
                return null;
            }
            $payload = $res->json();
            if (($payload['aud'] ?? '') !== $clientId) {
                return null;
            }
            if (empty($payload['email'])) {
                return null;
            }
            return $payload;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

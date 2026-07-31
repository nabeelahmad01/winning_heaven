<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Models\Gateway;
use App\Models\Game;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\PublicId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DistributorController extends Controller
{
    public function index()
    {
        return response()->json(['ok' => true, 'items' => Distributor::query()->latest()->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:distributors,email',
            'password' => 'required|string|min:6',
            'type' => 'required|in:A,B',
            'commission_rate' => 'nullable|numeric',
            'website_commission_rate' => 'nullable|numeric',
        ]);
        $item = Distributor::create([
            'public_id' => PublicId::make('dist_'),
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'],
            'type' => $data['type'],
            'commission_rate' => $data['commission_rate'] ?? 0,
            'website_commission_rate' => $data['website_commission_rate'] ?? 0,
        ]);
        return response()->json(['ok' => true, 'item' => $item], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required']);
        $dist = Distributor::query()->where('email', strtolower($data['email']))->first();
        if (!$dist || !Hash::check($data['password'], $dist->password)) {
            return response()->json(['ok' => false, 'error' => 'Invalid credentials'], 401);
        }
        $request->session()->put('distributor_id', $dist->public_id);

        // Bridge to Laravel auth so WH.api auth middleware works
        $user = User::query()->firstOrCreate(
            ['email' => $dist->email],
            [
                'name' => $dist->name,
                'password' => $dist->password,
                'role' => 'distributor_staff',
                'roles' => ['distributor_staff'],
                'distributor_id' => $dist->public_id,
                'referral_code' => strtoupper(substr(md5($dist->email), 0, 8)),
            ]
        );
        if ($user->distributor_id !== $dist->public_id) {
            $user->distributor_id = $dist->public_id;
            $user->role = 'distributor_staff';
            $user->save();
        }
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['ok' => true, 'distributor' => $dist]);
    }

    public function stats(Request $request, string $publicId)
    {
        $deposits = Transaction::query()->where('distributor_id', $publicId)->where('type', 'DEPOSIT')->where('status', 'SUCCESS')->sum('amount');
        $withdraws = Transaction::query()->where('distributor_id', $publicId)->where('type', 'WITHDRAW')->where('status', 'SUCCESS')->sum('amount');
        $dist = Distributor::query()->where('public_id', $publicId)->firstOrFail();
        $earned = CommissionService::commission((float) $deposits, (float) $withdraws, (float) $dist->commission_rate);
        $websiteEarned = CommissionService::commission((float) $deposits, (float) $withdraws, (float) $dist->website_commission_rate);
        $cashed = Transaction::query()
            ->where('user_email', $dist->email)
            ->where('type', 'COMMISSION_WITHDRAW')
            ->whereIn('status', ['SUCCESS', 'PENDING', 'PENDING_COINS'])
            ->sum('amount');
        $websitePaid = Transaction::query()
            ->where('user_email', $dist->email)
            ->where('type', 'WEBSITE_COMMISSION_PAYMENT')
            ->where('status', 'SUCCESS')
            ->sum('amount');

        return response()->json([
            'ok' => true,
            'stats' => [
                'players' => User::query()->where('distributor_id', $publicId)->count(),
                'deposits' => (float) $deposits,
                'withdrawals' => (float) $withdraws,
                'commission' => $earned,
                'available_commission' => max(0, $earned - (float) $cashed),
                'website_commission' => $websiteEarned,
                'website_due' => max(0, $websiteEarned - (float) $websitePaid),
            ],
        ]);
    }

    public function players(string $publicId)
    {
        $items = User::query()->where('distributor_id', $publicId)->where('role', 'user')->latest()->limit(200)->get();
        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function createPlayer(Request $request, string $publicId)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);
        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'],
            'role' => 'user',
            'distributor_id' => $publicId,
            'referral_code' => strtoupper(substr(md5($data['email'] . time()), 0, 8)),
        ]);
        return response()->json(['ok' => true, 'item' => $user], 201);
    }

    public function cashout(Request $request, string $publicId)
    {
        $dist = Distributor::query()->where('public_id', $publicId)->firstOrFail();
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'gateway' => 'nullable|string',
            'code' => 'nullable|string',
            'note' => 'nullable|string',
        ]);
        $stats = $this->stats($request, $publicId)->getData(true)['stats'];
        if ((float) $data['amount'] > (float) $stats['available_commission']) {
            return response()->json(['ok' => false, 'error' => 'Amount exceeds available commission'], 422);
        }
        $tx = Transaction::create([
            'public_id' => PublicId::make('tx_'),
            'user_email' => $dist->email,
            'type' => 'COMMISSION_WITHDRAW',
            'status' => 'PENDING',
            'amount' => $data['amount'],
            'gateway' => $data['gateway'] ?? 'USDT',
            'code' => $data['code'] ?? null,
            'note' => $data['note'] ?? 'Distributor commission cashout',
            'distributor_id' => $publicId,
            'distributor_type' => $dist->type,
            'distributor_name' => $dist->name,
        ]);
        return response()->json(['ok' => true, 'item' => $tx], 201);
    }

    public function websitePay(Request $request, string $publicId)
    {
        $dist = Distributor::query()->where('public_id', $publicId)->firstOrFail();
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'code' => 'nullable|string',
            'screenshot' => 'nullable|string',
            'note' => 'nullable|string',
        ]);
        $tx = Transaction::create([
            'public_id' => PublicId::make('tx_'),
            'user_email' => $dist->email,
            'type' => 'WEBSITE_COMMISSION_PAYMENT',
            'status' => 'PENDING',
            'amount' => $data['amount'],
            'gateway' => 'USDT',
            'code' => $data['code'] ?? null,
            'screenshot' => $data['screenshot'] ?? null,
            'has_screenshot' => !empty($data['screenshot']),
            'note' => $data['note'] ?? 'Website commission payment',
            'distributor_id' => $publicId,
            'distributor_type' => $dist->type,
            'distributor_name' => $dist->name,
        ]);
        return response()->json(['ok' => true, 'item' => $tx], 201);
    }


    public function update(Request $request, string $publicId)
    {
        $dist = Distributor::query()->where('public_id', $publicId)->firstOrFail();
        $data = $request->validate([
            'name' => 'nullable|string',
            'type' => 'nullable|in:A,B',
            'commission_rate' => 'nullable|numeric',
            'website_commission_rate' => 'nullable|numeric',
            'password' => 'nullable|string|min:6',
            'status' => 'nullable|string',
        ]);
        if (!empty($data['password'])) {
            $dist->password = $data['password'];
            unset($data['password']);
        }
        $dist->fill($data);
        $dist->save();
        return response()->json(['ok' => true, 'item' => $dist]);
    }

    public function destroy(string $publicId)
    {
        User::query()->where('distributor_id', $publicId)->update([
            'former_distributor_id' => $publicId,
            'distributor_id' => null,
        ]);
        Gateway::query()->where('distributor_id', $publicId)->delete();
        Distributor::query()->where('public_id', $publicId)->delete();
        return response()->json(['ok' => true]);
    }

    public function statsByDate(Request $request, string $publicId)
    {
        $date = $request->query('date', now()->toDateString());
        $deposits = Transaction::query()->where('distributor_id', $publicId)->where('type', 'DEPOSIT')->where('status', 'SUCCESS')->whereDate('created_at', $date)->sum('amount');
        $withdraws = Transaction::query()->where('distributor_id', $publicId)->where('type', 'WITHDRAW')->where('status', 'SUCCESS')->whereDate('created_at', $date)->sum('amount');
        return response()->json(['ok' => true, 'date' => $date, 'deposits' => (float)$deposits, 'withdrawals' => (float)$withdraws]);
    }

    public function gateways(string $publicId)
    {
        return response()->json(['ok' => true, 'items' => Gateway::query()->where('distributor_id', $publicId)->latest()->get()]);
    }

    public function storeGateway(Request $request, string $publicId)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'tag' => 'nullable|string',
            'phone' => 'nullable|string',
            'theme' => 'nullable|string',
            'qr_image' => 'nullable|string',
            'redirect_url' => 'nullable|string',
            'is_withdraw_active' => 'nullable|boolean',
        ]);
        $item = Gateway::create(array_merge($data, [
            'public_id' => PublicId::make('gw_'),
            'distributor_id' => $publicId,
            'is_withdraw_active' => $data['is_withdraw_active'] ?? true,
        ]));
        return response()->json(['ok' => true, 'item' => $item], 201);
    }

    public function staff(string $publicId)
    {
        $items = User::query()->where('distributor_id', $publicId)->where('role', '!=', 'user')->latest()->get();
        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function storeStaff(Request $request, string $publicId)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'roles' => 'required|array',
            'allowed_game_ids' => 'nullable|array',
        ]);
        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'],
            'role' => $data['roles'][0] ?? 'coins_admin',
            'roles' => $data['roles'],
            'allowed_game_ids' => $data['allowed_game_ids'] ?? [],
            'distributor_id' => $publicId,
            'referral_code' => strtoupper(substr(md5($data['email']), 0, 8)),
            'status' => 'ACTIVE',
        ]);
        return response()->json(['ok' => true, 'item' => $user], 201);
    }
}

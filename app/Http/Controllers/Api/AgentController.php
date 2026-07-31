<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\CampaignRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\PublicId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AgentController extends Controller
{
    public function index()
    {
        return response()->json(['ok' => true, 'items' => Agent::query()->latest()->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:agents,email',
            'password' => 'required|string|min:6',
            'agent_code' => 'nullable|string',
            'account_type' => 'nullable|in:agent,sub-distributor',
            'commission_rate' => 'nullable|numeric',
            'parent_agent_code' => 'nullable|string',
        ]);
        $code = strtoupper($data['agent_code'] ?? ('AGT' . random_int(1000, 9999)));
        $item = Agent::create([
            'public_id' => PublicId::make('agent_'),
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'],
            'agent_code' => $code,
            'account_type' => $data['account_type'] ?? 'agent',
            'role' => ($data['account_type'] ?? 'agent') === 'sub-distributor' ? 'Sub-Distributor' : 'Agent',
            'commission_rate' => $data['commission_rate'] ?? 0,
            'parent_agent_code' => $data['parent_agent_code'] ?? null,
        ]);
        return response()->json(['ok' => true, 'item' => $item], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required']);
        $agent = Agent::query()->where('email', strtolower($data['email']))->first();
        if (!$agent || !Hash::check($data['password'], $agent->password)) {
            return response()->json(['ok' => false, 'error' => 'Invalid credentials'], 401);
        }
        $request->session()->put('agent_id', $agent->public_id);

        $user = \App\Models\User::query()->firstOrCreate(
            ['email' => $agent->email],
            [
                'name' => $agent->name,
                'password' => $agent->password,
                'role' => 'user',
                'agent_code' => $agent->agent_code,
                'referral_code' => $agent->agent_code,
            ]
        );
        if (!$user->agent_code) {
            $user->agent_code = $agent->agent_code;
            $user->save();
        }
        \Illuminate\Support\Facades\Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['ok' => true, 'agent' => $agent]);
    }

    public function stats(string $code)
    {
        $agent = Agent::query()->where('agent_code', $code)->firstOrFail();
        $emails = User::query()->where('agent_code', $code)->pluck('email');
        $deposits = Transaction::query()->whereIn('user_email', $emails)->where('type', 'DEPOSIT')->where('status', 'SUCCESS')->sum('amount');
        $withdraws = Transaction::query()->whereIn('user_email', $emails)->where('type', 'WITHDRAW')->where('status', 'SUCCESS')->sum('amount');
        $commission = CommissionService::commission((float)$deposits, (float)$withdraws, (float)$agent->commission_rate);
        $paid = Transaction::query()
            ->where('user_email', $agent->email)
            ->where('type', 'AFFILIATE_COMMISSION_WITHDRAW')
            ->whereIn('status', ['SUCCESS', 'PENDING', 'PENDING_COINS'])
            ->sum('amount');
        return response()->json([
            'ok' => true,
            'stats' => [
                'players' => $emails->count(),
                'deposits' => $deposits,
                'withdrawals' => $withdraws,
                'commission' => $commission,
                'available_commission' => max(0, $commission - (float) $paid),
            ],
        ]);
    }

    public function campaigns(Request $request)
    {
        $q = CampaignRequest::query()->latest();
        if ($email = $request->query('agent_email')) {
            $q->where('agent_email', strtolower($email));
        }
        return response()->json(['ok' => true, 'items' => $q->limit(100)->get()]);
    }

    public function storeCampaign(Request $request)
    {
        $data = $request->validate([
            'budget' => 'required|numeric',
            'campaign_name' => 'required|string',
            'facebook_page_link' => 'nullable|string',
            'start_date' => 'nullable|string',
            'end_date' => 'nullable|string',
            'notes' => 'nullable|string',
            'payment_proof' => 'nullable|string',
            'agent_code' => 'required|string',
        ]);
        $agent = Agent::query()->where('agent_code', $data['agent_code'])->firstOrFail();
        $item = CampaignRequest::create([
            'public_id' => PublicId::make('camp_'),
            'agent_email' => $agent->email,
            'agent_code' => $agent->agent_code,
            'budget' => $data['budget'],
            'campaign_name' => $data['campaign_name'],
            'facebook_page_link' => $data['facebook_page_link'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'payment_proof' => $data['payment_proof'] ?? null,
            'has_payment_proof' => !empty($data['payment_proof']),
            'status' => 'PENDING',
        ]);
        return response()->json(['ok' => true, 'item' => $item], 201);
    }

    public function updateCampaign(Request $request, string $publicId)
    {
        $item = CampaignRequest::query()->where('public_id', $publicId)->firstOrFail();
        $data = $request->validate([
            'status' => 'required|string',
            'tracking_link' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        $item->status = $data['status'];
        if (array_key_exists('tracking_link', $data)) {
            $item->tracking_link = $data['tracking_link'];
        }
        if (!empty($data['notes'])) {
            $item->notes = trim(($item->notes ? $item->notes . "\n" : '') . $data['notes']);
        }
        $item->save();
        return response()->json(['ok' => true, 'item' => $item]);
    }

    public function cashout(Request $request, string $code)
    {
        $agent = Agent::query()->where('agent_code', $code)->firstOrFail();
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'gateway' => 'nullable|string',
            'code' => 'nullable|string',
            'note' => 'nullable|string',
        ]);
        $stats = $this->stats($code)->getData(true)['stats'];
        $earned = (float) ($stats['commission'] ?? 0);
        $paid = Transaction::query()
            ->where('user_email', $agent->email)
            ->where('type', 'AFFILIATE_COMMISSION_WITHDRAW')
            ->whereIn('status', ['SUCCESS', 'PENDING', 'PENDING_COINS'])
            ->sum('amount');
        $available = max(0, $earned - (float) $paid);
        if ((float) $data['amount'] > $available) {
            return response()->json(['ok' => false, 'error' => 'Amount exceeds available balance', 'available' => $available], 422);
        }
        $tx = Transaction::create([
            'public_id' => PublicId::make('tx_'),
            'user_email' => $agent->email,
            'type' => 'AFFILIATE_COMMISSION_WITHDRAW',
            'status' => 'PENDING',
            'amount' => $data['amount'],
            'gateway' => $data['gateway'] ?? 'USDT',
            'code' => $data['code'] ?? null,
            'note' => $data['note'] ?? ('Affiliate cashout ' . $agent->agent_code),
        ]);
        return response()->json(['ok' => true, 'item' => $tx, 'available' => $available - (float) $data['amount']], 201);
    }

    public function update(Request $request, string $id)
    {
        $agent = Agent::query()->where('public_id', $id)->orWhere('agent_code', $id)->orWhere('email', $id)->firstOrFail();
        $data = $request->validate([
            'name' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'commission_rate' => 'nullable|numeric',
            'status' => 'nullable|string',
            'account_type' => 'nullable|string',
        ]);
        if (!empty($data['password'])) {
            $agent->password = $data['password'];
            unset($data['password']);
        }
        $agent->fill($data);
        $agent->save();
        return response()->json(['ok' => true, 'item' => $agent]);
    }

    public function destroy(string $id)
    {
        Agent::query()->where('public_id', $id)->orWhere('agent_code', $id)->delete();
        return response()->json(['ok' => true]);
    }

    public function signupReport(Request $request, string $code)
    {
        $from = $request->query('fromDate', now()->subDays(30)->toDateString());
        $to = $request->query('toDate', now()->toDateString());
        $players = User::query()->where('agent_code', $code)
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->get(['email','name','created_at','campaign','status']);
        $emails = $players->pluck('email');
        $deposited = Transaction::query()->whereIn('user_email', $emails)->where('type','DEPOSIT')->where('status','SUCCESS')->distinct('user_email')->count('user_email');
        return response()->json([
            'ok' => true,
            'from' => $from,
            'to' => $to,
            'signups' => $players->count(),
            'deposited_players' => $deposited,
            'players' => $players,
        ]);
    }

    public function dailyTransactions(Request $request, string $code)
    {
        $date = $request->query('date', now()->toDateString());
        $emails = User::query()->where('agent_code', $code)->pluck('email');
        $items = Transaction::query()->whereIn('user_email', $emails)->whereDate('created_at', $date)->latest()->limit(200)->get();
        return response()->json(['ok' => true, 'date' => $date, 'items' => $items]);
    }
}

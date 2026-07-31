<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountRequest;
use App\Models\CoinsNotification;
use App\Models\SupportMessage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class AdminStatsController extends Controller
{
    public function index(Request $request)
    {
        $dist = $request->query('distributor_id');

        $tx = Transaction::query();
        $coins = CoinsNotification::query()->where('status', 'PENDING');
        $reqs = AccountRequest::query()->where('status', 'PENDING');
        $support = SupportMessage::query()->where('read', false)->where('sender_type', 'player');

        if ($dist) {
            $tx->where('distributor_id', $dist);
            $coins->where('distributor_id', $dist);
            $reqs->where('distributor_id', $dist);
            $support->where('distributor_id', $dist);
        } else {
            $tx->where(function ($q) {
                $q->whereNull('distributor_type')->orWhere('distributor_type', '!=', 'B');
            });
        }

        $pendingDeposits = (clone $tx)->where('type', 'DEPOSIT')->where('status', 'PENDING')->count();
        $pendingWithdraws = (clone $tx)->where('type', 'WITHDRAW')->where('status', 'PENDING')->count();

        return response()->json([
            'ok' => true,
            'stats' => [
                'players' => User::query()->where('role', 'user')->count(),
                'pendingDeposits' => $pendingDeposits,
                'pendingWithdraws' => $pendingWithdraws,
                // Jackpot: one ledger badge = pending deposits + pending withdraws (finance queue)
                'pendingTransactionsCount' => $pendingDeposits + $pendingWithdraws,
                'pendingCoins' => $coins->count(),
                'pendingRequests' => $reqs->count(),
                'unreadSupport' => $support->count(),
                'depositSum' => (clone $tx)->where('type', 'DEPOSIT')->where('status', 'SUCCESS')->sum('amount'),
                'withdrawSum' => (clone $tx)->where('type', 'WITHDRAW')->where('status', 'SUCCESS')->sum('amount'),
            ],
        ]);
    }

    public function byDate(Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        return response()->json([
            'ok' => true,
            'date' => $date,
            'deposits' => (float) Transaction::query()->where('type','DEPOSIT')->whereIn('status',['SUCCESS','COINS_LOADING'])->whereDate('created_at',$date)->sum('amount'),
            'withdrawals' => (float) Transaction::query()->where('type','WITHDRAW')->where('status','SUCCESS')->whereDate('created_at',$date)->sum('amount'),
        ]);
    }
}

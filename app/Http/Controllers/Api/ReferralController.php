<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoinsNotification;
use App\Models\PendingReferral;
use App\Services\PublicId;
use App\Services\Realtime;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function index(Request $request)
    {
        $email = strtolower($request->query('email') ?: $request->user()->email);
        $items = PendingReferral::query()
            ->where('referrer_email', $email)
            ->latest()
            ->limit(100)
            ->get();
        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function claim(Request $request)
    {
        $data = $request->validate([
            'public_id' => 'required|string',
            'game_title' => 'required|string',
        ]);
        $user = $request->user();
        $item = PendingReferral::query()
            ->where('public_id', $data['public_id'])
            ->where('referrer_email', $user->email)
            ->where('status', 'PENDING')
            ->firstOrFail();

        $item->status = 'CLAIMED';
        $item->claimed_at = now();
        $item->save();

        CoinsNotification::create([
            'public_id' => PublicId::make('cn_'),
            'user_email' => $user->email,
            'game_title' => $data['game_title'],
            'deposit_amount' => $item->reward_coins,
            'bonus_applied' => -2,
            'total_coins' => $item->reward_coins,
            'status' => 'PENDING',
            'distributor_id' => $user->distributor_id,
            'notified_at' => now(),
        ]);
        Realtime::publish('coins', ['distributorId' => $user->distributor_id]);
        return response()->json(['ok' => true, 'item' => $item]);
    }
}

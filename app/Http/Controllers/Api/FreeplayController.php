<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoinsNotification;
use App\Models\Transaction;
use App\Services\FreeplayService;
use App\Services\PublicId;
use App\Services\Realtime;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class FreeplayController extends Controller
{
    public function gate(Request $request)
    {
        return response()->json([
            'ok' => true,
            'gate' => FreeplayService::gate($request->user()->email),
            'is_freeplay_session' => FreeplayService::isFreeplaySession($request->user()->email),
            'min_request' => FreeplayService::minRequest(),
            'cashout_cap' => FreeplayService::cashoutCap(),
        ]);
    }

    public function claim(Request $request)
    {
        $data = $request->validate([
            'game_title' => 'required|string',
            'note' => 'nullable|string',
        ]);
        $user = $request->user();
        $promoBypass = (bool) preg_match('/promo freeplay/i', (string) ($data['note'] ?? ''));
        $gate = FreeplayService::gate($user->email, $promoBypass);
        if (!$gate['can_claim']) {
            return response()->json(['ok' => false, 'error' => $gate['message'], 'gate' => $gate], 422);
        }

        $settings = SettingsService::global();
        $amount = (float) ($settings['signup_freeplay'] ?? 3);
        $code = $gate['is_first'] ? 'SIGNUP-FREE3' : 'FREEPLAY';

        $tx = Transaction::create([
            'public_id' => PublicId::make('tx_'),
            'user_email' => $user->email,
            'type' => 'BONUS',
            'status' => 'COINS_LOADING',
            'amount' => $amount,
            'code' => $code,
            'game_title' => $data['game_title'],
            'gateway' => $gate['is_first'] ? 'Signup Bonus' : 'Freeplay',
            'note' => $data['note'] ?? null,
            'distributor_id' => $user->distributor_id,
        ]);

        CoinsNotification::create([
            'public_id' => PublicId::make('cn_'),
            'user_email' => $user->email,
            'game_title' => $data['game_title'],
            'deposit_amount' => $amount,
            'bonus_applied' => -3,
            'total_coins' => $amount,
            'status' => 'PENDING',
            'transaction_id' => $tx->public_id,
            'is_freeplay' => true,
            'distributor_id' => $user->distributor_id,
            'notified_at' => now(),
        ]);

        Realtime::publish('coins', ['distributorId' => $user->distributor_id]);
        return response()->json(['ok' => true, 'amount' => $amount, 'code' => $code, 'gate' => $gate]);
    }
}

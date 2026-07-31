<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoinsNotification;
use App\Models\PendingReferral;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BonusService;
use App\Services\FreeplayService;
use App\Services\PublicId;
use App\Services\Realtime;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $q = Transaction::query()->latest();

        if ($email = $request->query('email')) {
            $q->where('user_email', strtolower($email));
        }
        if ($type = $request->query('type')) {
            $q->where('type', $type);
        }
        if ($status = $request->query('status')) {
            $statuses = array_filter(array_map('trim', explode(',', $status)));
            count($statuses) > 1 ? $q->whereIn('status', $statuses) : $q->where('status', $statuses[0] ?? $status);
        }
        if ($dist = $request->query('distributor_id')) {
            $q->where('distributor_id', $dist);
        }
        if ($request->boolean('hq_global')) {
            $q->where(function ($qq) {
                $qq->whereNull('distributor_type')->orWhere('distributor_type', '!=', 'B');
            });
        }

        return response()->json(['ok' => true, 'items' => $q->limit(200)->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'gateway' => 'nullable|string',
            'game_title' => 'nullable|string',
            'code' => 'nullable|string',
            'screenshot' => 'nullable|string',
            'tag_qr_screenshot' => 'nullable|string',
            'name_on_tag' => 'nullable|string',
            'phone_on_tag' => 'nullable|string',
            'email_on_tag' => 'nullable|string',
            'note' => 'nullable|string',
            'is_freeplay_withdraw' => 'nullable|boolean',
            'game_amount' => 'nullable|numeric',
            'game_username' => 'nullable|string',
            'parent_tx_id' => 'nullable|string',
            'is_remainder_request' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $settings = SettingsService::frontend();
        $global = SettingsService::global();
        $amount = (float) $data['amount'];

        // Remainder claim from partial payout hold
        if (!empty($data['is_remainder_request']) && !empty($data['parent_tx_id'])) {
            $parent = Transaction::query()->where('public_id', $data['parent_tx_id'])->firstOrFail();
            if ($parent->user_email !== $user->email) {
                return response()->json(['ok' => false, 'error' => 'Not your transaction'], 403);
            }
            if (!$parent->remainder_claim_available_at || now()->lt($parent->remainder_claim_available_at)) {
                return response()->json(['ok' => false, 'error' => 'Remainder not claimable yet'], 422);
            }
            if ($parent->remainder_requested) {
                return response()->json(['ok' => false, 'error' => 'Remainder already requested'], 422);
            }
            $hold = (float) $parent->payout_hold;
            if ($hold <= 0) {
                return response()->json(['ok' => false, 'error' => 'No hold amount'], 422);
            }
            $parent->remainder_requested = true;
            $parent->remainder_status = 'REQUESTED';
            $parent->save();

            $tx = Transaction::create([
                'public_id' => PublicId::make('tx_'),
                'user_email' => $user->email,
                'type' => $parent->type,
                'status' => 'PENDING',
                'amount' => $hold,
                'gateway' => $parent->gateway,
                'code' => $parent->code,
                'game_title' => $parent->game_title,
                'note' => 'Remainder claim of hold $' . number_format($hold, 2),
                'parent_tx_id' => $parent->public_id,
                'is_freeplay_withdraw' => (bool) $parent->is_freeplay_withdraw,
                'distributor_id' => $user->distributor_id,
                'distributor_type' => $user->distributor_id ? optional(\App\Models\Distributor::query()->where('public_id', $user->distributor_id)->first())->type : null,
            ]);
            Realtime::publish('transactions', ['distributorId' => $user->distributor_id, 'txType' => $tx->type]);
            return response()->json(['ok' => true, 'item' => $tx], 201);
        }

        if ($data['type'] === 'DEPOSIT' && $amount < (float) ($settings['minimum_deposit_limit'] ?? 5)) {
            return response()->json(['ok' => false, 'error' => 'Below minimum deposit'], 422);
        }
        if ($data['type'] === 'WITHDRAW' && $amount < (float) ($settings['minimum_withdrawal_limit'] ?? 25)) {
            return response()->json(['ok' => false, 'error' => 'Below minimum withdraw'], 422);
        }

        // Freeplay bonus via transactions endpoint (Jackpot path)
        if ($data['type'] === 'BONUS' && in_array($data['code'] ?? '', ['SIGNUP-FREE3', 'FREEPLAY'], true)) {
            $promoBypass = (bool) preg_match('/promo freeplay/i', (string) ($data['note'] ?? ''));
            $gate = FreeplayService::gate($user->email, $promoBypass);
            if (!$gate['can_claim']) {
                return response()->json(['ok' => false, 'error' => $gate['message'], 'gate' => $gate], 422);
            }
            if (empty($data['game_title'])) {
                return response()->json(['ok' => false, 'error' => 'Select a game to claim freeplay.'], 422);
            }
            $fpAmount = (float) ($global['signup_freeplay'] ?? 3);
            $code = $gate['is_first'] ? 'SIGNUP-FREE3' : 'FREEPLAY';
            $tx = Transaction::create([
                'public_id' => PublicId::make('tx_'),
                'user_email' => $user->email,
                'type' => 'BONUS',
                'status' => 'COINS_LOADING',
                'amount' => $fpAmount,
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
                'deposit_amount' => $fpAmount,
                'bonus_applied' => -3,
                'total_coins' => $fpAmount,
                'status' => 'PENDING',
                'transaction_id' => $tx->public_id,
                'is_freeplay' => true,
                'distributor_id' => $user->distributor_id,
                'notified_at' => now(),
            ]);
            Realtime::publish('coins', ['distributorId' => $user->distributor_id]);
            return response()->json(['ok' => true, 'item' => $tx], 201);
        }

        $isFreeplayWithdraw = false;
        if ($data['type'] === 'WITHDRAW') {
            $isFreeplayWithdraw = FreeplayService::isFreeplaySession($user->email)
                || (bool) ($data['is_freeplay_withdraw'] ?? false);
            if ($isFreeplayWithdraw && $amount < FreeplayService::minRequest()) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Freeplay withdraw request must be at least $' . number_format(FreeplayService::minRequest(), 0) . '.',
                ], 422);
            }
        }

        $status = $data['type'] === 'WITHDRAW' ? 'PENDING_COINS' : 'PENDING';

        $tx = Transaction::create([
            'public_id' => PublicId::make('tx_'),
            'user_email' => $user->email,
            'type' => $data['type'],
            'status' => $status,
            'amount' => $amount,
            'gateway' => $data['gateway'] ?? null,
            'game_title' => $data['game_title'] ?? null,
            'game_username' => $data['game_username'] ?? null,
            'code' => $data['code'] ?? null,
            'screenshot' => $data['screenshot'] ?? null,
            'tag_qr_screenshot' => $data['tag_qr_screenshot'] ?? null,
            'has_screenshot' => !empty($data['screenshot']),
            'name_on_tag' => $data['name_on_tag'] ?? null,
            'phone_on_tag' => $data['phone_on_tag'] ?? null,
            'email_on_tag' => $data['email_on_tag'] ?? null,
            'note' => $data['note'] ?? null,
            'is_freeplay_withdraw' => $isFreeplayWithdraw,
            'game_amount' => $data['game_amount'] ?? null,
            'distributor_id' => $user->distributor_id,
        ]);

        if ($data['type'] === 'WITHDRAW') {
            CoinsNotification::create([
                'public_id' => PublicId::make('cn_'),
                'user_email' => $user->email,
                'game_title' => $data['game_title'] ?? '',
                'game_username' => $data['game_username'] ?? null,
                'deposit_amount' => $amount,
                'bonus_applied' => -1,
                'total_coins' => -1 * $amount,
                'status' => 'PENDING',
                'transaction_id' => $tx->public_id,
                'is_freeplay_withdraw' => $isFreeplayWithdraw,
                'distributor_id' => $user->distributor_id,
                'notified_at' => now(),
            ]);
            Realtime::publish('coins', ['distributorId' => $user->distributor_id]);
        }

        Realtime::publish('transactions', [
            'distributorId' => $user->distributor_id,
            'txType' => $tx->type,
        ]);

        return response()->json(['ok' => true, 'item' => $tx], 201);
    }

    public function update(Request $request, string $publicId)
    {
        $tx = Transaction::query()->where('public_id', $publicId)->firstOrFail();
        $data = $request->validate([
            'status' => 'nullable|string',
            'note' => 'nullable|string',
            'payout_hold' => 'nullable|numeric',
            'payout_sent' => 'nullable|numeric',
            'payout_amount' => 'nullable|numeric',
            'payout_proof' => 'nullable|string',
            'screenshot' => 'nullable|string',
            'processed_by' => 'nullable|string',
            'remainder_wait_hours' => 'nullable|integer',
            'remainder_wait_minutes' => 'nullable|integer',
        ]);

        $actor = $request->user();
        $newStatus = $data['status'] ?? $tx->status;

        // Deposit approve → COINS_LOADING + coins task (Jackpot parity)
        if ($tx->type === 'DEPOSIT' && $newStatus === 'SUCCESS' && !in_array($tx->status, ['SUCCESS', 'COINS_LOADING'], true)) {
            $player = User::query()->where('email', $tx->user_email)->first();
            $bonus = $player ? BonusService::depositBonusPercent($player) : 20;
            $coins = BonusService::coinsFromDeposit((float) $tx->amount, $bonus);

            $exists = CoinsNotification::query()->where('transaction_id', $tx->public_id)->exists();
            if (!$exists) {
                CoinsNotification::create([
                    'public_id' => PublicId::make('cn_'),
                    'user_email' => $tx->user_email,
                    'game_title' => $tx->game_title,
                    'deposit_amount' => $tx->amount,
                    'bonus_applied' => $bonus,
                    'total_coins' => $coins,
                    'status' => 'PENDING',
                    'transaction_id' => $tx->public_id,
                    'distributor_id' => $tx->distributor_id,
                    'notified_at' => now(),
                ]);
            }

            if ($player && $player->pending_deposit_bonus_percent !== null) {
                $player->update([
                    'pending_deposit_bonus_percent' => null,
                    'pending_bonus_promo_id' => null,
                    'pending_bonus_promo_title' => null,
                ]);
            }

            // First deposit referral reward (Jackpot parity)
            if ($player && $player->referred_by) {
                $priorSuccess = Transaction::query()
                    ->where('user_email', $tx->user_email)
                    ->where('type', 'DEPOSIT')
                    ->whereIn('status', ['SUCCESS', 'COINS_LOADING'])
                    ->where('public_id', '!=', $tx->public_id)
                    ->exists();
                if (!$priorSuccess) {
                    $already = PendingReferral::query()
                        ->where('referee_email', $tx->user_email)
                        ->exists();
                    if (!$already) {
                        $pct = (float) (SettingsService::global()['referral_bonus'] ?? 10);
                        PendingReferral::create([
                            'public_id' => PublicId::make('ref_'),
                            'referrer_email' => strtolower($player->referred_by),
                            'referee_email' => $tx->user_email,
                            'reward_coins' => round(((float) $tx->amount) * ($pct / 100), 2),
                            'status' => 'PENDING',
                        ]);
                    }
                }
            }

            $newStatus = 'COINS_LOADING';
            Realtime::publish('coins', ['distributorId' => $tx->distributor_id]);
        }

        if ($tx->type === 'WITHDRAW' && $newStatus === 'PENDING' && $tx->is_freeplay_withdraw) {
            $cap = FreeplayService::cashoutCap();
            $data['payout_amount'] = $cap;
            $tx->amount = $cap;
            $tx->note = $tx->note ?: ('Freeplay win capped at $' . number_format($cap, 0) . ' max cashout.');
        }

        if (isset($data['payout_hold']) && (float) $data['payout_hold'] > 0) {
            $hours = (int) ($data['remainder_wait_hours'] ?? 0);
            $mins = (int) ($data['remainder_wait_minutes'] ?? 0);
            $tx->remainder_claim_available_at = now()->addHours($hours)->addMinutes($mins);
            $tx->payout_hold = $data['payout_hold'];
        }

        $tx->status = $newStatus;
        $tx->note = $data['note'] ?? $tx->note;
        $tx->payout_sent = $data['payout_sent'] ?? $tx->payout_sent;
        $tx->payout_amount = $data['payout_amount'] ?? $tx->payout_amount;
        $tx->payout_proof = $data['payout_proof'] ?? $tx->payout_proof;
        $tx->screenshot = $data['screenshot'] ?? $tx->screenshot;
        $tx->processed_by = $data['processed_by'] ?? ($actor?->email);
        if (in_array($newStatus, ['SUCCESS', 'COINS_LOADING'], true) && $tx->type === 'DEPOSIT') {
            $tx->approved_by = $actor?->email;
        }
        if ($newStatus === 'SUCCESS' && $tx->type !== 'DEPOSIT') {
            $tx->approved_by = $actor?->email;
        }
        $tx->remainder_wait_hours = $data['remainder_wait_hours'] ?? $tx->remainder_wait_hours;
        $tx->remainder_wait_minutes = $data['remainder_wait_minutes'] ?? $tx->remainder_wait_minutes;
        $tx->save();

        Realtime::publish('transactions', [
            'distributorId' => $tx->distributor_id,
            'txType' => $tx->type,
        ]);

        return response()->json(['ok' => true, 'item' => $tx->fresh()]);
    }
}

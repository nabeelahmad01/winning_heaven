<?php

namespace App\Services;

use App\Models\Transaction;

class FreeplayService
{
    public static function isFreeplayBonus(Transaction $tx): bool
    {
        return $tx->type === 'BONUS'
            && in_array($tx->code, ['SIGNUP-FREE3', 'FREEPLAY'], true);
    }

    /** Detect freeplay cashout session: last SUCCESS freeplay, no SUCCESS deposit or freeplay withdraw after it. */
    public static function isFreeplaySession(string $email): bool
    {
        $txs = Transaction::query()
            ->where('user_email', strtolower($email))
            ->latest()
            ->limit(200)
            ->get();

        $lastFreeplay = $txs->first(fn ($t) => self::isFreeplayBonus($t) && $t->status === 'SUCCESS');
        if (!$lastFreeplay) {
            return false;
        }

        $hasDepositAfter = $txs->contains(function ($t) use ($lastFreeplay) {
            return $t->type === 'DEPOSIT'
                && $t->status === 'SUCCESS'
                && $t->created_at > $lastFreeplay->created_at;
        });

        $hasFpWithdrawAfter = $txs->contains(function ($t) use ($lastFreeplay) {
            return $t->type === 'WITHDRAW'
                && $t->is_freeplay_withdraw
                && $t->created_at > $lastFreeplay->created_at;
        });

        return !$hasDepositAfter && !$hasFpWithdrawAfter;
    }

    /**
     * @return array{can_claim:bool,phase:string,is_first:bool,message:string,deposit_total?:float,remaining?:float}
     */
    public static function gate(string $email, bool $promoBypass = false): array
    {
        $settings = SettingsService::global();
        $threshold = (float) ($settings['repeat_freeplay_deposit_threshold'] ?? 25);

        $txs = Transaction::query()
            ->where('user_email', strtolower($email))
            ->latest()
            ->limit(300)
            ->get();

        $pending = $txs->first(function ($t) {
            return self::isFreeplayBonus($t)
                && in_array($t->status, ['COINS_LOADING', 'PENDING', 'PENDING_COINS'], true);
        });
        if ($pending) {
            return [
                'can_claim' => false,
                'phase' => 'pending',
                'is_first' => false,
                'message' => 'You already have a freeplay request pending. Please wait for it to be processed.',
            ];
        }

        $success = $txs->filter(fn ($t) => self::isFreeplayBonus($t) && $t->status === 'SUCCESS');
        if ($success->isEmpty()) {
            return [
                'can_claim' => true,
                'phase' => 'signup',
                'is_first' => true,
                'message' => 'Signup freeplay available.',
            ];
        }

        if ($promoBypass) {
            return [
                'can_claim' => true,
                'phase' => 'promo',
                'is_first' => false,
                'message' => 'Promo freeplay available.',
            ];
        }

        $mostRecent = $success->first();
        $anchor = $mostRecent;

        $lastCashoutAfter = $txs->first(function ($t) use ($mostRecent) {
            return $t->type === 'WITHDRAW'
                && $t->status !== 'FAILED'
                && $t->created_at > $mostRecent->created_at;
        });
        if ($lastCashoutAfter) {
            $anchor = $lastCashoutAfter;
        }

        $depositTotal = (float) $txs
            ->filter(fn ($t) => $t->type === 'DEPOSIT' && $t->status === 'SUCCESS' && $t->created_at > $anchor->created_at)
            ->sum('amount');

        if ($depositTotal >= $threshold) {
            return [
                'can_claim' => true,
                'phase' => 'deposit',
                'is_first' => false,
                'deposit_total' => $depositTotal,
                'remaining' => 0,
                'message' => 'You qualify for another freeplay after depositing $' . number_format($threshold, 0) . '+.',
            ];
        }

        $remaining = max(0, $threshold - $depositTotal);
        return [
            'can_claim' => false,
            'phase' => 'need_deposit',
            'is_first' => false,
            'deposit_total' => $depositTotal,
            'remaining' => $remaining,
            'message' => $lastCashoutAfter
                ? "You will be eligible for freeplay after depositing \${$remaining} more since your last cashout (\${$depositTotal} / \${$threshold})."
                : "You will be eligible for freeplay after depositing \${$remaining} more (\${$depositTotal} / \${$threshold}).",
        ];
    }

    public static function cashoutCap(): float
    {
        return (float) (SettingsService::global()['freeplay_cashout_cap'] ?? 30);
    }

    public static function minRequest(): float
    {
        return (float) (SettingsService::global()['freeplay_min_request'] ?? 100);
    }
}

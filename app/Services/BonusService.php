<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;

class BonusService
{
    public static function depositBonusPercent(User $user, ?float $promoPercent = null): float
    {
        $settings = SettingsService::global();
        if ($promoPercent !== null) {
            return (float) $promoPercent;
        }
        if ($user->pending_deposit_bonus_percent !== null) {
            return (float) $user->pending_deposit_bonus_percent;
        }
        $hasSuccessDeposit = Transaction::query()
            ->where('user_email', $user->email)
            ->where('type', 'DEPOSIT')
            ->where('status', 'SUCCESS')
            ->exists();
        return $hasSuccessDeposit
            ? (float) $settings['regular_deposit_bonus']
            : (float) $settings['first_deposit_bonus'];
    }

    public static function coinsFromDeposit(float $amount, float $bonusPercent): float
    {
        return floor($amount * (1 + ($bonusPercent / 100)));
    }
}

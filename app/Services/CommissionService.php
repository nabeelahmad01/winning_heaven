<?php

namespace App\Services;

class CommissionService
{
    public static function netProfit(float $deposits, float $withdrawals): float
    {
        return max(0, $deposits - $withdrawals);
    }

    public static function commission(float $deposits, float $withdrawals, float $ratePercent): float
    {
        return self::netProfit($deposits, $withdrawals) * ($ratePercent / 100);
    }
}

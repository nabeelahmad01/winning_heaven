<?php

namespace App\Services;

use App\Events\OpsUpdated;

class Realtime
{
    public static function publish(string $type, array $payload = []): void
    {
        try {
            event(new OpsUpdated($type, $payload));
        } catch (\Throwable $e) {
            // Broadcasting may be log driver in local — still fine for sync listeners later
            report($e);
        }
    }
}

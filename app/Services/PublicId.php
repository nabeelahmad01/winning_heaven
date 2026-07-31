<?php

namespace App\Services;

class PublicId
{
    public static function make(string $prefix = ''): string
    {
        $id = (string) (int) (microtime(true) * 1000) . random_int(100, 999);
        return $prefix ? $prefix . $id : $id;
    }
}

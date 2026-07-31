<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $fillable = [
        'endpoint','user_email','audience','distributor_id','type','platform','subscription',
        'native_token','user_agent','client_kind','standalone'
    ];

    protected function casts(): array
    {
        return [
            'subscription' => 'array',
            'standalone' => 'boolean',
        ];
    }
}

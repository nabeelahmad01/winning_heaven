<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingReferral extends Model
{
    protected $fillable = [
        'public_id','referrer_email','referee_email','reward_coins','status','claimed_at'
    ];

    protected function casts(): array
    {
        return [
            'reward_coins' => 'decimal:2',
            'claimed_at' => 'datetime',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoinsNotification extends Model
{
    protected $fillable = [
        'public_id','user_email','game_title','game_username','deposit_amount','bonus_applied','total_coins',
        'status','read','hold_note','processed_by','transaction_id','is_freeplay','is_freeplay_withdraw',
        'distributor_id','distributor_type','notified_at'
    ];

    protected function casts(): array
    {
        return [
            'deposit_amount' => 'decimal:2',
            'bonus_applied' => 'decimal:2',
            'total_coins' => 'decimal:2',
            'read' => 'boolean',
            'is_freeplay' => 'boolean',
            'is_freeplay_withdraw' => 'boolean',
            'notified_at' => 'datetime',
        ];
    }
}

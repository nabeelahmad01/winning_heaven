<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'public_id','title','message','target_group','image','promo_type','freeplay_amount','bonus_percent'
    ];

    protected function casts(): array
    {
        return [
            'freeplay_amount' => 'decimal:2',
            'bonus_percent' => 'decimal:2',
        ];
    }
}

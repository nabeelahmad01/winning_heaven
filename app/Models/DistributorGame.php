<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistributorGame extends Model
{
    protected $fillable = [
        'distributor_id','game_id','title','available_coins','used_coins','open_panel_link'
    ];

    protected function casts(): array
    {
        return [
            'available_coins' => 'decimal:2',
            'used_coins' => 'decimal:2',
        ];
    }
}

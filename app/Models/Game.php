<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = [
        'public_id','title','badge','image','link','open_panel_link','available_coins','used_coins'
    ];

    protected function casts(): array
    {
        return [
            'available_coins' => 'decimal:2',
            'used_coins' => 'decimal:2',
        ];
    }
}

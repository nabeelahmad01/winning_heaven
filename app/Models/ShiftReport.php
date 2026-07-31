<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftReport extends Model
{
    protected $fillable = [
        'public_id','staff_email','shift_name','shift_date','total_loaded','notes'
    ];

    protected function casts(): array
    {
        return [
            'shift_date' => 'datetime',
            'total_loaded' => 'decimal:2',
        ];
    }
}

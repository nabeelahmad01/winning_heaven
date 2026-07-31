<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = [
        'public_id','name','email','password','agent_code','account_type','role','status',
        'commission_rate','parent_agent_code','auth_provider'
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'password' => 'hashed',
        ];
    }
}

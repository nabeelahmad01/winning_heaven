<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Distributor extends Model
{
    protected $fillable = [
        'public_id','name','email','password','role','type','commission_rate','website_commission_rate','status'
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'website_commission_rate' => 'decimal:2',
            'password' => 'hashed',
        ];
    }

    public function isTypeB(): bool
    {
        return strtoupper((string) $this->type) === 'B';
    }
}

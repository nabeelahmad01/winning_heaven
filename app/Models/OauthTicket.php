<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OauthTicket extends Model
{
    protected $fillable = [
        'sid','ticket','status','user_payload','is_new_user','completed_at','expires_at'
    ];

    protected function casts(): array
    {
        return [
            'user_payload' => 'array',
            'is_new_user' => 'boolean',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}

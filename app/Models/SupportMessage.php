<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    protected $fillable = [
        'public_id','user_email','user_name','message','attachment','has_attachment','sender_type',
        'sender_email','read','distributor_id','distributor_type','distributor_name'
    ];

    protected function casts(): array
    {
        return [
            'has_attachment' => 'boolean',
            'read' => 'boolean',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    protected $fillable = [
        'public_id','name','subtitle','tag','phone','theme','qr_image','redirect_url',
        'is_withdraw_active','require_name_on_tag','require_tag','require_phone_on_tag',
        'require_email_on_tag','distributor_id'
    ];

    protected function casts(): array
    {
        return [
            'is_withdraw_active' => 'boolean',
            'require_name_on_tag' => 'boolean',
            'require_tag' => 'boolean',
            'require_phone_on_tag' => 'boolean',
            'require_email_on_tag' => 'boolean',
        ];
    }
}

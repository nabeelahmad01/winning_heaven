<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountRequest extends Model
{
    protected $fillable = [
        'public_id','user_email','game_title','status','game_account_username','game_account_password',
        'processed_by','rejection_reason','referral_reward_id','distributor_id','distributor_type','distributor_name'
    ];
}

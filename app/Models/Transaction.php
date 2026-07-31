<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'public_id','user_email','type','status','amount','gateway','code','game_title','game_username',
        'note','screenshot','tag_qr_screenshot','payout_proof','has_screenshot','proof_pending',
        'name_on_tag','phone_on_tag','email_on_tag','payout_qr','payout_sent','payout_hold','payout_amount',
        'remainder_paid','remainder_requested','remainder_status','remainder_claim_available_at',
        'remainder_wait_hours','remainder_wait_minutes','parent_tx_id','is_freeplay_withdraw','game_amount',
        'approved_by','allotted_by','processed_by','coins_allotted_at','coins_hold_note','coins_hold_at',
        'distributor_id','distributor_type','distributor_name'
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payout_sent' => 'decimal:2',
            'payout_hold' => 'decimal:2',
            'payout_amount' => 'decimal:2',
            'game_amount' => 'decimal:2',
            'has_screenshot' => 'boolean',
            'proof_pending' => 'boolean',
            'remainder_paid' => 'boolean',
            'remainder_requested' => 'boolean',
            'is_freeplay_withdraw' => 'boolean',
            'remainder_claim_available_at' => 'datetime',
            'coins_allotted_at' => 'datetime',
            'coins_hold_at' => 'datetime',
        ];
    }
}

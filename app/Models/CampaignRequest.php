<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignRequest extends Model
{
    protected $fillable = [
        'public_id','agent_email','agent_code','budget','campaign_name','facebook_page_link',
        'start_date','end_date','notes','payment_proof','has_payment_proof','status','tracking_link'
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'has_payment_proof' => 'boolean',
        ];
    }
}

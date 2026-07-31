<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletedUser extends Model
{
    protected $fillable = [
        'email','snapshot','deleted_at','deleted_by','deleted_entity_type','wipe_game_access',
        'restore_game_titles','linked_player_emails','former_distributor_id'
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'restore_game_titles' => 'array',
            'linked_player_emails' => 'array',
            'wipe_game_access' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }
}

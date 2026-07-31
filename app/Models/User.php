<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'roles', 'coins', 'referral_code', 'referred_by',
        'distributor_id', 'former_distributor_id', 'agent_code', 'campaign', 'is_subscribed',
        'status', 'allowed_game_ids', 'pending_deposit_bonus_percent', 'pending_bonus_promo_id',
        'pending_bonus_promo_title', 'pending_bonus_freeplay', 'session_revoked', 'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'roles' => 'array',
            'allowed_game_ids' => 'array',
            'coins' => 'decimal:2',
            'is_subscribed' => 'boolean',
            'session_revoked' => 'boolean',
            'pending_deposit_bonus_percent' => 'decimal:2',
            'pending_bonus_freeplay' => 'decimal:2',
        ];
    }

    public function hasRole(string $role): bool
    {
        if ($this->role === $role) {
            return true;
        }
        $roles = $this->roles ?? [];
        if (is_string($this->role) && str_contains($this->role, ',')) {
            $roles = array_merge($roles, array_map('trim', explode(',', $this->role)));
        }
        return in_array($role, $roles, true);
    }

    public function isStaff(): bool
    {
        foreach (['admin','operation_admin','financial_admin','coins_admin','support_admin','distributor_staff'] as $r) {
            if ($this->hasRole($r)) return true;
        }
        return false;
    }
}

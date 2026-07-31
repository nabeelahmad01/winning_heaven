<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Distributor;
use App\Models\Game;
use App\Models\Gateway;
use App\Models\Setting;
use App\Models\User;
use App\Services\PublicId;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;

class WinningHeavenSeeder extends Seeder
{
    public function run(): void
    {
        Setting::putValue('global_settings', SettingsService::globalDefaults());
        Setting::putValue('frontend_settings', SettingsService::frontendDefaults());

        User::query()->updateOrCreate(
            ['email' => 'admin@winningheaven.com'],
            [
                'name' => 'System Admin',
                'password' => 'admin123',
                'role' => 'admin',
                'roles' => ['admin', 'operation_admin', 'financial_admin', 'coins_admin', 'support_admin'],
                'coins' => 0,
                'referral_code' => 'ADMIN001',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'player@winningheaven.com'],
            [
                'name' => 'Demo Player',
                'password' => 'player123',
                'role' => 'user',
                'coins' => 100,
                'referral_code' => 'PLAY001',
            ]
        );

        // Replace old titles with original-art games
        Game::query()->delete();
        foreach ([
            ['title' => 'Nebula Jack', 'link' => 'https://example.com/nebula', 'image' => '/games/nebula.png', 'available_coins' => 50000],
            ['title' => 'Coin Vault', 'link' => 'https://example.com/vault', 'image' => '/games/vault.png', 'available_coins' => 50000],
            ['title' => 'Night Sweeps', 'link' => 'https://example.com/vegas', 'image' => '/games/vegas.png', 'available_coins' => 50000],
        ] as $g) {
            Game::query()->create([
                'public_id' => PublicId::make('game_'),
                'title' => $g['title'],
                'badge' => 'hot',
                'link' => $g['link'],
                'image' => $g['image'],
                'available_coins' => $g['available_coins'],
            ]);
        }

        foreach ([
            ['name' => 'Cash App', 'theme' => 'cashapp', 'tag' => '$WinningHeaven'],
            ['name' => 'Chime', 'theme' => 'chime', 'tag' => 'WinningHeaven'],
            ['name' => 'Venmo', 'theme' => 'venmo', 'tag' => '@WinningHeaven'],
        ] as $gw) {
            Gateway::query()->updateOrCreate(
                ['name' => $gw['name'], 'distributor_id' => null],
                [
                    'public_id' => PublicId::make('gw_'),
                    'theme' => $gw['theme'],
                    'tag' => $gw['tag'],
                    'is_withdraw_active' => true,
                ]
            );
        }

        Distributor::query()->updateOrCreate(
            ['email' => 'dist@winningheaven.com'],
            [
                'public_id' => PublicId::make('dist_'),
                'name' => 'Demo Distributor',
                'password' => 'dist123',
                'type' => 'A',
                'commission_rate' => 40,
                'website_commission_rate' => 0,
            ]
        );

        Agent::query()->updateOrCreate(
            ['email' => 'agent@winningheaven.com'],
            [
                'public_id' => PublicId::make('agent_'),
                'name' => 'Demo Agent',
                'password' => 'agent123',
                'agent_code' => 'AGT1001',
                'commission_rate' => 25,
            ]
        );
    }
}

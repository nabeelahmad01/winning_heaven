<?php

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    public static function globalDefaults(): array
    {
        return [
            'first_deposit_bonus' => 300,
            'regular_deposit_bonus' => 20,
            'referral_bonus' => 10,
            'usdt_address' => '',
            'usdt_qr_code' => '',
            'affiliate_payout_network' => 'TRC20',
            'affiliate_payout_wallet' => '',
            'affiliate_payout_qr_code' => '',
            'affiliate_payout_wallet_bep20' => '',
            'affiliate_payout_qr_bep20' => '',
            'affiliate_platform_commission_rate' => 90,
            'ad_payment_network' => 'BEP20',
            'ad_payment_wallet' => '',
            'ad_payment_qr_code' => '',
            'ad_budget_limit' => 6000,
            'freeplay_cashout_cap' => 30,
            'freeplay_min_request' => 100,
            'repeat_freeplay_deposit_threshold' => 25,
            'signup_freeplay' => 3,
        ];
    }

    public static function frontendDefaults(): array
    {
        return [
            'logo_url' => '/brand/logo.png',
            'login_bg_url' => '/brand/bg.png',
            'notification_sound_url' => 'https://raw.githubusercontent.com/AUTOMATIC1111/stable-diffusion-webui/master/notification.mp3',
            'withdraw_notice' => 'Fastest Withdrawals inside 5 Minutes!',
            'cashout_notice' => 'Standard cashout processing hours: 9 AM - 11 PM EST',
            'android_app_url' => '/downloads/winning-heaven.apk',
            'ios_app_url' => '',
            'get_app_enabled' => true,
            'chime_active' => true,
            'venmo_active' => true,
            'cashapp_active' => true,
            'first_deposit_bonus' => 300,
            'signup_freeplay' => 3,
            'minimum_deposit_limit' => 5,
            'minimum_withdrawal_limit' => 5,
            'withdraw_require_game_screenshot' => false,
            'withdraw_require_tag_qr_screenshot' => true,
            'landing_welcome' => 'WELCOME TO WINNING HEAVEN',
            'landing_grab' => 'Grab amazing bonuses and win big!',
            'landing_quick_signup' => 'Quick signup',
            'landing_signup_with_google' => 'Sign up with Google',
            'landing_login_with_google' => 'Continue with Google',
            'landing_or_create' => 'or create account with email',
            'landing_or_login' => 'or login with email',
            'landing_messenger_warning' => 'Google sign-in is not supported inside Messenger. Please open this page in Chrome or Safari.',
            'lobby_hero_promo' => 'GET 300% SIGNUP BONUS ON YOUR FIRST DEPOSIT',
            'lobby_trust_badge_1' => 'Instant Withdrawals',
            'lobby_trust_badge_2' => 'Secure & Safe',
            'lobby_trust_badge_3' => 'Trusted by 1B+ Players',
            'lobby_freeplay_value' => '$3',
            'lobby_freeplay_label' => 'FREEPLAY',
            'lobby_freeplay_condition' => 'ON SIGNUP!',
            'lobby_freeplay_claim_btn' => 'CLAIM FREEPLAY NOW',
            'lobby_bullet_1_title' => 'PLAY',
            'lobby_bullet_1_desc' => 'Explore exciting games',
            'lobby_bullet_2_title' => 'WIN',
            'lobby_bullet_2_desc' => 'Win real rewards',
            'lobby_bullet_3_title' => 'CASH OUT',
            'lobby_bullet_3_desc' => 'Fast withdrawals',
            'lobby_hero_side_image' => '/brand/promo.png',
            'lobby_hero_side_image_alt' => 'Download mobile app and get $3 freeplay',
            'lobby_hero_side_enabled' => true,
            'marquee_payouts' => [
                ['name' => 'Elizabeth Audrey', 'amount' => '$208.00', 'time' => '1 hour ago', 'color' => 'av-purple', 'init' => 'EA'],
                ['name' => 'Jamie', 'amount' => '$30.00', 'time' => '1 hour ago', 'color' => 'av-blue', 'init' => 'JM'],
                ['name' => 'Angel', 'amount' => '$90.00', 'time' => '1 hour ago', 'color' => 'av-green', 'init' => 'AN'],
                ['name' => 'Ashley', 'amount' => '$45.00', 'time' => '1 hour ago', 'color' => 'av-orange', 'init' => 'AS'],
                ['name' => 'Ryan G.', 'amount' => '$420.00', 'time' => '2 hours ago', 'color' => 'av-red', 'init' => 'RG'],
                ['name' => 'Michael S.', 'amount' => '$150.00', 'time' => '2 hours ago', 'color' => 'av-purple', 'init' => 'MS'],
            ],
            'cashout_rules' => [
                ['title' => '1. Account Verification', 'description' => 'Before requesting your first cashout, your email must be verified. Go to customer support if you need assistance updating details.'],
                ['title' => '2. Playthrough Requirements', 'description' => 'Sign-up bonuses and deposit match values carry a standard 1x playthrough requirement before funds are eligible for withdrawal requests.'],
                ['title' => '3. Minimum & Maximum Cashouts', 'description' => 'The minimum cashout limit is $5. Daily maximum cashouts are capped at $5,000 for standard players. Support can raise limits for VIP accounts.'],
                ['title' => '4. Payout Duration', 'description' => 'Withdrawal requests are processed instantly or within 10-15 minutes on average via digital wallets.'],
            ],
            'lobby_cashout_trust_items' => [
                ['icon' => 'fa-shield-halved', 'title' => '100% SECURE', 'description' => 'Your data is always protected'],
                ['icon' => 'fa-circle-check', 'title' => 'FAIR PLAY', 'description' => 'Provably fair and transparent'],
                ['icon' => 'fa-bolt', 'title' => 'INSTANT WITHDRAWALS', 'description' => 'Get your winnings instantly'],
                ['icon' => 'fa-headset', 'title' => '24/7 SUPPORT', 'description' => 'Always here to help you'],
            ],
            'proof_screenshots' => [],
            'info_page_enabled' => true,
            'info_show_on_auth' => true,
            'info_show_on_lobby' => true,
            'info_tagline' => 'PLAY SMARTER. CASHOUT FASTER.',
            'info_lead' => 'Official channels for updates, community, and player support. Reach us anytime — we\'re here to help you win big.',
            'info_support_note' => 'For account help, deposits, or withdrawals, email support and our team will get back to you.',
            'info_instagram_enabled' => true,
            'info_instagram_label' => 'Instagram',
            'info_instagram_handle' => '@winningheaven',
            'info_instagram_url' => 'https://www.instagram.com/winningheaven',
            'info_telegram_enabled' => true,
            'info_telegram_label' => 'Telegram',
            'info_telegram_handle' => 't.me/WinningHeaven',
            'info_telegram_url' => 'https://t.me/WinningHeaven',
            'info_facebook_enabled' => true,
            'info_facebook_label' => 'Facebook',
            'info_facebook_handle' => 'Winning Heaven',
            'info_facebook_url' => 'https://www.facebook.com/winningheaven',
            'info_whatsapp_enabled' => true,
            'info_whatsapp_label' => 'WhatsApp',
            'info_whatsapp_handle' => '+1 000 000 0000',
            'info_whatsapp_url' => 'https://wa.me/',
            'info_email_enabled' => true,
            'info_email_label' => 'Email Support',
            'info_email_handle' => 'support@winningheaven.com',
            'info_email_url' => 'mailto:support@winningheaven.com',
        ];
    }

    public static function global(): array
    {
        return array_merge(self::globalDefaults(), Setting::getValue('global_settings', []) ?: []);
    }

    public static function frontend(): array
    {
        $merged = array_merge(self::frontendDefaults(), Setting::getValue('frontend_settings', []) ?: []);
        if (empty($merged['notification_sound_url'])) {
            $merged['notification_sound_url'] = self::frontendDefaults()['notification_sound_url'];
        }
        return $merged;
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 64)->default('user')->after('password');
            $table->json('roles')->nullable()->after('role');
            $table->decimal('coins', 12, 2)->default(100)->after('roles');
            $table->string('referral_code', 16)->nullable()->unique()->after('coins');
            $table->string('referred_by')->nullable()->index()->after('referral_code');
            $table->string('distributor_id', 32)->nullable()->index()->after('referred_by');
            $table->string('former_distributor_id', 32)->nullable()->after('distributor_id');
            $table->string('agent_code', 32)->nullable()->index()->after('former_distributor_id');
            $table->string('campaign')->default('organic')->after('agent_code');
            $table->boolean('is_subscribed')->default(false)->after('campaign');
            $table->string('status', 32)->nullable()->after('is_subscribed');
            $table->json('allowed_game_ids')->nullable()->after('status');
            $table->decimal('pending_deposit_bonus_percent', 8, 2)->nullable()->after('allowed_game_ids');
            $table->string('pending_bonus_promo_id')->nullable()->after('pending_deposit_bonus_percent');
            $table->string('pending_bonus_promo_title')->nullable()->after('pending_bonus_promo_id');
            $table->decimal('pending_bonus_freeplay', 12, 2)->nullable()->after('pending_bonus_promo_title');
            $table->boolean('session_revoked')->default(false)->after('pending_bonus_freeplay');
        });

        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->string('title');
            $table->string('badge')->default('none');
            $table->longText('image')->nullable();
            $table->string('link')->nullable();
            $table->string('open_panel_link')->nullable();
            $table->decimal('available_coins', 14, 2)->default(0);
            $table->decimal('used_coins', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('gateways', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->string('name');
            $table->string('subtitle')->nullable();
            $table->string('tag')->nullable();
            $table->string('phone')->nullable();
            $table->string('theme')->default('chime');
            $table->longText('qr_image')->nullable();
            $table->text('redirect_url')->nullable();
            $table->boolean('is_withdraw_active')->default(true);
            $table->boolean('require_name_on_tag')->default(false);
            $table->boolean('require_tag')->default(false);
            $table->boolean('require_phone_on_tag')->default(false);
            $table->boolean('require_email_on_tag')->default(false);
            $table->string('distributor_id', 32)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('distributors', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('distributor');
            $table->char('type', 1)->default('A'); // A | B
            $table->decimal('commission_rate', 8, 2)->default(0);
            $table->decimal('website_commission_rate', 8, 2)->default(0);
            $table->string('status', 32)->default('ACTIVE');
            $table->timestamps();
        });

        Schema::create('distributor_games', function (Blueprint $table) {
            $table->id();
            $table->string('distributor_id', 32)->index();
            $table->string('game_id');
            $table->string('title')->nullable();
            $table->decimal('available_coins', 14, 2)->default(0);
            $table->decimal('used_coins', 14, 2)->default(0);
            $table->string('open_panel_link')->nullable();
            $table->timestamps();
            $table->unique(['distributor_id', 'game_id']);
        });

        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('agent_code')->unique();
            $table->string('account_type')->default('agent'); // agent | sub-distributor
            $table->string('role')->default('Agent');
            $table->string('status')->default('ACTIVE');
            $table->decimal('commission_rate', 8, 2)->default(0);
            $table->string('parent_agent_code')->nullable()->index();
            $table->string('auth_provider')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->string('user_email')->index();
            $table->string('type', 40)->index();
            $table->string('status', 32)->index();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('gateway')->nullable();
            $table->string('code')->nullable();
            $table->string('game_title')->nullable();
            $table->string('game_username')->nullable();
            $table->text('note')->nullable();
            $table->longText('screenshot')->nullable();
            $table->longText('tag_qr_screenshot')->nullable();
            $table->longText('payout_proof')->nullable();
            $table->boolean('has_screenshot')->default(false);
            $table->boolean('proof_pending')->default(false);
            $table->string('name_on_tag')->nullable();
            $table->string('phone_on_tag')->nullable();
            $table->string('email_on_tag')->nullable();
            $table->longText('payout_qr')->nullable();
            $table->decimal('payout_sent', 14, 2)->nullable();
            $table->decimal('payout_hold', 14, 2)->nullable();
            $table->decimal('payout_amount', 14, 2)->nullable();
            $table->boolean('remainder_paid')->default(false);
            $table->boolean('remainder_requested')->default(false);
            $table->string('remainder_status')->nullable();
            $table->timestamp('remainder_claim_available_at')->nullable();
            $table->unsignedInteger('remainder_wait_hours')->default(0);
            $table->unsignedInteger('remainder_wait_minutes')->default(0);
            $table->string('parent_tx_id')->nullable()->index();
            $table->boolean('is_freeplay_withdraw')->default(false);
            $table->decimal('game_amount', 14, 2)->nullable();
            $table->string('approved_by')->nullable();
            $table->string('allotted_by')->nullable();
            $table->string('processed_by')->nullable();
            $table->timestamp('coins_allotted_at')->nullable();
            $table->text('coins_hold_note')->nullable();
            $table->timestamp('coins_hold_at')->nullable();
            $table->string('distributor_id', 32)->nullable()->index();
            $table->char('distributor_type', 1)->nullable();
            $table->string('distributor_name')->nullable();
            $table->timestamps();
            $table->index(['user_email', 'type', 'status']);
            $table->index(['distributor_id', 'status', 'type']);
        });

        Schema::create('account_requests', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->string('user_email')->index();
            $table->string('game_title');
            $table->string('status')->default('PENDING')->index();
            $table->string('game_account_username')->nullable();
            $table->string('game_account_password')->nullable();
            $table->string('processed_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('referral_reward_id')->nullable();
            $table->string('distributor_id', 32)->nullable()->index();
            $table->char('distributor_type', 1)->nullable();
            $table->string('distributor_name')->nullable();
            $table->timestamps();
        });

        Schema::create('game_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('user_email')->index();
            $table->string('game_title');
            $table->string('username');
            $table->string('password');
            $table->string('status')->default('READY');
            $table->timestamps();
            $table->unique(['user_email', 'game_title']);
        });

        Schema::create('coins_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->string('user_email')->index();
            $table->string('game_title');
            $table->string('game_username')->nullable();
            $table->decimal('deposit_amount', 14, 2)->default(0);
            $table->decimal('bonus_applied', 8, 2)->default(0);
            $table->decimal('total_coins', 14, 2)->default(0);
            $table->string('status')->default('PENDING')->index();
            $table->boolean('read')->default(false);
            $table->text('hold_note')->nullable();
            $table->string('processed_by')->nullable();
            $table->string('transaction_id')->nullable()->unique();
            $table->boolean('is_freeplay')->default(false);
            $table->boolean('is_freeplay_withdraw')->default(false);
            $table->string('distributor_id', 32)->nullable()->index();
            $table->char('distributor_type', 1)->nullable();
            $table->timestamp('notified_at')->useCurrent();
            $table->timestamps();
            $table->index(['status', 'notified_at']);
        });

        Schema::create('pending_referrals', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->string('referrer_email')->index();
            $table->string('referee_email')->index();
            $table->decimal('reward_coins', 14, 2)->default(0);
            $table->string('status')->default('PENDING');
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('target_group')->default('all');
            $table->longText('image')->nullable();
            $table->string('promo_type')->default('message');
            $table->decimal('freeplay_amount', 12, 2)->default(0);
            $table->decimal('bonus_percent', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->string('user_email')->index();
            $table->string('user_name')->nullable();
            $table->text('message');
            $table->longText('attachment')->nullable();
            $table->boolean('has_attachment')->default(false);
            $table->string('sender_type')->default('player');
            $table->string('sender_email')->nullable();
            $table->boolean('read')->default(false);
            $table->string('distributor_id', 32)->nullable()->index();
            $table->char('distributor_type', 1)->nullable();
            $table->string('distributor_name')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_requests', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->string('agent_email')->index();
            $table->string('agent_code')->nullable();
            $table->decimal('budget', 14, 2)->default(0);
            $table->string('campaign_name');
            $table->text('facebook_page_link')->nullable();
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->longText('payment_proof')->nullable();
            $table->boolean('has_payment_proof')->default(false);
            $table->string('status')->default('PENDING')->index();
            $table->text('tracking_link')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->text('endpoint');
            $table->string('user_email')->index();
            $table->string('audience')->default('player');
            $table->string('distributor_id', 32)->nullable();
            $table->string('type')->default('web');
            $table->string('platform')->default('web');
            $table->json('subscription')->nullable();
            $table->text('native_token')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('client_kind', 40)->nullable();
            $table->boolean('standalone')->default(false);
            $table->timestamps();
        });

        Schema::create('shift_reports', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->string('staff_email')->index();
            $table->string('shift_name')->nullable();
            $table->timestamp('shift_date')->nullable();
            $table->decimal('total_loaded', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('deleted_users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->json('snapshot')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->string('deleted_by')->nullable();
            $table->string('deleted_entity_type')->nullable();
            $table->boolean('wipe_game_access')->default(false);
            $table->json('restore_game_titles')->nullable();
            $table->json('linked_player_emails')->nullable();
            $table->string('former_distributor_id', 32)->nullable();
            $table->timestamps();
        });

        Schema::create('oauth_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('sid')->nullable()->unique();
            $table->string('ticket')->nullable()->unique();
            $table->string('status')->default('pending');
            $table->json('user_payload')->nullable();
            $table->boolean('is_new_user')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_tickets');
        Schema::dropIfExists('deleted_users');
        Schema::dropIfExists('shift_reports');
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('campaign_requests');
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('pending_referrals');
        Schema::dropIfExists('coins_notifications');
        Schema::dropIfExists('game_accounts');
        Schema::dropIfExists('account_requests');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('agents');
        Schema::dropIfExists('distributor_games');
        Schema::dropIfExists('distributors');
        Schema::dropIfExists('gateways');
        Schema::dropIfExists('games');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role', 'roles', 'coins', 'referral_code', 'referred_by', 'distributor_id',
                'former_distributor_id', 'agent_code', 'campaign', 'is_subscribed', 'status',
                'allowed_game_ids', 'pending_deposit_bonus_percent', 'pending_bonus_promo_id',
                'pending_bonus_promo_title', 'pending_bonus_freeplay', 'session_revoked',
            ]);
        });
    }
};

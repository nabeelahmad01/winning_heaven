<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AccountRequest;
use App\Models\Agent;
use App\Models\CampaignRequest;
use App\Models\CoinsNotification;
use App\Models\DeletedUser;
use App\Models\Distributor;
use App\Models\Game;
use App\Models\GameAccount;
use App\Models\Gateway;
use App\Models\PendingReferral;
use App\Models\Promotion;
use App\Models\ShiftReport;
use App\Models\SupportMessage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FreeplayService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalController extends Controller
{
    public function home()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isStaff() || $user->hasRole('admin')) {
                return redirect()->route('admin');
            }
            return redirect()->route('lobby');
        }
        return redirect()->route('login');
    }

    public function login()
    {
        return view('auth.login', ['frontend' => SettingsService::frontend(), 'portal' => 'player']);
    }

    public function register()
    {
        return redirect('/login?tab=register');
    }

    public function adminLogin()
    {
        if (Auth::check() && (Auth::user()->isStaff() || Auth::user()->hasRole('admin'))) {
            return redirect()->route('admin');
        }
        return view('auth.staff-login', [
            'frontend' => SettingsService::frontend(),
            'title' => 'HQ Staff Login',
            'subtitle' => 'Admin · Finance · Coins · Operations · Support',
            'portal' => 'admin',
        ]);
    }

    public function lobby(Request $request)
    {
        $user = $request->user();
        $email = $user->email;

        $games = Game::query()->orderBy('title')->get();
        $transactions = Transaction::query()->where('user_email', $email)->latest()->limit(40)->get();
        $accountRequests = AccountRequest::query()->where('user_email', $email)->latest()->limit(30)->get();
        $gameAccounts = GameAccount::query()->where('user_email', $email)->get();

        $gateways = Gateway::query()->where(function ($q) {
            $q->whereNull('distributor_id')->orWhere('distributor_id', '');
        })->get();

        return view('lobby.index', [
            'user' => $user,
            'frontend' => SettingsService::frontend(),
            'games' => $games,
            'gateways' => $gateways,
            // jsGateways: lean payload for multi-step deposit/withdraw UI
            'jsGateways' => $gateways->map(fn ($g) => [
                'name' => $g->name,
                'subtitle' => $g->subtitle,
                'tag' => $g->tag,
                'phone' => $g->phone,
                'theme' => $g->theme,
                'qr_image' => $g->qr_image,
                'redirect_url' => $g->redirect_url,
                'is_withdraw_active' => (bool) $g->is_withdraw_active,
                'require_name_on_tag' => (bool) $g->require_name_on_tag,
                'require_tag' => (bool) $g->require_tag,
                'require_phone_on_tag' => (bool) ($g->require_phone_on_tag ?? true),
                'require_email_on_tag' => (bool) ($g->require_email_on_tag ?? false),
            ])->values(),
            'jsGames' => $games->map(fn ($g) => [
                'title' => $g->title,
                'image' => $g->image,
                'link' => $g->link,
                'badge' => $g->badge ?? 'none',
            ])->values(),
            'transactions' => $transactions,
            'accountRequests' => $accountRequests,
            'gameAccounts' => $gameAccounts->keyBy('game_title'),
            'jsGameAccounts' => $gameAccounts->map(fn ($a) => [
                'game_title' => $a->game_title,
                'username' => $a->username,
                'password' => $a->password,
            ])->values(),
            'jsAccountReqs' => $accountRequests->map(fn ($r) => [
                'game_title' => $r->game_title,
                'status' => $r->status,
            ])->values(),
            'jsTransactions' => $transactions->map(fn ($t) => [
                'type' => $t->type,
                'amount' => $t->amount,
                'status' => $t->status,
                'code' => $t->code,
                'gateway' => $t->gateway,
                'game_title' => $t->game_title,
                'is_freeplay_withdraw' => (bool) $t->is_freeplay_withdraw,
                'payout_hold' => (float) ($t->payout_hold ?? 0),
                'remainder_claim_available_at' => (string) ($t->remainder_claim_available_at ?? ''),
                'remainder_requested' => (bool) $t->remainder_requested,
                'remainder_status' => $t->remainder_status,
                'public_id' => $t->public_id,
                'created_at' => (string) $t->created_at,
            ])->values(),
            'freeplayGate' => FreeplayService::gate($email),
            'isFreeplaySession' => FreeplayService::isFreeplaySession($email),
            'freeplayMinRequest' => FreeplayService::minRequest(),
            'freeplayCashoutCap' => FreeplayService::cashoutCap(),
        ]);
    }

    public function referrals(Request $request)
    {
        $user = $request->user();
        $pending = PendingReferral::query()->where('referrer_email', $user->email)->latest()->get();
        $referred = User::query()->where('referred_by', $user->email)->latest()->limit(50)->get();
        return view('lobby.referrals', [
            'user' => $user,
            'frontend' => SettingsService::frontend(),
            'pending' => $pending,
            'referred' => $referred,
            'games' => Game::query()->orderBy('title')->get(),
            'referralLink' => url('/login?tab=register&ref=' . $user->referral_code),
        ]);
    }

    public function info()
    {
        return view('lobby.info', ['frontend' => SettingsService::frontend()]);
    }

    public function admin(Request $request, ?string $tab = null)
    {
        $user = $request->user();
        if (!$user->isStaff() && !$user->hasRole('admin')) {
            return redirect('/admin/login');
        }

        $path = '/' . ltrim($request->path(), '/');
        $portalBase = 'admin';
        $forcedRole = null;
        $portalName = 'HQ Desk';
        if (str_starts_with($path, 'finance') || str_starts_with($path, '/finance')) {
            $portalBase = 'finance';
            $forcedRole = 'financial_admin';
            $portalName = 'Financial Manager Portal';
        } elseif (str_starts_with($path, 'operations') || str_starts_with($path, '/operations')) {
            $portalBase = 'operations';
            $forcedRole = 'operation_admin';
            $portalName = 'Operation Manager Panel';
        } elseif (str_starts_with($path, 'coins-staff') || str_starts_with($path, '/coins-staff')) {
            $portalBase = 'coins-staff';
            $forcedRole = 'coins_admin';
            $portalName = 'Coins Admin Portal';
        } elseif (str_starts_with($path, 'support-staff') || str_starts_with($path, '/support-staff')) {
            $portalBase = 'support-staff';
            $forcedRole = 'support_admin';
            $portalName = 'Support Agent Portal';
        } elseif (str_starts_with($path, 'boss') || str_starts_with($path, '/boss')) {
            $portalBase = 'boss';
            $forcedRole = 'admin';
            $portalName = 'Super Boss Panel';
        }

        // Enforce forcedRole for staff portals (Jackpot parity)
        if ($forcedRole) {
            $roles = array_values(array_unique(array_filter(array_merge(
                is_array($user->roles ?? null) ? $user->roles : [],
                $user->role
                    ? (str_contains((string) $user->role, ',')
                        ? array_map('trim', explode(',', (string) $user->role))
                        : [(string) $user->role])
                    : []
            ))));
            $rolesLower = array_map('strtolower', $roles);
            if (!in_array(strtolower($forcedRole), $rolesLower, true) && !in_array('admin', $rolesLower, true)) {
                return redirect('/admin/login')->with('error', 'Access denied for this portal.');
            }
        }

        // Finance ledger: PENDING deposits + PENDING withdraws only (after coins)
        $pendingDeposits = Transaction::query()
            ->where('type', 'DEPOSIT')->where('status', 'PENDING')
            ->where(function ($q) {
                $q->whereNull('distributor_type')->orWhere('distributor_type', '!=', 'B');
            })
            ->latest()->limit(80)->get();
        $pendingWithdraws = Transaction::query()
            ->where('type', 'WITHDRAW')->where('status', 'PENDING')
            ->where(function ($q) {
                $q->whereNull('distributor_type')->orWhere('distributor_type', '!=', 'B');
            })
            ->latest()->limit(80)->get();

        return view('admin.index', [
            'user' => $user,
            'frontend' => SettingsService::frontend(),
            'global' => SettingsService::global(),
            'portalBase' => $portalBase,
            'forcedRole' => $forcedRole,
            'portalName' => $portalName,
            'initialTab' => $tab ?: null,
            'pendingDeposits' => $pendingDeposits,
            'pendingWithdraws' => $pendingWithdraws,
            'pendingCoins' => CoinsNotification::query()->where('status', 'PENDING')->latest('notified_at')->limit(80)->get(),
            'pendingRequests' => AccountRequest::query()->where('status', 'PENDING')->latest()->limit(80)->get(),
            'coinsLoading' => Transaction::query()->where('type', 'DEPOSIT')->where('status', 'COINS_LOADING')->latest()->limit(80)->get(),
            'allGames' => Game::query()->orderBy('title')->get(),
            'allGateways' => Gateway::query()->latest()->get(),
            'players' => User::query()->where('role', 'user')->latest()->limit(100)->get(),
            'staff' => User::query()->where(function ($q) {
                $q->where('role', '!=', 'user')->orWhereNotNull('roles');
            })->latest()->limit(80)->get(),
            'distributors' => Distributor::query()->latest()->get(),
            'agents' => Agent::query()->latest()->get(),
            'support' => SupportMessage::query()->latest()->limit(100)->get(),
            'campaigns' => CampaignRequest::query()->latest()->limit(50)->get(),
            'ledger' => Transaction::query()->latest()->limit(100)->get(),
            'promotions' => Promotion::query()->latest()->limit(100)->get(),
            'shiftReports' => ShiftReport::query()->latest()->limit(50)->get(),
            'deletedPlayers' => DeletedUser::query()->latest('deleted_at')->limit(100)->get(),
            'jsSupportThreads' => SupportMessage::query()->latest()->limit(200)->get()
                ->groupBy('user_email')
                ->map(function ($msgs, $email) {
                    return [
                        'email' => $email,
                        // Bodies + images loaded live via GET /api/support?email= (avoids huge SSR page)
                        'messages' => $msgs->sortBy('created_at')->values()->map(fn ($m) => [
                            'public_id' => $m->public_id,
                            'sender_type' => $m->sender_type,
                            'message' => $m->message,
                            'has_attachment' => (bool) ($m->has_attachment || $m->attachment),
                            'created_at' => (string) $m->created_at,
                        ])->all(),
                    ];
                })->values()->all(),
        ]);
    }

    public function distributor(?string $tab = null)
    {
        return view('distributor.index', [
            'frontend' => SettingsService::frontend(),
            'initialTab' => $tab ?: null,
        ]);
    }

    public function affiliate(?string $tab = null)
    {
        $teamCreate = request()->routeIs('affiliate.team.create');
        return view('affiliate.index', [
            'frontend' => SettingsService::frontend(),
            'initialTab' => $teamCreate ? 'team' : ($tab ?: null),
            'teamCreate' => $teamCreate,
        ]);
    }
}

@extends('layouts.app')
@section('title', ($portalName ?? 'HQ') . ' — Winning Heaven')
@section('content')
@php
  $logo = $frontend['logo_url'] ?? '/brand/logo.png';
  $portalBase = $portalBase ?? 'admin';
  $forcedRole = $forcedRole ?? null;
  $portalName = $portalName ?? 'HQ Desk';
  $roleList = array_values(array_unique(array_filter(array_merge(
    is_array($user->roles ?? null) ? $user->roles : [],
    $user->role
      ? (str_contains((string) $user->role, ',')
          ? array_map('trim', explode(',', (string) $user->role))
          : [(string) $user->role])
      : []
  ))));
  // When staff portal forces a role, restrict to that role's tabs (admin still full on /boss|/admin)
  if ($forcedRole && $forcedRole !== 'admin') {
    $roleList = [$forcedRole];
  }
  $isSuperAdmin = in_array('admin', array_map('strtolower', $roleList), true) && !$forcedRole;
  $isFullAdmin = $isSuperAdmin || in_array('admin', array_map('strtolower', $roleList), true) || ($forcedRole === 'admin');
  $isOpAdmin = in_array('operation_admin', array_map('strtolower', $roleList), true);
  // Jackpot-aligned URL tab keys (used in /admin/{tab} and data-pane)
  $paneAccess = [
    'financial_admin' => ['dashboard', 'ledger', 'gateways', 'tx_search', 'website_payments'],
    'coins_admin' => ['dashboard', 'shift_dashboard', 'coins', 'requests', 'tx_search'],
    'support_admin' => ['dashboard', 'support'],
    'operation_admin' => null, // almost all except staff/settings/frontend — handled below
  ];
  $superOnly = ['affiliate_commissions', 'website_payments', 'campaign_requests', 'deleted_accounts', 'frontend_settings', 'push'];
  $canPane = function (string $pane) use ($isFullAdmin, $isOpAdmin, $roleList, $paneAccess, $superOnly, $forcedRole): bool {
    $roles = array_map('strtolower', $roleList);
    if (in_array($pane, $superOnly, true)) {
      return in_array('admin', $roles, true) && (!$forcedRole || $forcedRole === 'admin');
    }
    if (in_array('admin', $roles, true)) return true;
    if ($isOpAdmin) {
      return !in_array($pane, ['staff', 'settings', 'frontend_settings', 'push'], true);
    }
    foreach ($roles as $r) {
      if (isset($paneAccess[$r]) && is_array($paneAccess[$r]) && in_array($pane, $paneAccess[$r], true)) return true;
    }
    return false;
  };
  // Nav labels — Jackpot structure: single Ledger (no separate deposits/withdraws)
  $nav = [
    'dashboard' => 'Dashboard',
    'shift_dashboard' => 'Shift Dashboard',
    'promotions' => 'Promotions',
    'games' => 'Games',
    'users' => 'Players',
    'requests' => 'Account requests',
    'ledger' => 'Ledger',
    'gateways' => 'Gateways',
    'tx_search' => 'TX Search',
    'coins' => 'Coins queue',
    'shift_reports' => 'Shift reports',
    'support' => 'Support',
    'staff' => 'Staff',
    'distributors' => 'Distributors',
    'affiliates' => 'Affiliates',
    'affiliate_commissions' => 'Affiliate cashouts',
    'website_payments' => 'Website payments',
    'campaign_requests' => 'Campaigns',
    'deleted_accounts' => 'Deleted players',
    'settings' => 'Settings',
    'frontend_settings' => 'Frontend CMS',
    'push' => 'Push',
  ];
  // URL tab → first allowed pane
  $urlTab = $initialTab ?? null;
  $firstPane = null;
  if ($urlTab && isset($nav[$urlTab]) && $canPane($urlTab)) {
    $firstPane = $urlTab;
  }
  if (!$firstPane) {
    foreach (array_keys($nav) as $key) {
      if ($canPane($key)) { $firstPane = $key; break; }
    }
  }
  $supportThreads = collect($support)->groupBy('user_email');
  $promotions = $promotions ?? collect();
  $shiftReports = $shiftReports ?? collect();
  $deletedPlayers = $deletedPlayers ?? collect();
  $coinsLoading = $coinsLoading ?? collect();
  $staff = $staff ?? collect();
  // Finance pending only (Jackpot Ledger)
  $ledgerDeposits = collect($pendingDeposits ?? []);
  $ledgerWithdraws = collect($pendingWithdraws ?? [])->filter(fn ($t) => strtoupper((string) $t->status) === 'PENDING');
@endphp
<div class="wh-admin">
  <aside class="wh-aside">
    <div class="brand">
      <img src="{{ asset(ltrim($logo,'/')) }}" alt="">
      <div>
        <strong style="display:block">Winning Heaven</strong>
        <span style="color:var(--mute);font-size:.75rem">{{ $portalName }}</span>
      </div>
    </div>
    <nav class="wh-aside-nav" id="adminAsideNav">
    @foreach($nav as $pane => $label)
      @if($canPane($pane))
        <button type="button" class="{{ $firstPane === $pane ? 'is-on' : '' }}" data-pane="{{ $pane }}">
          <span>{{ $label }}</span>
          @if(in_array($pane, ['ledger','coins','requests','support','campaign_requests','affiliate_commissions','website_payments'], true))
            <span class="wh-nav-badge {{ $pane === 'support' ? 'is-purple' : ($pane === 'coins' ? 'is-mint' : '') }}" data-badge="{{ $pane }}" hidden>0</span>
          @endif
        </button>
      @endif
    @endforeach
    </nav>
    <div class="wh-aside-foot">
      <div style="padding:.35rem .35rem;color:var(--mute);font-size:.75rem">
        {{ $user->email }} · {{ implode(', ', $roleList) ?: ($user->role ?: 'staff') }}
      </div>
      <a class="nav" href="/download-admin-app?v=2.0" download id="downloadPortalApp" style="font-size:.82rem">
        <i class="fa-solid fa-download"></i> Download WH Portal App (4.5 MB)
      </a>
      <button type="button" id="logoutBtn">Logout</button>
    </div>
  </aside>

  <div class="wh-admin-main">
    @if($canPane('dashboard'))
    @php
      $todayStart = now()->startOfDay();
      $todayDeps = collect($ledger ?? [])->filter(function ($t) use ($todayStart) {
        return strtoupper((string) $t->type) === 'DEPOSIT'
          && in_array(strtoupper((string) $t->status), ['SUCCESS', 'COINS_LOADING', 'PENDING'], true)
          && $t->created_at && $t->created_at->gte($todayStart);
      });
      $todayWds = collect($ledger ?? [])->filter(function ($t) use ($todayStart) {
        return strtoupper((string) $t->type) === 'WITHDRAW'
          && in_array(strtoupper((string) $t->status), ['SUCCESS', 'PENDING', 'PENDING_COINS', 'PAID'], true)
          && $t->created_at && $t->created_at->gte($todayStart);
      });
      $todayDepSum = $todayDeps->sum(fn ($t) => (float) $t->amount);
      $todayWdSum = $todayWds->sum(fn ($t) => (float) ($t->payout_amount ?? $t->amount));
    @endphp
    <div class="wh-pane {{ $firstPane === 'dashboard' ? 'is-on' : '' }}" id="pane-dashboard">
      <h1 style="font-family:var(--font-display);margin:0 0 .85rem">Operations desk</h1>
      <div class="wh-stats">
        <div class="wh-tile">
          <div style="color:var(--mute);font-size:.8rem">Pending deposits</div>
          <div style="font-size:1.8rem;font-weight:800" id="sDep">{{ $pendingDeposits->count() }}</div>
        </div>
        <div class="wh-tile">
          <div style="color:var(--mute);font-size:.8rem">Pending withdraws</div>
          <div style="font-size:1.8rem;font-weight:800" id="sWd">{{ $pendingWithdraws->count() }}</div>
        </div>
        <div class="wh-tile">
          <div style="color:var(--mute);font-size:.8rem">Coins queue</div>
          <div style="font-size:1.8rem;font-weight:800" id="sCoins">{{ $pendingCoins->count() }}</div>
        </div>
        <div class="wh-tile">
          <div style="color:var(--mute);font-size:.8rem">Account requests</div>
          <div style="font-size:1.8rem;font-weight:800" id="sReq">{{ $pendingRequests->count() }}</div>
        </div>
      </div>
      <div class="wh-stats" style="grid-template-columns:repeat(4,1fr)">
        <div class="wh-tile">
          <div style="color:var(--mute);font-size:.8rem">Today deposits $</div>
          <div style="font-size:1.35rem;font-weight:800;color:var(--mint)">${{ number_format($todayDepSum, 2) }}</div>
          <div style="color:var(--mute);font-size:.72rem">{{ $todayDeps->count() }} txs</div>
        </div>
        <div class="wh-tile">
          <div style="color:var(--mute);font-size:.8rem">Today withdraws $</div>
          <div style="font-size:1.35rem;font-weight:800;color:var(--sand)">${{ number_format($todayWdSum, 2) }}</div>
          <div style="color:var(--mute);font-size:.72rem">{{ $todayWds->count() }} txs</div>
        </div>
        <div class="wh-tile">
          <div style="color:var(--mute);font-size:.8rem">Unread support</div>
          <div style="font-size:1.4rem;font-weight:800" id="sSupport">—</div>
        </div>
        <div class="wh-tile">
          <div style="color:var(--mute);font-size:.8rem">Players</div>
          <div style="font-size:1.4rem;font-weight:800" id="sPlayers">—</div>
        </div>
      </div>

      <div class="wh-tile" style="margin-bottom:1rem">
        <div style="display:flex;justify-content:space-between;align-items:end;gap:1rem;flex-wrap:wrap;margin-bottom:.65rem">
          <h3 style="font-family:var(--font-display);margin:0;font-size:1.05rem">Game coins pool</h3>
          <span style="color:var(--mute);font-size:.78rem">Remaining = available − used</span>
        </div>
        <table class="wh-table">
          <thead>
            <tr><th>Game</th><th>Available</th><th>Used</th><th>Remaining</th><th></th></tr>
          </thead>
          <tbody>
            @forelse($allGames as $g)
            @php
              $avail = (float) ($g->available_coins ?? 0);
              $used = (float) ($g->used_coins ?? 0);
              $remain = $avail - $used;
            @endphp
            <tr>
              <td>{{ $g->title }}</td>
              <td>{{ number_format($avail, 0) }}</td>
              <td>{{ number_format($used, 0) }}</td>
              <td><strong style="color:{{ $remain < 500 ? 'var(--danger)' : 'var(--mint)' }}">{{ number_format($remain, 0) }}</strong></td>
              <td>
                <button type="button" class="wh-btn-sm" onclick="updatePoolFromDash('{{ $g->public_id }}', '{{ e($g->title) }}', {{ $avail }})">Update Pool</button>
              </td>
            </tr>
            @empty
            <tr><td colspan="5" style="color:var(--mute)">No games</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="wh-tile" style="margin-bottom:1rem">
        <h3 style="font-family:var(--font-display);margin:0 0 .65rem;font-size:1.05rem">Date stats</h3>
        <div style="display:flex;gap:.55rem;align-items:end;flex-wrap:wrap;margin-bottom:.65rem">
          <div class="wh-field" style="margin:0">
            <label>Date</label>
            <div class="box"><input type="date" id="dashStatsDate" value="{{ now()->toDateString() }}"></div>
          </div>
          <button type="button" class="wh-cta" id="dashStatsLoad">Load</button>
        </div>
        <div class="wh-stats" style="grid-template-columns:1fr 1fr;margin:0">
          <div class="wh-tile" style="box-shadow:none;padding:.65rem">
            <div style="color:var(--mute);font-size:.8rem">Deposits</div>
            <div style="font-size:1.25rem;font-weight:800;color:var(--mint)" id="dashDateDeps">—</div>
          </div>
          <div class="wh-tile" style="box-shadow:none;padding:.65rem">
            <div style="color:var(--mute);font-size:.8rem">Withdrawals</div>
            <div style="font-size:1.25rem;font-weight:800;color:var(--sand)" id="dashDateWds">—</div>
          </div>
        </div>
      </div>

      <div class="wh-tile" style="margin-bottom:1rem">
        <h3 style="font-family:var(--font-display);margin:0 0 .65rem;font-size:1.05rem">Shift report</h3>
        <form id="shiftReportForm" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:.55rem;align-items:end">
          <div class="wh-field" style="margin:0"><label>Shift</label><div class="box"><input name="shift_name" placeholder="AM / PM / Night" required></div></div>
          <div class="wh-field" style="margin:0"><label>Notes</label><div class="box"><input name="notes" placeholder="Highlights / issues"></div></div>
          <div class="wh-field" style="margin:0"><label>Total loaded</label><div class="box"><input name="total_loaded" type="number" step="0.01" placeholder="0.00"></div></div>
          <button class="wh-cta" type="submit">Save</button>
        </form>
        <p style="color:var(--mute);font-size:.75rem;margin:.5rem 0 0">Posts to <code>POST /admin/shift-reports</code>.</p>
      </div>

      <p style="color:var(--mute)">Deposit approve → coins task → coins load · withdraw → coins deduct → finance pay. Stats refresh every 2s.</p>
    </div>
    @endif

    @if($canPane('shift_dashboard'))
    <div class="wh-pane {{ $firstPane === 'shift_dashboard' ? 'is-on' : '' }}" id="pane-shift_dashboard">
      <h2 style="font-family:var(--font-display)">Shift Dashboard</h2>
      <p style="color:var(--mute);margin-top:-.35rem">Pending account IDs, coins allotments, and deposits waiting on coins load.</p>

      <div class="wh-tile" style="margin-bottom:1rem">
        <h3 style="font-family:var(--font-display);margin:0 0 .65rem;font-size:1.05rem">Pending account requests</h3>
        <table class="wh-table">
          <thead><tr><th>User</th><th>Game</th><th></th></tr></thead>
          <tbody>
            @forelse($pendingRequests as $r)
            <tr>
              <td>{{ $r->user_email }}</td>
              <td>{{ $r->game_title }}</td>
              <td class="wh-btn-row">
                <button type="button" class="wh-btn-sm" onclick="issueAccount('{{ $r->public_id }}')">Issue ID</button>
                <button type="button" class="wh-btn-sm danger" onclick="rejectAccount('{{ $r->public_id }}')">Reject</button>
              </td>
            </tr>
            @empty
            <tr><td colspan="3" style="color:var(--mute)">No pending requests</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="wh-tile" style="margin-bottom:1rem">
        <h3 style="font-family:var(--font-display);margin:0 0 .65rem;font-size:1.05rem">Pending coins</h3>
        <table class="wh-table">
          <thead><tr><th>User</th><th>Game</th><th>Coins</th><th></th></tr></thead>
          <tbody>
            @forelse($pendingCoins as $c)
            <tr>
              <td>{{ $c->user_email }}</td>
              <td>{{ $c->game_title }}</td>
              <td><strong>{{ (int) $c->total_coins }}</strong></td>
              <td class="wh-btn-row">
                <button type="button" class="wh-btn-sm" onclick="completeCoins('{{ $c->public_id }}')">Complete</button>
                <button type="button" class="wh-btn-sm ghost" onclick="holdCoins('{{ $c->public_id }}')">Hold</button>
              </td>
            </tr>
            @empty
            <tr><td colspan="4" style="color:var(--mute)">Coins queue empty</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="wh-tile">
        <h3 style="font-family:var(--font-display);margin:0 0 .65rem;font-size:1.05rem">COINS_LOADING deposits</h3>
        <p style="color:var(--mute);font-size:.8rem;margin:0 0 .65rem">These deposits are approved and waiting for coins staff to finish loading.</p>
        <table class="wh-table">
          <thead><tr><th>User</th><th>Amount</th><th>Game</th><th>Code</th><th>When</th></tr></thead>
          <tbody>
            @forelse($coinsLoading as $tx)
            <tr>
              <td>{{ $tx->user_email }}</td>
              <td>${{ number_format((float) $tx->amount, 2) }}</td>
              <td>{{ $tx->game_title }}</td>
              <td>{{ $tx->code }}</td>
              <td style="font-size:.78rem">{{ $tx->created_at }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="color:var(--mute)">No COINS_LOADING deposits</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    @if($canPane('promotions'))
    <div class="wh-pane {{ $firstPane === 'promotions' ? 'is-on' : '' }}" id="pane-promotions">
      <h2 style="font-family:var(--font-display)">Promotions</h2>
      <p style="color:var(--mute);margin:.25rem 0 1rem;font-size:.85rem">
        Broadcast to players (Jackpot flow). Target: All / Subscribed / Unsubscribed / Active depositors.
        Super Admin + Operations can create. Emails send when SMTP is configured.
      </p>
      <div class="wh-tile" style="margin-bottom:1rem">
        <form id="promoForm" style="display:grid;gap:.65rem;grid-template-columns:1fr 1fr;align-items:end">
          <div class="wh-field" style="margin:0;grid-column:1/-1"><label>Title *</label><div class="box"><input name="title" required placeholder="Weekend reload bonus"></div></div>
          <div class="wh-field" style="margin:0;grid-column:1/-1"><label>Message</label><div class="box"><input name="message" placeholder="Shown in lobby + email body"></div></div>
          <div class="wh-field" style="margin:0">
            <label>Target group</label>
            <select name="target_group" style="width:100%;border:1px solid var(--line);background:rgba(0,0,0,.35);color:var(--ink);border-radius:12px;padding:.75rem .85rem">
              <option value="subscribed" selected>Subscribed only</option>
              <option value="all">All players</option>
              <option value="unsubscribed">Unsubscribed</option>
              <option value="active">Active (approved deposit)</option>
            </select>
          </div>
          <div class="wh-field" style="margin:0">
            <label>Type</label>
            <select name="promo_type" id="promoType" style="width:100%;border:1px solid var(--line);background:rgba(0,0,0,.35);color:var(--ink);border-radius:12px;padding:.75rem .85rem">
              <option value="message">message</option>
              <option value="freeplay">freeplay</option>
              <option value="deposit_bonus">deposit_bonus</option>
            </select>
          </div>
          <div class="wh-field" style="margin:0"><label>Freeplay amount</label><div class="box"><input name="freeplay_amount" type="number" step="0.01" value="0"></div></div>
          <div class="wh-field" style="margin:0"><label>Bonus percent</label><div class="box"><input name="bonus_percent" type="number" step="0.01" value="0"></div></div>
          <label class="wh-check" style="grid-column:1/-1"><input type="checkbox" name="send_email" value="1" checked> Send email blast to target players</label>
          <label class="wh-check" style="grid-column:1/-1"><input type="checkbox" name="send_push" value="1" checked> Count / include web-push subscribers (Push tab needs VAPID)</label>
          <button class="wh-cta" type="submit" style="grid-column:1/-1">Create &amp; Broadcast</button>
        </form>
      </div>
      <div class="wh-tile">
        <table class="wh-table">
          <thead><tr><th>Title</th><th>Target</th><th>Type</th><th>Freeplay</th><th>Bonus %</th><th></th></tr></thead>
          <tbody>
            @forelse($promotions as $promo)
            <tr>
              <td>{{ $promo->title }}<div style="color:var(--mute);font-size:.75rem">{{ \Illuminate\Support\Str::limit($promo->message ?? '', 60) }}</div></td>
              <td><span class="wh-badge">{{ $promo->target_group ?? 'all' }}</span></td>
              <td><span class="wh-badge">{{ $promo->promo_type }}</span></td>
              <td>{{ $promo->freeplay_amount }}</td>
              <td>{{ $promo->bonus_percent }}</td>
              <td><button type="button" class="wh-btn-sm danger" onclick="deletePromo('{{ $promo->public_id }}')">Delete</button></td>
            </tr>
            @empty
            <tr><td colspan="6" style="color:var(--mute)">No promotions</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    @if($canPane('coins'))
    <div class="wh-pane {{ $firstPane === 'coins' ? 'is-on' : '' }}" id="pane-coins">
      <h2 style="font-family:var(--font-display)">Coins allotment</h2>
      <div class="wh-tile">
        <table class="wh-table">
          <thead>
            <tr>
              <th>User</th><th>Game</th><th>Deposit</th><th>Bonus</th><th>Coins</th><th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($pendingCoins as $c)
            @php
              $bonus = (float) $c->bonus_applied;
              $isDeduct = abs($bonus - (-1)) < 0.001;
              $isFreeplay = abs($bonus - (-3)) < 0.001 || !empty($c->is_freeplay);
              $isFpWd = !empty($c->is_freeplay_withdraw);
            @endphp
            <tr>
              <td>{{ $c->user_email }}</td>
              <td>
                {{ $c->game_title }}
                @if($isFpWd)
                  <div><span class="wh-badge" style="background:rgba(255,77,109,.18);color:#ff6b7a">FREEPLAY WITHDRAW · MAX $30</span></div>
                @endif
              </td>
              <td>
                @if($isDeduct)
                  ${{ number_format((float) $c->deposit_amount, 2) }} <span style="color:#ff6b7a">(cashout)</span>
                @else
                  ${{ number_format((float) $c->deposit_amount, 2) }}
                @endif
              </td>
              <td>
                @if($isDeduct)
                  <span class="wh-badge" style="background:rgba(255,77,109,.18);color:#ff6b7a">DEDUCT (−1)</span>
                @elseif($isFreeplay)
                  <span class="wh-badge">FREEPLAY (−3)</span>
                @else
                  {{ $c->bonus_applied }}%
                @endif
              </td>
              <td>
                @if((float) $c->total_coins < 0)
                  <strong style="color:#ff6b7a">{{ (int) $c->total_coins }}</strong>
                @else
                  <strong>{{ (int) $c->total_coins }}</strong>
                @endif
              </td>
              <td class="wh-btn-row">
                <button type="button" class="wh-btn-sm" onclick="completeCoins('{{ $c->public_id }}')">Mark loaded</button>
                <button type="button" class="wh-btn-sm ghost" onclick="holdCoins('{{ $c->public_id }}')">Hold</button>
              </td>
            </tr>
            @empty
            <tr><td colspan="6" style="color:var(--mute)">Coins queue empty</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    @if($canPane('requests'))
    <div class="wh-pane {{ $firstPane === 'requests' ? 'is-on' : '' }}" id="pane-requests">
      <h2 style="font-family:var(--font-display)">Game account requests</h2>
      <p style="color:var(--mute);font-size:.85rem;margin-top:0">Issue credentials inline (Jackpot Shift/Requests style) — player sees Email · Username · Password in lobby.</p>
      <div class="wh-tile">
        <table class="wh-table">
          <thead>
            <tr><th>User</th><th>Game</th><th>Username</th><th>Password</th><th></th></tr>
          </thead>
          <tbody>
            @forelse($pendingRequests as $r)
            <tr data-tx-id="{{ $r->public_id }}">
              <td>{{ $r->user_email }}</td>
              <td>{{ $r->game_title }}</td>
              <td><input class="wh-inline-input" id="cred-user-{{ $r->public_id }}" placeholder="game username" autocomplete="off"></td>
              <td><input class="wh-inline-input" id="cred-pass-{{ $r->public_id }}" placeholder="game password" autocomplete="off"></td>
              <td class="wh-btn-row">
                <button type="button" class="wh-btn-sm" onclick="saveAccountCreds('{{ $r->public_id }}')">Save / Issue</button>
                <button type="button" class="wh-btn-sm danger" onclick="rejectAccount('{{ $r->public_id }}')">Reject</button>
              </td>
            </tr>
            @empty
            <tr><td colspan="5" style="color:var(--mute)">No pending requests</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    @if($canPane('games'))
    <div class="wh-pane {{ $firstPane === 'games' ? 'is-on' : '' }}" id="pane-games">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
        <div>
          <h2 style="font-family:var(--font-display);margin:0">Games library</h2>
          <p style="color:var(--mute);margin:.25rem 0 0;font-size:.85rem">Create button opens a modal (Jackpot AdminGameModal flow).</p>
        </div>
        <button type="button" class="wh-cta" id="openGameModalBtn"><i class="fa-solid fa-plus"></i> Create Game</button>
      </div>
      <div class="wh-tile">
        <table class="wh-table">
          <thead>
            <tr><th>Title</th><th>Badge</th><th>Pool</th><th>Used</th><th>Link</th><th></th></tr>
          </thead>
          <tbody>
            @forelse($allGames as $g)
            <tr>
              <td>{{ $g->title }}</td>
              <td><span class="wh-badge">{{ $g->badge ?? 'none' }}</span></td>
              <td>{{ $g->available_coins }}</td>
              <td>{{ $g->used_coins ?? 0 }}</td>
              <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis">{{ $g->link }}</td>
              <td class="wh-btn-row">
                <button type="button" class="wh-btn-sm" onclick="openEditGameModal({{ json_encode($g) }})">Edit Game</button>
                <button type="button" class="wh-btn-sm ghost" onclick="editGameCoins('{{ $g->public_id }}', '{{ e($g->available_coins) }}')">Edit pool</button>
                <button type="button" class="wh-btn-sm danger" onclick="deleteGame('{{ $g->public_id }}', '{{ e($g->title) }}')">Delete</button>
              </td>
            </tr>
            @empty
            <tr><td colspan="6" style="color:var(--mute)">No games</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    @if($canPane('gateways'))
    <div class="wh-pane {{ $firstPane === 'gateways' ? 'is-on' : '' }}" id="pane-gateways">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
        <div>
          <h2 style="font-family:var(--font-display);margin:0">Payment gateways</h2>
          <p style="color:var(--mute);margin:.25rem 0 0;font-size:.85rem">Create opens modal — theme colors, QR, withdraw flags (Jackpot AdminGatewayModal).</p>
        </div>
        <button type="button" class="wh-cta" id="openGwModalBtn"><i class="fa-solid fa-plus"></i> Create Gateway</button>
      </div>
      <div class="wh-tile">
        <table class="wh-table">
          <thead>
            <tr><th>Name</th><th>Theme</th><th>Tag</th><th>Withdraw</th><th></th></tr>
          </thead>
          <tbody>
            @forelse($allGateways as $gw)
            <tr>
              <td>
                <strong>{{ $gw->name }}</strong>
                @if($gw->subtitle)<div style="color:var(--mute);font-size:.75rem">{{ $gw->subtitle }}</div>@endif
              </td>
              <td><span class="wh-theme-chip" data-theme="{{ $gw->theme }}">{{ $gw->theme }}</span></td>
              <td>{{ $gw->tag }}</td>
              <td>{{ !empty($gw->is_withdraw_active) ? 'Yes' : 'No' }}</td>
              <td class="wh-btn-row">
                <button type="button" class="wh-btn-sm ghost" onclick="editGatewayById('{{ $gw->public_id }}')">Edit</button>
                <button type="button" class="wh-btn-sm danger" onclick="deleteGateway('{{ $gw->public_id }}')">Delete</button>
              </td>
            </tr>
            @empty
            <tr><td colspan="5" style="color:var(--mute)">No gateways</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    @if($canPane('users'))
    <div class="wh-pane {{ $firstPane === 'users' ? 'is-on' : '' }}" id="pane-users">
      <h2 style="font-family:var(--font-display)">Players</h2>
      <div class="wh-tile" style="margin-bottom:1rem">
        <form id="playerCreateForm" class="wh-bento" style="display:grid;gap:.55rem;grid-template-columns:1fr 1fr 1fr auto;align-items:end">
          <div class="wh-field" style="margin:0"><label>Name</label><div class="box"><input name="name" required></div></div>
          <div class="wh-field" style="margin:0"><label>Email</label><div class="box"><input name="email" type="email" required></div></div>
          <div class="wh-field" style="margin:0"><label>Password</label><div class="box"><input name="password" type="text" value="player123" required></div></div>
          <input type="hidden" name="role" value="user">
          <button class="wh-cta" type="submit">Create player</button>
        </form>
      </div>
      <div class="wh-tile" style="margin-bottom:1rem">
        <div class="wh-field" style="margin:0;max-width:360px">
          <label>Search</label>
          <div class="box"><input id="playerSearch" type="search" placeholder="Filter by email or name…"></div>
        </div>
      </div>
      <div class="wh-tile">
        <table class="wh-table" id="playersTable">
          <thead>
            <tr><th>Name</th><th>Email</th><th>Status</th><th>Ref</th><th>Dist</th><th>Agent</th><th></th></tr>
          </thead>
          <tbody>
            @forelse($players as $p)
            <tr data-search="{{ strtolower($p->name.' '.$p->email) }}">
              <td>{{ $p->name }}</td>
              <td>{{ $p->email }}</td>
              <td><span class="wh-badge">{{ $p->status ?? 'active' }}</span></td>
              <td>{{ $p->referral_code }}</td>
              <td>{{ $p->distributor_id }}</td>
              <td>{{ $p->agent_code }}</td>
              <td class="wh-btn-row">
                @if(strtolower((string) ($p->status ?? 'active')) === 'suspended')
                  <button type="button" class="wh-btn-sm" onclick="setPlayerStatus({{ (int) $p->id }}, 'active')">Reactivate</button>
                @else
                  <button type="button" class="wh-btn-sm danger" onclick="setPlayerStatus({{ (int) $p->id }}, 'suspended')">Suspend</button>
                @endif
                <button type="button" class="wh-btn-sm ghost" onclick="resetPlayerPassword({{ (int) $p->id }}, '{{ e($p->email) }}')">Reset pw</button>
                <button type="button" class="wh-btn-sm danger" onclick="deleteUser({{ (int) $p->id }}, '{{ e($p->email) }}')">Delete</button>
              </td>
            </tr>
            @empty
            <tr><td colspan="7" style="color:var(--mute)">No players</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    @if($canPane('ledger'))
    <div class="wh-pane {{ $firstPane === 'ledger' ? 'is-on' : '' }}" id="pane-ledger">
      <h2 style="font-family:var(--font-display);margin:0 0 .35rem">Financial Transaction Ledger</h2>
      <p style="color:var(--mute);margin:0 0 1rem;font-size:.85rem">Pending deposits and withdrawals (after coins). History lives in TX Search.</p>
      <div class="wh-tile" style="margin-bottom:1rem">
        <div class="wh-field" style="margin:0;max-width:420px">
          <label>Search queue</label>
          <div class="box"><input id="ledgerQueueSearch" type="search" placeholder="email / game / code…"></div>
        </div>
      </div>

      <div class="wh-tile" style="margin-bottom:1.25rem">
        <h3 style="font-family:var(--font-display);margin:0 0 .65rem;font-size:1.05rem;letter-spacing:.04em">DEPOSIT REQUESTS</h3>
        <table class="wh-table">
          <thead>
            <tr>
              <th>User</th><th>Amount</th><th>Gateway</th><th>Game</th><th>Code</th><th>Screenshot</th><th></th>
            </tr>
          </thead>
          <tbody id="ledgerDepBody">
            @forelse($ledgerDeposits as $tx)
            <tr data-tx-id="{{ $tx->public_id }}" data-search="{{ strtolower($tx->user_email.' '.$tx->game_title.' '.$tx->code.' '.$tx->gateway) }}">
              <td>{{ $tx->user_email }}</td>
              <td>${{ number_format((float) $tx->amount, 2) }}</td>
              <td>{{ $tx->gateway }}</td>
              <td>{{ $tx->game_title }}</td>
              <td>{{ $tx->code }}</td>
              <td>
                @php $shot = $tx->screenshot ?? ''; @endphp
                @if($shot || $tx->has_screenshot)
                  @if(is_string($shot) && (str_starts_with($shot, 'data:image') || preg_match('/\.(png|jpe?g|gif|webp)(\?|$)/i', $shot) || str_starts_with($shot, '/')))
                    <img class="wh-shot-thumb" src="{{ $shot }}" alt="proof" onclick="openShotLightbox(this.src)" title="View screenshot">
                  @elseif($shot)
                    <a href="{{ $shot }}" target="_blank" rel="noopener" style="color:var(--aqua)">View</a>
                  @else
                    <span style="color:var(--mute)">Proof on file</span>
                  @endif
                @else
                  <span style="color:var(--mute)">—</span>
                @endif
              </td>
              <td class="wh-btn-row">
                <button type="button" class="wh-btn-sm" onclick="approveDeposit('{{ $tx->public_id }}')">Approve</button>
                <button type="button" class="wh-btn-sm danger" onclick="rejectDeposit('{{ $tx->public_id }}')">Reject</button>
              </td>
            </tr>
            @empty
            <tr class="wh-empty-row"><td colspan="7" style="color:var(--mute)">No pending deposits</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="wh-tile">
        <h3 style="font-family:var(--font-display);margin:0 0 .65rem;font-size:1.05rem;letter-spacing:.04em">WITHDRAWAL REQUESTS</h3>
        <table class="wh-table">
          <thead>
            <tr>
              <th>User</th><th>Amount</th><th>Payout</th><th>Status</th><th>Game</th><th>Gateway / Tag</th><th></th>
            </tr>
          </thead>
          <tbody id="ledgerWdBody">
            @forelse($ledgerWithdraws as $tx)
            <tr data-tx-id="{{ $tx->public_id }}" data-search="{{ strtolower($tx->user_email.' '.$tx->game_title.' '.$tx->code.' '.$tx->gateway) }}">
              <td>
                {{ $tx->user_email }}
                @if($tx->is_freeplay_withdraw)
                  <span class="wh-badge" style="background:rgba(255,77,109,.18);color:#ff6b7a;margin-left:.25rem">FREEPLAY</span>
                @endif
              </td>
              <td>${{ number_format((float) $tx->amount, 2) }}</td>
              <td>
                @if($tx->payout_amount !== null)
                  ${{ number_format((float) $tx->payout_amount, 2) }}
                @else
                  <span style="color:var(--mute)">same</span>
                @endif
              </td>
              <td><span class="wh-badge">{{ $tx->status }}</span></td>
              <td>{{ $tx->game_title }}</td>
              <td>{{ $tx->gateway }} · {{ $tx->code }}</td>
              <td class="wh-btn-row">
                <button type="button" class="wh-btn-sm" onclick="markPaid('{{ $tx->public_id }}', {{ (float) ($tx->payout_amount ?? $tx->amount) }})">Mark paid</button>
                <button type="button" class="wh-btn-sm ghost" onclick="partialPayout('{{ $tx->public_id }}', {{ (float) ($tx->payout_amount ?? $tx->amount) }})">Partial</button>
                <button type="button" class="wh-btn-sm danger" onclick="failWithdraw('{{ $tx->public_id }}')">Fail</button>
              </td>
            </tr>
            @empty
            <tr class="wh-empty-row"><td colspan="7" style="color:var(--mute)">No pending withdrawals</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    @if($canPane('tx_search'))
    <div class="wh-pane {{ $firstPane === 'tx_search' ? 'is-on' : '' }}" id="pane-tx_search">
      <h2 style="font-family:var(--font-display)">Transaction search</h2>
      <div class="wh-tile" style="margin-bottom:1rem">
        <div class="wh-bento" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:.6rem;align-items:end">
          <div class="wh-field" style="margin:0">
            <label>Search</label>
            <div class="box"><input id="txSearchQ" type="search" placeholder="email / game / code / gateway…"></div>
          </div>
          <div class="wh-field" style="margin:0">
            <label>Type</label>
            <div class="box">
              <select id="txSearchType" style="flex:1;border:0;background:transparent;color:var(--ink);padding:.75rem 0;outline:none">
                <option value="">All types</option>
                <option value="DEPOSIT">DEPOSIT</option>
                <option value="WITHDRAW">WITHDRAW</option>
              </select>
            </div>
          </div>
          <div class="wh-field" style="margin:0">
            <label>Status</label>
            <div class="box">
              <select id="txSearchStatus" style="flex:1;border:0;background:transparent;color:var(--ink);padding:.75rem 0;outline:none">
                <option value="">All statuses</option>
                <option value="PENDING">PENDING</option>
                <option value="PENDING_COINS">PENDING_COINS</option>
                <option value="COINS_LOADING">COINS_LOADING</option>
                <option value="SUCCESS">SUCCESS</option>
                <option value="FAILED">FAILED</option>
              </select>
            </div>
          </div>
          <button type="button" class="wh-cta" id="txSearchFetch">Refresh API</button>
        </div>
        <p style="color:var(--mute);font-size:.75rem;margin:.5rem 0 0">Filters ledger rows client-side. Optional <code>GET /transactions</code> refresh.</p>
      </div>
      <div class="wh-tile">
        <table class="wh-table" id="txSearchTable">
          <thead>
            <tr><th>Type</th><th>User</th><th>Amount</th><th>Status</th><th>Game</th><th>Code</th><th>When</th></tr>
          </thead>
          <tbody>
            @forelse($ledger as $tx)
            <tr
              data-search="{{ strtolower($tx->user_email.' '.$tx->game_title.' '.$tx->code.' '.$tx->gateway) }}"
              data-type="{{ $tx->type }}"
              data-status="{{ $tx->status }}"
            >
              <td>{{ $tx->type }}</td>
              <td>{{ $tx->user_email }}</td>
              <td>${{ number_format((float) $tx->amount, 2) }}</td>
              <td><span class="wh-badge">{{ $tx->status }}</span></td>
              <td>{{ $tx->game_title }}</td>
              <td>{{ $tx->code }}</td>
              <td style="font-size:.78rem;white-space:nowrap">{{ $tx->created_at }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="color:var(--mute)">No transactions</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    @if($canPane('distributors'))
    <div class="wh-pane {{ $firstPane === 'distributors' ? 'is-on' : '' }}" id="pane-distributors">
      <h2 style="font-family:var(--font-display)">Distributors (A/B)</h2>
      <div class="wh-tile" style="margin-bottom:1rem">
        <form id="distForm" class="wh-bento" style="display:grid;gap:.5rem;grid-template-columns:repeat(5,1fr) auto;align-items:end">
          <div class="wh-field" style="margin:0"><label>Name</label><div class="box"><input name="name" required></div></div>
          <div class="wh-field" style="margin:0"><label>Email</label><div class="box"><input name="email" type="email" required></div></div>
          <div class="wh-field" style="margin:0">
            <label>Type</label>
            <div class="box">
              <select name="type" style="flex:1;border:0;background:transparent;color:var(--ink);padding:.75rem 0;outline:none">
                <option value="A">Type A</option>
                <option value="B">Type B</option>
              </select>
            </div>
          </div>
          <div class="wh-field" style="margin:0"><label>Commission %</label><div class="box"><input name="commission_rate" type="number" step="0.01" value="40"></div></div>
          <div class="wh-field" style="margin:0"><label>Website rate %</label><div class="box"><input name="website_commission_rate" type="number" step="0.01" value="0"></div></div>
          <input type="hidden" name="password" value="dist123">
          <button class="wh-cta" type="submit">Create</button>
        </form>
      </div>
      <div class="wh-tile">
        <table class="wh-table">
          <thead>
            <tr><th>Name</th><th>Email</th><th>Type</th><th>Rate</th><th>Website</th><th></th></tr>
          </thead>
          <tbody>
            @forelse($distributors as $d)
            <tr>
              <td>{{ $d->name }}</td>
              <td>{{ $d->email }}</td>
              <td><span class="wh-badge">{{ $d->type }}</span></td>
              <td>{{ $d->commission_rate }}%</td>
              <td>{{ $d->website_commission_rate }}%</td>
              <td><button type="button" class="wh-btn-sm danger" onclick="deleteDistributor('{{ $d->public_id }}', '{{ e($d->name) }}')">Delete</button></td>
            </tr>
            @empty
            <tr><td colspan="6" style="color:var(--mute)">No distributors</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    @if($canPane('affiliates'))
    <div class="wh-pane {{ $firstPane === 'affiliates' ? 'is-on' : '' }}" id="pane-affiliates">
      <h2 style="font-family:var(--font-display)">Affiliates / agents</h2>
      <div class="wh-tile" style="margin-bottom:1rem">
        <form id="agentForm" class="wh-bento" style="display:grid;gap:.5rem;grid-template-columns:repeat(4,1fr) auto;align-items:end">
          <div class="wh-field" style="margin:0"><label>Name</label><div class="box"><input name="name" required></div></div>
          <div class="wh-field" style="margin:0"><label>Email</label><div class="box"><input name="email" type="email" required></div></div>
          <div class="wh-field" style="margin:0"><label>Code</label><div class="box"><input name="agent_code" placeholder="AGT…"></div></div>
          <div class="wh-field" style="margin:0"><label>Rate %</label><div class="box"><input name="commission_rate" type="number" step="0.01" value="25"></div></div>
          <input type="hidden" name="password" value="agent123">
          <button class="wh-cta" type="submit">Create</button>
        </form>
      </div>
      <div class="wh-tile">
        <table class="wh-table">
          <thead>
            <tr><th>Name</th><th>Code</th><th>Email</th><th>Rate</th><th></th></tr>
          </thead>
          <tbody>
            @forelse($agents as $a)
            <tr>
              <td>{{ $a->name }}</td>
              <td>{{ $a->agent_code }}</td>
              <td>{{ $a->email }}</td>
              <td>{{ $a->commission_rate }}%</td>
              <td><button type="button" class="wh-btn-sm danger" onclick="deleteAgent('{{ $a->public_id ?: $a->agent_code }}', '{{ e($a->name) }}')">Delete</button></td>
            </tr>
            @empty
            <tr><td colspan="5" style="color:var(--mute)">No agents</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    @if($canPane('campaign_requests'))
    <div class="wh-pane {{ $firstPane === 'campaign_requests' ? 'is-on' : '' }}" id="pane-campaign_requests">
      <h2 style="font-family:var(--font-display)">Campaign / ad budget requests</h2>
      <div class="wh-tile">
        <table class="wh-table">
          <thead>
            <tr><th>Agent</th><th>Campaign</th><th>Budget</th><th>Status</th><th>Link</th><th></th></tr>
          </thead>
          <tbody>
            @forelse($campaigns as $c)
            <tr>
              <td>{{ $c->agent_code }}<div style="color:var(--mute);font-size:.75rem">{{ $c->agent_email }}</div></td>
              <td>{{ $c->campaign_name }}</td>
              <td>${{ number_format((float) $c->budget, 2) }}</td>
              <td><span class="wh-badge">{{ $c->status }}</span></td>
              <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis">{{ $c->tracking_link ?: '—' }}</td>
              <td class="wh-btn-row">
                @if($c->status === 'PENDING')
                  <button type="button" class="wh-btn-sm" onclick="approveCampaign('{{ $c->public_id }}')">Approve</button>
                  <button type="button" class="wh-btn-sm danger" onclick="rejectCampaign('{{ $c->public_id }}')">Reject</button>
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="6" style="color:var(--mute)">No campaign requests</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    @if($canPane('support'))
    <div class="wh-pane {{ $firstPane === 'support' ? 'is-on' : '' }}" id="pane-support">
      <h2 style="font-family:var(--font-display)">Support inbox</h2>
      <div class="wh-bento" style="display:grid;grid-template-columns:280px 1fr;gap:.85rem;min-height:420px">
        <div class="wh-tile" style="padding:.5rem;overflow:auto;max-height:70vh">
          @forelse($supportThreads as $email => $msgs)
            @php $last = $msgs->first(); @endphp
            <button
              type="button"
              class="support-thread-btn"
              data-email="{{ $email }}"
              style="display:block;width:100%;text-align:left;border:0;background:transparent;color:var(--ink);padding:.7rem .65rem;border-radius:12px;cursor:pointer;margin-bottom:.2rem"
            >
              <strong style="display:block;font-size:.85rem">{{ $email }}</strong>
              <span style="color:var(--mute);font-size:.72rem;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ \Illuminate\Support\Str::limit($last->message ?? '', 48) }}</span>
            </button>
          @empty
            <p style="color:var(--mute);padding:.65rem">No messages</p>
          @endforelse
        </div>
        <div class="wh-tile" style="display:flex;flex-direction:column;min-height:420px">
          <div id="supportThreadMeta" style="color:var(--mute);font-size:.85rem;margin-bottom:.75rem">Select a thread</div>
          <div id="supportThreadBody" style="flex:1;overflow:auto;max-height:50vh;margin-bottom:.85rem">
            <p style="color:var(--mute)">Pick a player conversation on the left.</p>
          </div>
          <form id="supportReplyForm" style="display:none;gap:.55rem">
            <input type="hidden" id="supportReplyEmail" name="user_email">
            <div class="wh-field" style="margin:0">
              <label>Reply</label>
              <div class="box"><input id="supportReplyMsg" name="message" placeholder="Type reply…"></div>
            </div>
            <label class="wh-upload" style="margin:0">
              <input type="file" id="supportReplyFile" accept="image/*">
              <i class="fa-solid fa-paperclip"></i>
              <div>Attach screenshot (optional)</div>
              <img id="supportReplyPreview" alt="" style="display:none">
            </label>
            <button class="wh-cta" type="submit">Send reply</button>
          </form>
        </div>
      </div>
<script type="application/json" id="supportThreadsJson">{!! json_encode($jsSupportThreads ?? []) !!}</script>
    </div>
    @endif

    @if($canPane('shift_reports'))
    <div class="wh-pane {{ $firstPane === 'shift_reports' ? 'is-on' : '' }}" id="pane-shift_reports">
      <h2 style="font-family:var(--font-display)">Shift reports</h2>
      <div class="wh-tile">
        <table class="wh-table">
          <thead><tr><th>Staff</th><th>Shift</th><th>Date</th><th>Total loaded</th><th>Notes</th></tr></thead>
          <tbody>
            @forelse($shiftReports as $sr)
            <tr>
              <td>{{ $sr->staff_email }}</td>
              <td><span class="wh-badge">{{ $sr->shift_name }}</span></td>
              <td style="font-size:.78rem">{{ $sr->shift_date }}</td>
              <td>{{ number_format((float) $sr->total_loaded, 2) }}</td>
              <td>{{ $sr->notes ?: '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="color:var(--mute)">No shift reports yet</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    @if($canPane('staff'))
    <div class="wh-pane {{ $firstPane === 'staff' ? 'is-on' : '' }}" id="pane-staff">
      <h2 style="font-family:var(--font-display)">Staff accounts</h2>
      <div class="wh-tile" style="margin-bottom:1rem">
        <form id="staffForm" class="wh-bento" style="display:grid;gap:.6rem">
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.55rem">
            <div class="wh-field" style="margin:0"><label>Name</label><div class="box"><input name="name" required></div></div>
            <div class="wh-field" style="margin:0"><label>Email</label><div class="box"><input name="email" type="email" required></div></div>
            <div class="wh-field" style="margin:0"><label>Password</label><div class="box"><input name="password" type="text" value="staff123" required></div></div>
          </div>
          <div>
            <div style="color:var(--mute);font-size:.8rem;margin-bottom:.35rem">Roles</div>
            <div style="display:flex;flex-wrap:wrap;gap:.75rem">
              @foreach(['admin','operation_admin','financial_admin','coins_admin','support_admin'] as $roleOpt)
              <label style="display:flex;align-items:center;gap:.35rem;font-size:.85rem">
                <input type="checkbox" name="roles[]" value="{{ $roleOpt }}"> {{ $roleOpt }}
              </label>
              @endforeach
            </div>
          </div>
          <div>
            <div style="color:var(--mute);font-size:.8rem;margin-bottom:.35rem">Allowed games</div>
            <div style="display:flex;flex-wrap:wrap;gap:.75rem;max-height:120px;overflow:auto">
              @foreach($allGames as $g)
              <label style="display:flex;align-items:center;gap:.35rem;font-size:.85rem">
                <input type="checkbox" name="allowed_game_ids[]" value="{{ $g->public_id }}"> {{ $g->title }}
              </label>
              @endforeach
            </div>
          </div>
          <div><button class="wh-cta" type="submit">Create staff</button></div>
        </form>
      </div>
      <div class="wh-tile">
        <table class="wh-table" id="staffTable">
          <thead><tr><th>Name</th><th>Email</th><th>Role(s)</th><th>Games</th><th></th></tr></thead>
          <tbody>
            @forelse($staff as $s)
            @php
              $sRoles = is_array($s->roles ?? null) && count($s->roles) ? implode(', ', $s->roles) : ($s->role ?: '—');
              $sGames = is_array($s->allowed_game_ids ?? null) ? count($s->allowed_game_ids) : 0;
            @endphp
            <tr>
              <td>{{ $s->name }}</td>
              <td>{{ $s->email }}</td>
              <td style="font-size:.8rem">{{ $sRoles }}</td>
              <td>{{ $sGames }} allowed</td>
              <td class="wh-btn-row">
                <button type="button" class="wh-btn-sm ghost" onclick="resetPlayerPassword({{ (int) $s->id }}, '{{ e($s->email) }}')">Reset pw</button>
                <button type="button" class="wh-btn-sm danger" onclick="deleteUser({{ (int) $s->id }}, '{{ e($s->email) }}')">Delete</button>
              </td>
            </tr>
            @empty
            <tr><td colspan="5" style="color:var(--mute)">No staff accounts</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    @if($canPane('website_payments'))
    <div class="wh-pane {{ $firstPane === 'website_payments' ? 'is-on' : '' }}" id="pane-website_payments">
      <h2 style="font-family:var(--font-display)">Website commission payments</h2>
      <div class="wh-tile"><table class="wh-table"><thead><tr><th>From</th><th>Amount</th><th>Status</th><th>Code</th><th></th></tr></thead><tbody>
        @foreach($ledger->where('type','WEBSITE_COMMISSION_PAYMENT') as $tx)
        <tr>
          <td>{{ $tx->user_email }}</td><td>${{ number_format($tx->amount,2) }}</td><td><span class="wh-badge">{{ $tx->status }}</span></td><td>{{ $tx->code }}</td>
          <td class="wh-btn-row">
            @if($tx->status==='PENDING')
              <button class="wh-btn-sm" onclick="approveTx('{{ $tx->public_id }}','SUCCESS')">Approve</button>
              <button class="wh-btn-sm danger" onclick="approveTx('{{ $tx->public_id }}','FAILED')">Reject</button>
            @endif
          </td>
        </tr>
        @endforeach
        @foreach($ledger->where('type','COMMISSION_WITHDRAW') as $tx)
        <tr>
          <td>{{ $tx->user_email }} <span class="wh-badge">DIST CASHOUT</span></td><td>${{ number_format($tx->amount,2) }}</td><td><span class="wh-badge">{{ $tx->status }}</span></td><td>{{ $tx->code }}</td>
          <td class="wh-btn-row">
            @if(in_array($tx->status,['PENDING','PENDING_COINS'],true))
              <button class="wh-btn-sm" onclick="approveTx('{{ $tx->public_id }}','SUCCESS')">Mark paid</button>
              <button class="wh-btn-sm ghost" onclick="partialPay('{{ $tx->public_id }}')">Partial</button>
              <button class="wh-btn-sm danger" onclick="approveTx('{{ $tx->public_id }}','FAILED')">Fail</button>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody></table></div>
    </div>
    @endif

    @if($canPane('affiliate_commissions'))
    <div class="wh-pane {{ $firstPane === 'affiliate_commissions' ? 'is-on' : '' }}" id="pane-affiliate_commissions">
      <h2 style="font-family:var(--font-display)">Affiliate commission withdrawals</h2>
      <div class="wh-tile"><table class="wh-table"><thead><tr><th>Agent</th><th>Amount</th><th>Status</th><th>Gateway</th><th></th></tr></thead><tbody>
        @foreach($ledger->where('type','AFFILIATE_COMMISSION_WITHDRAW') as $tx)
        <tr>
          <td>{{ $tx->user_email }}</td><td>${{ number_format($tx->amount,2) }}</td><td><span class="wh-badge">{{ $tx->status }}</span></td><td>{{ $tx->gateway }}</td>
          <td class="wh-btn-row">
            @if($tx->status==='PENDING')
              <button class="wh-btn-sm" onclick="approveTx('{{ $tx->public_id }}','SUCCESS')">Mark paid</button>
              <button class="wh-btn-sm ghost" onclick="partialPay('{{ $tx->public_id }}')">Partial</button>
              <button class="wh-btn-sm danger" onclick="approveTx('{{ $tx->public_id }}','FAILED')">Reject</button>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody></table></div>
    </div>
    @endif

    @if($canPane('deleted_accounts'))
    <div class="wh-pane {{ $firstPane === 'deleted_accounts' ? 'is-on' : '' }}" id="pane-deleted_accounts">
      <h2 style="font-family:var(--font-display)">Deleted players</h2>
      <div class="wh-tile">
        <table class="wh-table">
          <thead><tr><th>Email</th><th>Deleted by</th><th>When</th><th>Type</th><th></th></tr></thead>
          <tbody>
            @forelse($deletedPlayers as $dp)
            <tr>
              <td>{{ $dp->email }}</td>
              <td>{{ $dp->deleted_by ?: '—' }}</td>
              <td style="font-size:.78rem">{{ $dp->deleted_at }}</td>
              <td>{{ $dp->deleted_entity_type ?: 'player' }}</td>
              <td><button type="button" class="wh-btn-sm" onclick="restoreDeleted('{{ e($dp->email) }}')">Restore</button></td>
            </tr>
            @empty
            <tr><td colspan="5" style="color:var(--mute)">No deleted players</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    @if($canPane('settings'))
    <div class="wh-pane {{ $firstPane === 'settings' ? 'is-on' : '' }}" id="pane-settings">
      <h2 style="font-family:var(--font-display)">Business settings</h2>
      <div class="wh-tile">
        <form id="settingsForm" class="wh-bento" style="display:grid;gap:.75rem;grid-template-columns:1fr 1fr;max-width:780px">
          <div class="wh-field"><label>First deposit bonus %</label><div class="box"><input name="first_deposit_bonus" type="number" step="0.01" value="{{ $global['first_deposit_bonus'] ?? 300 }}"></div></div>
          <div class="wh-field"><label>Regular deposit bonus %</label><div class="box"><input name="regular_deposit_bonus" type="number" step="0.01" value="{{ $global['regular_deposit_bonus'] ?? 20 }}"></div></div>
          <div class="wh-field"><label>Referral bonus %</label><div class="box"><input name="referral_bonus" type="number" step="0.01" value="{{ $global['referral_bonus'] ?? 10 }}"></div></div>
          <div class="wh-field"><label>Signup freeplay $</label><div class="box"><input name="signup_freeplay" type="number" step="0.01" value="{{ $global['signup_freeplay'] ?? 3 }}"></div></div>
          <div class="wh-field"><label>Freeplay cashout cap $</label><div class="box"><input name="freeplay_cashout_cap" type="number" step="0.01" value="{{ $global['freeplay_cashout_cap'] ?? 30 }}"></div></div>
          <div class="wh-field"><label>Freeplay min request (coins)</label><div class="box"><input name="freeplay_min_request" type="number" step="0.01" value="{{ $global['freeplay_min_request'] ?? 100 }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1"><label>Repeat freeplay deposit threshold $</label><div class="box"><input name="repeat_freeplay_deposit_threshold" type="number" step="0.01" value="{{ $global['repeat_freeplay_deposit_threshold'] ?? 25 }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1"><label>USDT address</label><div class="box"><input name="usdt_address" type="text" value="{{ $global['usdt_address'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Affiliate payout network</label><div class="box"><input name="affiliate_payout_network" value="{{ $global['affiliate_payout_network'] ?? 'TRC20' }}"></div></div>
          <div class="wh-field"><label>Platform commission %</label><div class="box"><input name="affiliate_platform_commission_rate" type="number" step="0.01" value="{{ $global['affiliate_platform_commission_rate'] ?? 90 }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1"><label>Affiliate payout wallet (TRC20)</label><div class="box"><input name="affiliate_payout_wallet" value="{{ $global['affiliate_payout_wallet'] ?? '' }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1"><label>Affiliate payout wallet (BEP20)</label><div class="box"><input name="affiliate_payout_wallet_bep20" value="{{ $global['affiliate_payout_wallet_bep20'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Ad payment network</label><div class="box"><input name="ad_payment_network" value="{{ $global['ad_payment_network'] ?? 'BEP20' }}"></div></div>
          <div class="wh-field"><label>Ad budget limit $</label><div class="box"><input name="ad_budget_limit" type="number" step="0.01" value="{{ $global['ad_budget_limit'] ?? 6000 }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1"><label>Ad payment wallet</label><div class="box"><input name="ad_payment_wallet" value="{{ $global['ad_payment_wallet'] ?? '' }}"></div></div>
          <input type="hidden" name="usdt_qr_code" id="usdtQrField" value="{{ $global['usdt_qr_code'] ?? '' }}">
          <input type="hidden" name="affiliate_payout_qr_code" value="{{ $global['affiliate_payout_qr_code'] ?? '' }}">
          <input type="hidden" name="affiliate_payout_qr_bep20" value="{{ $global['affiliate_payout_qr_bep20'] ?? '' }}">
          <input type="hidden" name="ad_payment_qr_code" value="{{ $global['ad_payment_qr_code'] ?? '' }}">
          <div style="grid-column:1 / -1">
            <label class="wh-upload" style="max-width:360px">
              <input type="file" id="usdtQrFile" accept="image/*">
              <i class="fa-solid fa-qrcode"></i>
              <div>Upload USDT QR (base64)</div>
              <img id="usdtQrPreview" alt="" style="{{ !empty($global['usdt_qr_code']) ? '' : 'display:none' }}" @if(!empty($global['usdt_qr_code'])) src="{{ $global['usdt_qr_code'] }}" @endif>
            </label>
          </div>
          <div style="grid-column:1 / -1"><button class="wh-cta" type="submit">Save settings</button></div>
        </form>
      </div>
    </div>
    @endif

    @if($canPane('frontend_settings'))
    <div class="wh-pane {{ $firstPane === 'frontend_settings' ? 'is-on' : '' }}" id="pane-frontend_settings">
      <h2 style="font-family:var(--font-display)">Frontend CMS</h2>
      <div class="wh-tile" style="max-width:780px">
        <form id="frontendForm" class="wh-bento" style="display:grid;gap:.75rem;grid-template-columns:1fr 1fr">
          <div class="wh-field" style="grid-column:1 / -1"><label>Landing welcome</label><div class="box"><input name="landing_welcome" id="fe_landing_welcome" value="{{ $frontend['landing_welcome'] ?? '' }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1"><label>Landing grab</label><div class="box"><input name="landing_grab" id="fe_landing_grab" value="{{ $frontend['landing_grab'] ?? '' }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1"><label>Lobby hero promo</label><div class="box"><input name="lobby_hero_promo" id="fe_lobby_hero_promo" value="{{ $frontend['lobby_hero_promo'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Trust badge 1</label><div class="box"><input name="lobby_trust_badge_1" id="fe_lobby_trust_badge_1" value="{{ $frontend['lobby_trust_badge_1'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Trust badge 2</label><div class="box"><input name="lobby_trust_badge_2" id="fe_lobby_trust_badge_2" value="{{ $frontend['lobby_trust_badge_2'] ?? '' }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1"><label>Trust badge 3</label><div class="box"><input name="lobby_trust_badge_3" id="fe_lobby_trust_badge_3" value="{{ $frontend['lobby_trust_badge_3'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Freeplay value</label><div class="box"><input name="lobby_freeplay_value" id="fe_lobby_freeplay_value" value="{{ $frontend['lobby_freeplay_value'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Freeplay label</label><div class="box"><input name="lobby_freeplay_label" id="fe_lobby_freeplay_label" value="{{ $frontend['lobby_freeplay_label'] ?? '' }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1"><label>Freeplay condition</label><div class="box"><input name="lobby_freeplay_condition" id="fe_lobby_freeplay_condition" value="{{ $frontend['lobby_freeplay_condition'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Min deposit $</label><div class="box"><input name="minimum_deposit_limit" id="fe_minimum_deposit_limit" type="number" step="0.01" value="{{ $frontend['minimum_deposit_limit'] ?? 5 }}"></div></div>
          <div class="wh-field"><label>Min withdrawal $</label><div class="box"><input name="minimum_withdrawal_limit" id="fe_minimum_withdrawal_limit" type="number" step="0.01" value="{{ $frontend['minimum_withdrawal_limit'] ?? 5 }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1"><label>Withdraw notice</label><div class="box"><input name="withdraw_notice" id="fe_withdraw_notice" value="{{ $frontend['withdraw_notice'] ?? '' }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1"><label>Cashout notice</label><div class="box"><input name="cashout_notice" id="fe_cashout_notice" value="{{ $frontend['cashout_notice'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Android app URL</label><div class="box"><input name="android_app_url" id="fe_android_app_url" value="{{ $frontend['android_app_url'] ?? '' }}"></div></div>
          <div class="wh-field"><label>iOS app URL</label><div class="box"><input name="ios_app_url" id="fe_ios_app_url" value="{{ $frontend['ios_app_url'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Get App enabled</label>
            <select name="get_app_enabled" id="fe_get_app_enabled" style="width:100%;border:1px solid var(--line);background:rgba(0,0,0,.35);color:var(--ink);border-radius:12px;padding:.75rem .85rem">
              <option value="1" @selected(!empty($frontend['get_app_enabled']))>Yes</option>
              <option value="0" @selected(empty($frontend['get_app_enabled']))>No</option>
            </select>
          </div>
          <div class="wh-field"><label>Require game screenshot (WD)</label>
            <select name="withdraw_require_game_screenshot" id="fe_withdraw_require_game_screenshot" style="width:100%;border:1px solid var(--line);background:rgba(0,0,0,.35);color:var(--ink);border-radius:12px;padding:.75rem .85rem">
              <option value="1" @selected(!empty($frontend['withdraw_require_game_screenshot']))>Yes</option>
              <option value="0" @selected(empty($frontend['withdraw_require_game_screenshot']))>No</option>
            </select>
          </div>
          <div class="wh-field"><label>Require tag QR screenshot (WD)</label>
            <select name="withdraw_require_tag_qr_screenshot" id="fe_withdraw_require_tag_qr_screenshot" style="width:100%;border:1px solid var(--line);background:rgba(0,0,0,.35);color:var(--ink);border-radius:12px;padding:.75rem .85rem">
              <option value="1" @selected(!isset($frontend['withdraw_require_tag_qr_screenshot']) || !empty($frontend['withdraw_require_tag_qr_screenshot']))>Yes</option>
              <option value="0" @selected(isset($frontend['withdraw_require_tag_qr_screenshot']) && empty($frontend['withdraw_require_tag_qr_screenshot']))>No</option>
            </select>
          </div>
          <div class="wh-field" style="grid-column:1 / -1"><label>HQ notification sound URL (mp3 / data URL)</label><div class="box"><input name="notification_sound_url" id="fe_notification_sound_url" value="{{ $frontend['notification_sound_url'] ?? '' }}" placeholder="https://…/notify.mp3"></div></div>
          <div class="wh-field"><label>Freeplay claim button</label><div class="box"><input name="lobby_freeplay_claim_btn" id="fe_lobby_freeplay_claim_btn" value="{{ $frontend['lobby_freeplay_claim_btn'] ?? 'CLAIM FREEPLAY NOW' }}"></div></div>
          <div class="wh-field"><label>Hero side image enabled</label>
            <select name="lobby_hero_side_enabled" id="fe_lobby_hero_side_enabled" style="width:100%;border:1px solid var(--line);background:rgba(0,0,0,.35);color:var(--ink);border-radius:12px;padding:.75rem .85rem">
              <option value="1" @selected(!isset($frontend['lobby_hero_side_enabled']) || !empty($frontend['lobby_hero_side_enabled']))>Yes</option>
              <option value="0" @selected(isset($frontend['lobby_hero_side_enabled']) && empty($frontend['lobby_hero_side_enabled']))>No</option>
            </select>
          </div>
          <div class="wh-field"><label>Bullet 1 title</label><div class="box"><input name="lobby_bullet_1_title" value="{{ $frontend['lobby_bullet_1_title'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Bullet 1 desc</label><div class="box"><input name="lobby_bullet_1_desc" value="{{ $frontend['lobby_bullet_1_desc'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Bullet 2 title</label><div class="box"><input name="lobby_bullet_2_title" value="{{ $frontend['lobby_bullet_2_title'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Bullet 2 desc</label><div class="box"><input name="lobby_bullet_2_desc" value="{{ $frontend['lobby_bullet_2_desc'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Bullet 3 title</label><div class="box"><input name="lobby_bullet_3_title" value="{{ $frontend['lobby_bullet_3_title'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Bullet 3 desc</label><div class="box"><input name="lobby_bullet_3_desc" value="{{ $frontend['lobby_bullet_3_desc'] ?? '' }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1"><label>Landing Google button (login)</label><div class="box"><input name="landing_login_with_google" value="{{ $frontend['landing_login_with_google'] ?? '' }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1"><label>Landing Google button (signup)</label><div class="box"><input name="landing_signup_with_google" value="{{ $frontend['landing_signup_with_google'] ?? '' }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1"><label>Messenger warning</label><div class="box"><input name="landing_messenger_warning" value="{{ $frontend['landing_messenger_warning'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Info page enabled</label>
            <select name="info_page_enabled" style="width:100%;border:1px solid var(--line);background:rgba(0,0,0,.35);color:var(--ink);border-radius:12px;padding:.75rem .85rem">
              <option value="1" @selected(!empty($frontend['info_page_enabled']))>Yes</option>
              <option value="0" @selected(empty($frontend['info_page_enabled']))>No</option>
            </select>
          </div>
          <div class="wh-field"><label>Info tagline</label><div class="box"><input name="info_tagline" value="{{ $frontend['info_tagline'] ?? '' }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1"><label>Info lead text</label><div class="box"><input name="info_lead" value="{{ $frontend['info_lead'] ?? '' }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1"><label>Info support note</label><div class="box"><input name="info_support_note" value="{{ $frontend['info_support_note'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Instagram handle</label><div class="box"><input name="info_instagram_handle" value="{{ $frontend['info_instagram_handle'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Instagram URL</label><div class="box"><input name="info_instagram_url" value="{{ $frontend['info_instagram_url'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Telegram handle</label><div class="box"><input name="info_telegram_handle" value="{{ $frontend['info_telegram_handle'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Telegram URL</label><div class="box"><input name="info_telegram_url" value="{{ $frontend['info_telegram_url'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Facebook handle</label><div class="box"><input name="info_facebook_handle" value="{{ $frontend['info_facebook_handle'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Facebook URL</label><div class="box"><input name="info_facebook_url" value="{{ $frontend['info_facebook_url'] ?? '' }}"></div></div>
          <div class="wh-field"><label>WhatsApp handle</label><div class="box"><input name="info_whatsapp_handle" value="{{ $frontend['info_whatsapp_handle'] ?? '' }}"></div></div>
          <div class="wh-field"><label>WhatsApp URL</label><div class="box"><input name="info_whatsapp_url" value="{{ $frontend['info_whatsapp_url'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Support email</label><div class="box"><input name="info_email_handle" value="{{ $frontend['info_email_handle'] ?? '' }}"></div></div>
          <div class="wh-field"><label>Support mailto URL</label><div class="box"><input name="info_email_url" value="{{ $frontend['info_email_url'] ?? '' }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1">
            <label>Favicon URL / Logo (Browser Tab Icon)</label>
            <div class="box"><input name="favicon_url" id="fe_favicon_url" value="{{ $frontend['favicon_url'] ?? '' }}" placeholder="/brand/logo.png"></div>
          </div>
          <div>
            <label class="wh-upload">
              <input type="file" id="feFaviconFile" accept="image/*">
              <i class="fa-solid fa-image"></i>
              <div>Upload Favicon → Base64</div>
              <img id="feFaviconPreview" alt="" style="display:none" src="{{ $frontend['favicon_url'] ?? '' }}">
            </label>
          </div>

          {{-- Simple Easy Builders for CMS Data --}}
          <div class="wh-tile" style="grid-column:1 / -1;background:rgba(0,0,0,.25);border:1px solid var(--line);padding:1rem">
            <h3 style="font-family:var(--font-display);margin-top:0"><i class="fa-solid fa-list-check" style="color:var(--sand)"></i> Marquee Live Payouts (Simple Builder)</h3>
            <p style="color:var(--mute);font-size:.85rem;margin-bottom:1rem">Add or edit recent winner payouts shown in the lobby marquee bar.</p>
            <div id="marqueePayoutsContainer" style="display:flex;flex-direction:column;gap:.65rem"></div>
            <button type="button" class="wh-btn-sm ghost" id="addMarqueePayoutBtn" style="margin-top:.75rem"><i class="fa-solid fa-plus"></i> Add Payout Row</button>
          </div>

          <div class="wh-tile" style="grid-column:1 / -1;background:rgba(0,0,0,.25);border:1px solid var(--line);padding:1rem">
            <h3 style="font-family:var(--font-display);margin-top:0"><i class="fa-solid fa-shield-halved" style="color:var(--sand)"></i> Cashout Rules (Simple Builder)</h3>
            <p style="color:var(--mute);font-size:.85rem;margin-bottom:1rem">Define rules displayed to players on the withdrawal/cashout screen.</p>
            <div id="cashoutRulesContainer" style="display:flex;flex-direction:column;gap:.65rem"></div>
            <button type="button" class="wh-btn-sm ghost" id="addCashoutRuleBtn" style="margin-top:.75rem"><i class="fa-solid fa-plus"></i> Add Rule Row</button>
          </div>

          <div class="wh-tile" style="grid-column:1 / -1;background:rgba(0,0,0,.25);border:1px solid var(--line);padding:1rem">
            <h3 style="font-family:var(--font-display);margin-top:0"><i class="fa-solid fa-award" style="color:var(--sand)"></i> Lobby Cashout Trust Items (Simple Builder)</h3>
            <p style="color:var(--mute);font-size:.85rem;margin-bottom:1rem">Trust badges shown on the player dashboard cashout card.</p>
            <div id="trustItemsContainer" style="display:flex;flex-direction:column;gap:.65rem"></div>
            <button type="button" class="wh-btn-sm ghost" id="addTrustItemBtn" style="margin-top:.75rem"><i class="fa-solid fa-plus"></i> Add Trust Item</button>
          </div>

          {{-- Advanced JSON fallbacks --}}
          <details style="grid-column:1 / -1;margin-top:.5rem">
            <summary style="cursor:pointer;color:var(--mute);font-size:.85rem">Advanced Raw JSON Editors (Optional)</summary>
            <div style="display:grid;gap:.75rem;margin-top:.75rem">
              <div class="wh-field"><label>Marquee payouts (JSON)</label>
                <textarea name="marquee_payouts_json" id="fe_marquee_payouts_json" rows="3" style="width:100%;border:1px solid var(--line);background:rgba(0,0,0,.35);color:var(--ink);border-radius:12px;padding:.75rem .85rem;font-family:ui-monospace,monospace;font-size:.75rem">{{ json_encode($frontend['marquee_payouts'] ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</textarea>
              </div>
              <div class="wh-field"><label>Cashout rules (JSON)</label>
                <textarea name="cashout_rules_json" id="fe_cashout_rules_json" rows="3" style="width:100%;border:1px solid var(--line);background:rgba(0,0,0,.35);color:var(--ink);border-radius:12px;padding:.75rem .85rem;font-family:ui-monospace,monospace;font-size:.75rem">{{ json_encode($frontend['cashout_rules'] ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</textarea>
              </div>
              <div class="wh-field"><label>Lobby cashout trust items (JSON)</label>
                <textarea name="lobby_cashout_trust_items_json" id="fe_lobby_cashout_trust_items_json" rows="3" style="width:100%;border:1px solid var(--line);background:rgba(0,0,0,.35);color:var(--ink);border-radius:12px;padding:.75rem .85rem;font-family:ui-monospace,monospace;font-size:.75rem">{{ json_encode($frontend['lobby_cashout_trust_items'] ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</textarea>
              </div>
              <div class="wh-field"><label>Proof screenshots (JSON URLs)</label>
                <textarea name="proof_screenshots_json" id="fe_proof_screenshots_json" rows="2" style="width:100%;border:1px solid var(--line);background:rgba(0,0,0,.35);color:var(--ink);border-radius:12px;padding:.75rem .85rem;font-family:ui-monospace,monospace;font-size:.75rem">{{ json_encode($frontend['proof_screenshots'] ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</textarea>
              </div>
            </div>
          </details>
          <div style="grid-column:1 / -1;display:flex;gap:.55rem;flex-wrap:wrap;align-items:center">
            <button class="wh-btn-sm ghost" type="button" id="feSoundTestBtn"><i class="fa-solid fa-volume-high"></i> Test sound</button>
            <label class="wh-upload" style="flex:1;min-width:180px;margin:0;padding:.55rem">
              <input type="file" id="feSoundFile" accept="audio/*,video/*,.mp3,.wav,.ogg">
              <i class="fa-solid fa-music"></i>
              <div style="font-size:.78rem">Upload custom notification sound</div>
            </label>
          </div>
          <div class="wh-field" style="grid-column:1 / -1"><label>Logo URL / data</label><div class="box"><input name="logo_url" id="fe_logo_url" value="{{ $frontend['logo_url'] ?? '' }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1"><label>Login BG URL / data</label><div class="box"><input name="login_bg_url" id="fe_login_bg_url" value="{{ $frontend['login_bg_url'] ?? '' }}"></div></div>
          <div class="wh-field" style="grid-column:1 / -1"><label>Hero side image URL</label><div class="box"><input name="lobby_hero_side_image" id="fe_lobby_hero_side_image" value="{{ $frontend['lobby_hero_side_image'] ?? '' }}"></div></div>
          <div>
            <label class="wh-upload">
              <input type="file" id="feLogoFile" accept="image/*">
              <i class="fa-solid fa-image"></i>
              <div>Upload logo → base64</div>
              <img id="feLogoPreview" alt="" style="display:none">
            </label>
          </div>
          <div>
            <label class="wh-upload">
              <input type="file" id="feBgFile" accept="image/*">
              <i class="fa-solid fa-image"></i>
              <div>Upload login BG → base64</div>
              <img id="feBgPreview" alt="" style="display:none">
            </label>
          </div>
          <div style="grid-column:1 / -1;display:flex;gap:.55rem;flex-wrap:wrap">
            <button class="wh-cta" type="submit">Save frontend</button>
            <button class="wh-btn-sm ghost" type="button" id="feReloadBtn">Reload from API</button>
          </div>
        </form>
      </div>
    </div>
    @endif

    @if($canPane('push'))
    <div class="wh-pane {{ $firstPane === 'push' ? 'is-on' : '' }}" id="pane-push">
      <h2 style="font-family:var(--font-display)">Push notifications</h2>
      <div class="wh-tile" style="max-width:520px">
        <p style="color:var(--mute)">Broadcast promo to subscribed players (Web Push + FCM when keys configured).</p>
        <div class="wh-field"><label>Title</label><div class="box"><input id="pushTitle" value="Winning Heaven offer"></div></div>
        <div class="wh-field"><label>Message</label><div class="box"><input id="pushBody" value="New bonus waiting in your lobby"></div></div>
        <button class="wh-cta" type="button" id="sendPush">Send broadcast</button>
        <p id="pushMsg" style="color:var(--sand)"></p>
      </div>
    </div>
    @endif
  </div>
</div>
<div class="wh-lightbox" id="shotLightbox" onclick="this.classList.remove('is-on')">
  <img id="shotLightboxImg" alt="Screenshot">
</div>

{{-- Create Game modal (Jackpot AdminGameModal) --}}
<div class="wh-desk-modal" id="gameCreateModal" aria-hidden="true">
  <div class="wh-desk-modal__card">
    <div class="wh-desk-modal__head">
      <h3 style="font-family:var(--font-display);margin:0">Create Game</h3>
      <button type="button" class="wh-nav__btn" data-close-modal="gameCreateModal">Close</button>
    </div>
    <form id="gameForm" class="wh-desk-modal__body">
      <div class="wh-field"><label>Title *</label><div class="box"><input name="title" required placeholder="Game name"></div></div>
      <div class="wh-field"><label>Badge</label>
        <select name="badge" style="width:100%;border:1px solid var(--line);background:rgba(0,0,0,.35);color:var(--ink);border-radius:12px;padding:.75rem .85rem">
          <option value="none">none</option>
          <option value="hot">HOT</option>
          <option value="new">NEW</option>
        </select>
      </div>
      <div class="wh-field"><label>Play link</label><div class="box"><input name="link" placeholder="https://…"></div></div>
      <div class="wh-field"><label>Pool coins</label><div class="box"><input name="available_coins" type="number" value="10000"></div></div>
      <input type="hidden" name="image" id="gameImageField" value="">
      <label class="wh-upload">
        <input type="file" id="gameImageFile" accept="image/*">
        <i class="fa-solid fa-image"></i>
        <div>Upload game logo / image</div>
        <img id="gameImagePreview" alt="" style="display:none">
      </label>
      <div class="wh-desk-modal__actions">
        <button type="button" class="wh-btn-sm ghost" data-close-modal="gameCreateModal">Cancel</button>
        <button class="wh-cta" type="submit">Save Game</button>
      </div>
    </form>
  </div>
</div>

{{-- Edit Game modal (Jackpot AdminGameModal) --}}
<div class="wh-desk-modal" id="gameEditModal" aria-hidden="true">
  <div class="wh-desk-modal__card">
    <div class="wh-desk-modal__head">
      <h3 style="font-family:var(--font-display);margin:0">Edit Game</h3>
      <button type="button" class="wh-nav__btn" data-close-modal="gameEditModal">Close</button>
    </div>
    <form id="gameEditForm" class="wh-desk-modal__body">
      <input type="hidden" id="editGamePublicId">
      <div class="wh-field"><label>Title *</label><div class="box"><input name="title" id="editGameTitle" required placeholder="Game name"></div></div>
      <div class="wh-field"><label>Badge</label>
        <select name="badge" id="editGameBadge" style="width:100%;border:1px solid var(--line);background:rgba(0,0,0,.35);color:var(--ink);border-radius:12px;padding:.75rem .85rem">
          <option value="none">none</option>
          <option value="hot">HOT</option>
          <option value="new">NEW</option>
        </select>
      </div>
      <div class="wh-field"><label>Play link *</label><div class="box"><input name="link" id="editGameLink" required placeholder="https://…"></div></div>
      <div class="wh-field"><label>Open panel link (optional)</label><div class="box"><input name="open_panel_link" id="editGameOpenPanelLink" placeholder="https://…"></div></div>
      <div class="wh-field"><label>Available pool coins</label><div class="box"><input name="available_coins" id="editGameCoins" type="number" step="1"></div></div>
      <input type="hidden" name="image" id="editGameImageField" value="">
      <label class="wh-upload">
        <input type="file" id="editGameImageFile" accept="image/*">
        <i class="fa-solid fa-image"></i>
        <div>Change game logo / image (optional)</div>
        <img id="editGameImagePreview" alt="" style="display:none">
      </label>
      <div class="wh-desk-modal__actions">
        <button type="button" class="wh-btn-sm ghost" data-close-modal="gameEditModal">Cancel</button>
        <button class="wh-cta" type="submit">Update Game</button>
      </div>
    </form>
  </div>
</div>

{{-- Create / Edit Gateway modal (Jackpot AdminGatewayModal) --}}
<div class="wh-desk-modal" id="gwCreateModal" aria-hidden="true">
  <div class="wh-desk-modal__card" style="max-width:560px">
    <div class="wh-desk-modal__head">
      <h3 style="font-family:var(--font-display);margin:0" id="gwModalTitle">Create Gateway</h3>
      <button type="button" class="wh-nav__btn" data-close-modal="gwCreateModal">Close</button>
    </div>
    <form id="gwForm" class="wh-desk-modal__body">
      <input type="hidden" id="gwEditId" value="">
      <div class="wh-field"><label>Gateway Name *</label><div class="box"><input name="name" required placeholder="e.g. Cash App, Stripe, Chime"></div></div>
      <div class="wh-field"><label>Description subtitle</label><div class="box"><input name="subtitle" placeholder="e.g. Pay using Cash App link"></div></div>
      <div class="wh-field"><label>Button Visual Theme *</label>
        <div class="wh-theme-picker" id="gwThemePicker">
          @foreach([
            'chime' => 'Chime Green',
            'cashapp' => 'Cash App',
            'stripe' => 'Stripe',
            'crypto' => 'Crypto',
            'zelle' => 'Zelle',
            'paypal' => 'PayPal',
            'venmo' => 'Venmo',
          ] as $th => $thLabel)
            <label class="wh-theme-opt" data-theme="{{ $th }}">
              <input type="radio" name="theme" value="{{ $th }}" {{ $th === 'chime' ? 'checked' : '' }}>
              <span>{{ $thLabel }}</span>
            </label>
          @endforeach
        </div>
        <p class="wh-field-hint">Player deposit CONTINUE button uses this color (same as Jackpot).</p>
      </div>
      <div class="wh-field" id="gwTagWrap"><label>Payment Tag / ID Address</label><div class="box"><input name="tag" placeholder="e.g. $MyTag, name@email.com"></div></div>
      <div class="wh-field" id="gwPhoneWrap"><label>Linked Phone / Info</label><div class="box"><input name="phone" placeholder="e.g. 555-123-4567, USDT TRC20"></div></div>
      <div class="wh-field" id="gwRedirectWrap" style="display:none"><label>Pay Redirect URL (Cash App / Stripe)</label><div class="box"><input name="redirect_url" placeholder="https://cash.app/$Tag or Stripe link — {amount} {code} ok"></div></div>
      <label class="wh-check"><input type="checkbox" name="is_withdraw_active" id="gwWithdrawActive" value="1" checked> Enable for withdrawals (cashout)</label>
      <div id="gwRequireWrap" style="display:grid;gap:.45rem;padding:.65rem;border:1px solid var(--line);border-radius:12px;margin:.35rem 0">
        <div style="color:var(--mute);font-size:.75rem;font-weight:700">Withdraw field requirements</div>
        <label class="wh-check"><input type="checkbox" name="require_name_on_tag" value="1" checked> Require name on tag</label>
        <label class="wh-check"><input type="checkbox" name="require_tag" value="1" checked> Require tag / address</label>
        <label class="wh-check"><input type="checkbox" name="require_phone_on_tag" value="1" checked> Require phone on tag</label>
        <label class="wh-check"><input type="checkbox" name="require_email_on_tag" value="1"> Require email on tag</label>
      </div>
      <input type="hidden" name="qr_image" id="gwQrField" value="">
      <label class="wh-upload" id="gwQrUpload">
        <input type="file" id="gwQrFile" accept="image/*">
        <i class="fa-solid fa-qrcode"></i>
        <div>Upload gateway QR (tag/QR gateways)</div>
        <img id="gwQrPreview" alt="" style="display:none">
      </label>
      <div class="wh-desk-modal__actions">
        <button type="button" class="wh-btn-sm ghost" data-close-modal="gwCreateModal">Cancel</button>
        <button class="wh-cta" type="submit" id="gwSaveBtn">Save Gateway</button>
      </div>
    </form>
  </div>
</div>
@endsection

@php
  $gwMap = [];
  foreach ($allGateways ?? [] as $g) {
    $gwMap[$g->public_id] = [
      'public_id' => $g->public_id,
      'name' => $g->name,
      'subtitle' => $g->subtitle,
      'tag' => $g->tag,
      'phone' => $g->phone,
      'theme' => $g->theme,
      'qr_image' => $g->qr_image,
      'redirect_url' => $g->redirect_url,
      'is_withdraw_active' => (bool) $g->is_withdraw_active,
      'require_name_on_tag' => $g->require_name_on_tag !== false,
      'require_tag' => $g->require_tag !== false,
      'require_phone_on_tag' => $g->require_phone_on_tag !== false,
      'require_email_on_tag' => (bool) $g->require_email_on_tag,
    ];
  }
@endphp

@push('scripts')
<script>
(function () {
  const PORTAL_BASE = {!! json_encode('/' . ($portalBase ?? 'admin')) !!};
  const ROLE_LIST = {!! json_encode($roleList) !!};
  const IS_FULL_ADMIN = {!! json_encode($isFullAdmin) !!};
  const GW_MAP = {!! json_encode($gwMap) !!};
  const completedActionIds = {};
  let bc = null;
  try { bc = new BroadcastChannel('wh-admin-events'); } catch (_) {}

  function currentTabUrl(pane) {
    return PORTAL_BASE + '/' + (pane || 'dashboard');
  }

  function showPane(pane, push) {
    const btn = document.querySelector('.wh-aside button[data-pane="' + pane + '"]');
    if (!btn) return;
    document.querySelectorAll('.wh-aside button[data-pane]').forEach((b) => b.classList.remove('is-on'));
    document.querySelectorAll('.wh-pane').forEach((p) => p.classList.remove('is-on'));
    btn.classList.add('is-on');
    const el = document.getElementById('pane-' + pane);
    if (el) el.classList.add('is-on');
    if (push !== false) {
      const url = currentTabUrl(pane);
      if (window.location.pathname !== url) {
        history.pushState({ adminTab: pane }, '', url);
      }
    }
    if (pane === 'ledger') softRefreshLedger();
    if (pane === 'coins') softRefreshCoins();
    if (pane === 'requests') softRefreshRequests();
    if (pane === 'support') softRefreshSupportSidebar();
  }

  document.querySelectorAll('.wh-aside button[data-pane]').forEach((btn) => {
    btn.onclick = () => showPane(btn.dataset.pane, true);
  });
  window.addEventListener('popstate', (e) => {
    const pane = (e.state && e.state.adminTab) || (window.location.pathname.split('/').filter(Boolean)[1]) || 'dashboard';
    showPane(pane, false);
  });
  // Ensure URL reflects initial pane
  (function syncInitialUrl() {
    const on = document.querySelector('.wh-aside button[data-pane].is-on');
    const pane = on ? on.dataset.pane : 'dashboard';
    const url = currentTabUrl(pane);
    if (window.location.pathname !== url) {
      history.replaceState({ adminTab: pane }, '', url);
    }
  })();

  function removeTxRow(id) {
    completedActionIds[id] = true;
    document.querySelectorAll('[data-tx-id="' + id + '"]').forEach((tr) => tr.remove());
  }

  function postAdminEvent(type, extra) {
    try {
      if (bc) bc.postMessage(Object.assign({ type: type }, extra || {}));
    } catch (_) {}
  }

  async function softRefreshLedger() {
    try {
      const d = await WH.api('/transactions?status=PENDING&hq_global=1');
      const items = (d.items || []).filter((t) => !completedActionIds[t.public_id]);
      const deps = items.filter((t) => String(t.type).toUpperCase() === 'DEPOSIT');
      const wds = items.filter((t) => String(t.type).toUpperCase() === 'WITHDRAW');
      const depBody = document.getElementById('ledgerDepBody');
      const wdBody = document.getElementById('ledgerWdBody');
      if (depBody) {
        depBody.innerHTML = deps.length ? deps.map((tx) => `
          <tr data-tx-id="${esc(tx.public_id)}" data-search="${esc((tx.user_email+' '+tx.game_title+' '+tx.code+' '+tx.gateway).toLowerCase())}">
            <td>${esc(tx.user_email)}</td>
            <td>$${Number(tx.amount).toFixed(2)}</td>
            <td>${esc(tx.gateway || '')}</td>
            <td>${esc(tx.game_title || '')}</td>
            <td>${esc(tx.code || '')}</td>
            <td>${tx.screenshot ? `<img class="wh-shot-thumb" src="${esc(tx.screenshot)}" alt="proof" onclick="openShotLightbox(this.src)">` : '<span style="color:var(--mute)">—</span>'}</td>
            <td class="wh-btn-row">
              <button type="button" class="wh-btn-sm" onclick="approveDeposit('${esc(tx.public_id)}')">Approve</button>
              <button type="button" class="wh-btn-sm danger" onclick="rejectDeposit('${esc(tx.public_id)}')">Reject</button>
            </td>
          </tr>`).join('') : '<tr class="wh-empty-row"><td colspan="7" style="color:var(--mute)">No pending deposits</td></tr>';
      }
      if (wdBody) {
        wdBody.innerHTML = wds.length ? wds.map((tx) => {
          const amt = Number(tx.payout_amount != null ? tx.payout_amount : tx.amount);
          return `
          <tr data-tx-id="${esc(tx.public_id)}" data-search="${esc((tx.user_email+' '+tx.game_title+' '+tx.code+' '+tx.gateway).toLowerCase())}">
            <td>${esc(tx.user_email)}${tx.is_freeplay_withdraw ? ' <span class="wh-badge" style="background:rgba(255,77,109,.18);color:#ff6b7a">FREEPLAY</span>' : ''}</td>
            <td>$${Number(tx.amount).toFixed(2)}</td>
            <td>${tx.payout_amount != null ? '$'+Number(tx.payout_amount).toFixed(2) : '<span style="color:var(--mute)">same</span>'}</td>
            <td><span class="wh-badge">${esc(tx.status)}</span></td>
            <td>${esc(tx.game_title || '')}</td>
            <td>${esc(tx.gateway || '')} · ${esc(tx.code || '')}</td>
            <td class="wh-btn-row">
              <button type="button" class="wh-btn-sm" onclick="markPaid('${esc(tx.public_id)}', ${amt})">Mark paid</button>
              <button type="button" class="wh-btn-sm ghost" onclick="partialPayout('${esc(tx.public_id)}', ${amt})">Partial</button>
              <button type="button" class="wh-btn-sm danger" onclick="failWithdraw('${esc(tx.public_id)}')">Fail</button>
            </td>
          </tr>`;
        }).join('') : '<tr class="wh-empty-row"><td colspan="7" style="color:var(--mute)">No pending withdrawals</td></tr>';
      }
      filterLedgerQueue();
    } catch (_) {}
  }

  async function softRefreshCoins() {
    try {
      const d = await WH.api('/coins-notifications?status=PENDING');
      const body = document.querySelector('#pane-coins tbody');
      if (!body) return;
      const items = (d.items || d.notifications || []).filter((c) => !completedActionIds[c.public_id]);
      if (!items.length) {
        body.innerHTML = '<tr><td colspan="6" style="color:var(--mute)">Coins queue empty</td></tr>';
        return;
      }
      body.innerHTML = items.map((c) => `
        <tr data-tx-id="${esc(c.public_id)}">
          <td>${esc(c.user_email)}</td>
          <td>${esc(c.game_title || '')}</td>
          <td>$${Number(c.deposit_amount || 0).toFixed(2)}</td>
          <td>${esc(c.bonus_applied)}</td>
          <td><strong>${Number(c.total_coins || 0)}</strong></td>
          <td class="wh-btn-row">
            <button type="button" class="wh-btn-sm" onclick="completeCoins('${esc(c.public_id)}')">Mark loaded</button>
            <button type="button" class="wh-btn-sm ghost" onclick="holdCoins('${esc(c.public_id)}')">Hold</button>
          </td>
        </tr>`).join('');
    } catch (_) {}
  }

  async function softRefreshRequests() {
    try {
      const d = await WH.api('/account-requests?status=PENDING');
      const body = document.querySelector('#pane-requests tbody');
      if (!body) return;
      const items = (d.items || []).filter((r) => !completedActionIds[r.public_id]);
      body.innerHTML = items.length ? items.map((r) => `
        <tr data-tx-id="${esc(r.public_id)}">
          <td>${esc(r.user_email)}</td>
          <td>${esc(r.game_title || '')}</td>
          <td><input class="wh-inline-input" id="cred-user-${esc(r.public_id)}" placeholder="game username" autocomplete="off"></td>
          <td><input class="wh-inline-input" id="cred-pass-${esc(r.public_id)}" placeholder="game password" autocomplete="off"></td>
          <td class="wh-btn-row">
            <button type="button" class="wh-btn-sm" onclick="saveAccountCreds('${esc(r.public_id)}')">Save / Issue</button>
            <button type="button" class="wh-btn-sm danger" onclick="rejectAccount('${esc(r.public_id)}')">Reject</button>
          </td>
        </tr>`).join('') : '<tr><td colspan="5" style="color:var(--mute)">No pending requests</td></tr>';
    } catch (_) {}
  }

  async function softRefreshGames() {
    try {
      const d = await WH.api('/games');
      const body = document.querySelector('#pane-games tbody');
      if (!body) return;
      const games = d.games || d.items || [];
      if (!games.length) {
        body.innerHTML = '<tr><td colspan="6" style="color:var(--mute)">No games</td></tr>';
        return;
      }
      body.innerHTML = games.map(g => `
        <tr>
          <td>${esc(g.title)}</td>
          <td><span class="wh-badge">${esc(g.badge || 'none')}</span></td>
          <td>${esc(g.available_coins ?? g.availableCoins ?? 0)}</td>
          <td>${esc(g.used_coins ?? g.usedCoins ?? 0)}</td>
          <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis">${esc(g.link || '')}</td>
          <td class="wh-btn-row">
            <button type="button" class="wh-btn-sm" onclick='openEditGameModal(${JSON.stringify(g)})'>Edit Game</button>
            <button type="button" class="wh-btn-sm ghost" onclick="editGameCoins('${esc(g.public_id)}', '${esc(g.available_coins)}')">Edit pool</button>
            <button type="button" class="wh-btn-sm danger" onclick="deleteGame('${esc(g.public_id)}', '${esc(g.title)}')">Delete</button>
          </td>
        </tr>
      `).join('');
    } catch (_) {}
  }

  async function softRefreshGateways() {
    try {
      const d = await WH.api('/gateways');
      const body = document.querySelector('#pane-gateways tbody');
      if (!body) return;
      const items = d.gateways || d.items || [];
      if (!items.length) {
        body.innerHTML = '<tr><td colspan="5" style="color:var(--mute)">No gateways</td></tr>';
        return;
      }
      body.innerHTML = items.map(gw => `
        <tr>
          <td><strong>${esc(gw.name || gw.theme)}</strong></td>
          <td><span class="wh-badge">${esc(gw.theme)}</span></td>
          <td>${esc(gw.tag || 'link')}</td>
          <td>${gw.is_withdraw_active ? '<span class="wh-badge">Yes</span>' : '<span class="wh-badge" style="opacity:.6">No</span>'}</td>
          <td class="wh-btn-row">
            <button type="button" class="wh-btn-sm ghost" onclick='openEditGwModal(${JSON.stringify(gw)})'>Edit</button>
            <button type="button" class="wh-btn-sm danger" onclick="deleteGateway('${esc(gw.public_id)}', '${esc(gw.name || gw.theme)}')">Delete</button>
          </td>
        </tr>
      `).join('');
    } catch (_) {}
  }

  window.saveAccountCreds = async function (id) {
    const username = (document.getElementById('cred-user-' + id)?.value || '').trim();
    const password = (document.getElementById('cred-pass-' + id)?.value || '').trim();
    if (!username || !password) {
      WH.toast('Fill both Username and Password', 'error');
      return;
    }
    removeTxRow(id);
    await run('Account issued', () =>
      WH.api('/account-requests/' + id, {
        method: 'PATCH',
        body: JSON.stringify({
          status: 'READY',
          game_account_username: username,
          game_account_password: password
        })
      }),
      { removeId: id, refresh: 'requests' }
    );
  };

  function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
  }

  function filterLedgerQueue() {
    const q = (document.getElementById('ledgerQueueSearch')?.value || '').trim().toLowerCase();
    document.querySelectorAll('#ledgerDepBody tr[data-tx-id], #ledgerWdBody tr[data-tx-id]').forEach((tr) => {
      const hay = tr.getAttribute('data-search') || '';
      tr.style.display = !q || hay.includes(q) ? '' : 'none';
    });
  }
  document.getElementById('ledgerQueueSearch')?.addEventListener('input', filterLedgerQueue);

  async function run(label, fn, opts) {
    opts = opts || {};
    try {
      await fn();
      WH.toast(label);
      if (opts.removeId) removeTxRow(opts.removeId);
      refreshStats();
      if (opts.refresh === 'ledger') softRefreshLedger();
      if (opts.refresh === 'coins') softRefreshCoins();
      if (opts.refresh === 'requests') softRefreshRequests();
      if (opts.event) postAdminEvent(opts.event.type, opts.event);
      // No location.reload — stay on current tab (Jackpot parity)
    } catch (e) {
      if (opts.removeId) delete completedActionIds[opts.removeId];
      WH.toast(e.message || 'Action failed', 'error');
    }
  }

  window.approveDeposit = async function (id) {
    const ok = await WH.confirm('Approve this deposit? Coins task will be created.', 'Approve deposit');
    if (!ok) return;
    removeTxRow(id);
    await run('Deposit approved', () =>
      WH.api('/transactions/' + id, { method: 'PATCH', body: JSON.stringify({ status: 'SUCCESS' }) }),
      { removeId: id, refresh: 'ledger', event: { type: 'coins', transactionId: id } }
    );
  };

  window.rejectDeposit = async function (id) {
    const out = await WH.promptFields('Reject deposit', 'Optional note for the player / ledger.', [
      { id: 'note', label: 'Reason', type: 'textarea', placeholder: 'Payment proof invalid…', value: '' }
    ]);
    if (out === null) return;
    removeTxRow(id);
    await run('Deposit rejected', () =>
      WH.api('/transactions/' + id, {
        method: 'PATCH',
        body: JSON.stringify({ status: 'FAILED', note: out.note || 'Rejected' })
      }),
      { removeId: id, refresh: 'ledger' }
    );
  };

  window.markPaid = async function (id, amount) {
    const ok = await WH.confirm('Mark this withdrawal as fully paid ($' + Number(amount).toFixed(2) + ')?', 'Mark paid');
    if (!ok) return;
    removeTxRow(id);
    await run('Marked paid', () =>
      WH.api('/transactions/' + id, {
        method: 'PATCH',
        body: JSON.stringify({ status: 'SUCCESS', payout_sent: Number(amount) })
      }),
      { removeId: id, refresh: 'ledger' }
    );
  };

  window.partialPayout = async function (id, total) {
    const out = await WH.promptFields(
      'Process withdrawal payout',
      'Full or partial payout (Jackpot Ledger). Total requested: $' + Number(total).toFixed(2) + '. If hold > 0, set wait hours/minutes — player can claim remainder after the timer.',
      [
        { id: 'payout_sent', label: 'Amount sent now ($)', type: 'number', value: String(Number(total)) },
        { id: 'payout_hold', label: 'Amount on hold ($)', type: 'number', value: '0' },
        { id: 'remainder_wait_hours', label: 'Remainder wait (hours)', type: 'number', value: '0' },
        { id: 'remainder_wait_minutes', label: 'Remainder wait (minutes)', type: 'number', value: '0' },
        { id: 'note', label: 'Payout note', type: 'textarea', placeholder: 'Full payout processed…', value: 'Full payout processed' },
        { id: 'payout_proof', label: 'Payout proof URL / note *', placeholder: 'receipt screenshot URL or note' }
      ]
    );
    if (out === null) return;
    if (!String(out.payout_proof || '').trim()) {
      WH.toast('Payout proof is required (same as Jackpot)', 'error');
      return;
    }
    const body = {
      status: 'SUCCESS',
      payout_sent: Number(out.payout_sent || 0),
      payout_hold: Number(out.payout_hold || 0),
      remainder_wait_hours: parseInt(out.remainder_wait_hours || '0', 10) || 0,
      remainder_wait_minutes: parseInt(out.remainder_wait_minutes || '0', 10) || 0,
      note: out.note || undefined,
      payout_proof: out.payout_proof || undefined
    };
    removeTxRow(id);
    await run('Payout saved', () =>
      WH.api('/transactions/' + id, { method: 'PATCH', body: JSON.stringify(body) }),
      { removeId: id, refresh: 'ledger' }
    );
  };

  window.failWithdraw = async function (id) {
    const out = await WH.promptFields('Fail withdrawal', 'Note for the player / ledger.', [
      { id: 'note', label: 'Reason', type: 'textarea', placeholder: 'Invalid tag / insufficient playthrough…', value: '' }
    ]);
    if (out === null) return;
    removeTxRow(id);
    await run('Withdrawal failed', () =>
      WH.api('/transactions/' + id, {
        method: 'PATCH',
        body: JSON.stringify({ status: 'FAILED', note: out.note || 'Failed' })
      }),
      { removeId: id, refresh: 'ledger' }
    );
  };

  window.completeCoins = async function (id) {
    const ok = await WH.confirm('Mark this coins job as completed / loaded?', 'Complete coins');
    if (!ok) return;
    removeTxRow(id);
    await run('Coins marked loaded', () =>
      WH.api('/coins-notifications/' + id, { method: 'PATCH', body: JSON.stringify({ status: 'COMPLETED' }) }),
      { removeId: id, refresh: 'coins', event: { type: 'transactions' } }
    );
  };

  window.holdCoins = async function (id) {
    const out = await WH.promptFields('Hold coins task', 'Explain why this allotment is on hold.', [
      { id: 'hold_note', label: 'Hold note', type: 'textarea', placeholder: 'Waiting for game ID / pool refill…', value: '' }
    ]);
    if (out === null) return;
    removeTxRow(id);
    await run('Coins on hold', () =>
      WH.api('/coins-notifications/' + id, {
        method: 'PATCH',
        body: JSON.stringify({ status: 'HOLD', hold_note: out.hold_note || 'On hold' })
      }),
      { removeId: id, refresh: 'coins' }
    );
  };

  window.issueAccount = async function (id) {
    const out = await WH.promptFields(
      'Issue game credentials',
      'Create the in-game username & password for this player (same as Jackpot Shift / Requests approve). Player will see Email · Username · Password in lobby.',
      [
        { id: 'username', label: 'Game username *', placeholder: 'e.g. wh_player_01' },
        { id: 'password', label: 'Game password *', type: 'text', placeholder: 'temp password for player' }
      ]
    );
    if (out === null) return;
    if (!out.username?.trim() || !out.password?.trim()) {
      WH.toast('Username and password required', 'error');
      return;
    }
    removeTxRow(id);
    await run('Account issued', () =>
      WH.api('/account-requests/' + id, {
        method: 'PATCH',
        body: JSON.stringify({
          status: 'READY',
          game_account_username: out.username.trim(),
          game_account_password: out.password.trim()
        })
      }),
      { removeId: id, refresh: 'requests' }
    );
  };

  window.rejectAccount = async function (id) {
    const out = await WH.promptFields('Reject account request', 'Optional note for the player.', [
      { id: 'note', label: 'Reason', type: 'textarea', placeholder: 'Unable to create account…', value: '' }
    ]);
    if (out === null) return;
    const note = out.note || 'Rejected';
    removeTxRow(id);
    await run('Request rejected', () =>
      WH.api('/account-requests/' + id, {
        method: 'PATCH',
        body: JSON.stringify({ status: 'REJECTED', rejection_reason: note })
      }),
      { removeId: id, refresh: 'requests' }
    );
  };

  window.editGameCoins = async function (id, current) {
    const val = await WH.prompt('New available_coins pool', String(current), 'Edit game pool');
    if (val == null || val === '') return;
    await run('Game pool updated', () =>
      WH.api('/games/' + id, { method: 'PATCH', body: JSON.stringify({ available_coins: Number(val) }) })
    );
  };

  window.updatePoolFromDash = async function (id, title, current) {
    const out = await WH.promptFields('Update pool — ' + title, 'Set available_coins for this game.', [
      { id: 'available_coins', label: 'Available coins', type: 'number', value: String(current) }
    ]);
    if (!out || out.available_coins === '' || out.available_coins == null) return;
    await run('Pool updated', () =>
      WH.api('/games/' + id, {
        method: 'PATCH',
        body: JSON.stringify({ available_coins: Number(out.available_coins) })
      })
    );
  };

  window.openShotLightbox = function (src) {
    const box = document.getElementById('shotLightbox');
    const img = document.getElementById('shotLightboxImg');
    if (!box || !img || !src) return;
    img.src = src;
    box.classList.add('is-on');
  };

  function readAdminFile(file) {
    return new Promise((resolve, reject) => {
      if (!file) return reject(new Error('No file'));
      if (file.size > 3 * 1024 * 1024) return reject(new Error('Image must be under 3MB'));
      const reader = new FileReader();
      reader.onload = () => resolve(reader.result);
      reader.onerror = () => reject(new Error('Could not read file'));
      reader.readAsDataURL(file);
    });
  }

  function openDeskModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('is-on');
    el.setAttribute('aria-hidden', 'false');
  }
  function closeDeskModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('is-on');
    el.setAttribute('aria-hidden', 'true');
  }
  document.querySelectorAll('[data-close-modal]').forEach((btn) => {
    btn.addEventListener('click', () => closeDeskModal(btn.getAttribute('data-close-modal')));
  });
  document.querySelectorAll('.wh-desk-modal').forEach((m) => {
    m.addEventListener('click', (e) => { if (e.target === m) closeDeskModal(m.id); });
  });

  function syncGwThemeUi() {
    const theme = (document.querySelector('#gwForm [name=theme]:checked') || {}).value || 'chime';
    const linkPay = theme === 'cashapp' || theme === 'stripe';
    document.getElementById('gwRedirectWrap').style.display = linkPay ? 'block' : 'none';
    document.getElementById('gwTagWrap').style.display = linkPay ? 'none' : 'block';
    document.getElementById('gwPhoneWrap').style.display = linkPay ? 'none' : 'block';
    document.getElementById('gwQrUpload').style.display = linkPay ? 'none' : 'block';
    document.querySelectorAll('.wh-theme-opt').forEach((lab) => {
      lab.classList.toggle('is-on', lab.getAttribute('data-theme') === theme);
    });
  }
  document.querySelectorAll('#gwForm [name=theme]').forEach((r) => r.addEventListener('change', syncGwThemeUi));
  document.getElementById('gwWithdrawActive')?.addEventListener('change', (e) => {
    document.getElementById('gwRequireWrap').style.display = e.target.checked ? 'grid' : 'none';
  });

  document.getElementById('openGameModalBtn')?.addEventListener('click', () => {
    document.getElementById('gameForm')?.reset();
    document.getElementById('gameImageField').value = '';
    const prev = document.getElementById('gameImagePreview');
    if (prev) { prev.style.display = 'none'; prev.removeAttribute('src'); }
    openDeskModal('gameCreateModal');
  });
  document.getElementById('openGwModalBtn')?.addEventListener('click', () => {
    document.getElementById('gwForm')?.reset();
    document.getElementById('gwEditId').value = '';
    document.getElementById('gwModalTitle').textContent = 'Create Gateway';
    document.getElementById('gwQrField').value = '';
    const prev = document.getElementById('gwQrPreview');
    if (prev) { prev.style.display = 'none'; prev.removeAttribute('src'); }
    const chime = document.querySelector('#gwForm [name=theme][value=chime]');
    if (chime) chime.checked = true;
    document.getElementById('gwWithdrawActive').checked = true;
    document.getElementById('gwRequireWrap').style.display = 'grid';
    syncGwThemeUi();
    openDeskModal('gwCreateModal');
  });

  const gameImageFile = document.getElementById('gameImageFile');
  if (gameImageFile) {
    gameImageFile.onchange = async (e) => {
      try {
        const dataUrl = await readAdminFile(e.target.files?.[0]);
        const field = document.getElementById('gameImageField');
        if (field) field.value = dataUrl;
        const prev = document.getElementById('gameImagePreview');
        if (prev) { prev.src = dataUrl; prev.style.display = 'block'; }
        WH.toast('Game image ready');
      } catch (err) {
        WH.toast(err.message || 'Upload failed', 'error');
        e.target.value = '';
      }
    };
  }

  const gwQrFile = document.getElementById('gwQrFile');
  if (gwQrFile) {
    gwQrFile.onchange = async (e) => {
      try {
        const dataUrl = await readAdminFile(e.target.files?.[0]);
        const field = document.getElementById('gwQrField');
        if (field) field.value = dataUrl;
        const prev = document.getElementById('gwQrPreview');
        if (prev) { prev.src = dataUrl; prev.style.display = 'block'; }
        WH.toast('Gateway QR ready');
      } catch (err) {
        WH.toast(err.message || 'Upload failed', 'error');
        e.target.value = '';
      }
    };
  }

  const shiftReportForm = document.getElementById('shiftReportForm');
  if (shiftReportForm) {
    shiftReportForm.onsubmit = async (e) => {
      e.preventDefault();
      const body = Object.fromEntries(new FormData(e.target));
      if (body.total_loaded !== undefined && body.total_loaded !== '') {
        body.total_loaded = Number(body.total_loaded);
      } else {
        delete body.total_loaded;
      }
      await run('Shift report saved', () =>
        WH.api('/admin/shift-reports', { method: 'POST', body: JSON.stringify(body) })
      );
    };
  }

  const dashStatsLoad = document.getElementById('dashStatsLoad');
  async function loadDashDateStats() {
    const date = document.getElementById('dashStatsDate')?.value || '';
    try {
      const d = await WH.api('/admin/stats/by-date' + (date ? ('?date=' + encodeURIComponent(date)) : ''));
      const depEl = document.getElementById('dashDateDeps');
      const wdEl = document.getElementById('dashDateWds');
      if (depEl) depEl.textContent = '$' + Number(d.deposits || 0).toFixed(2);
      if (wdEl) wdEl.textContent = '$' + Number(d.withdrawals || 0).toFixed(2);
    } catch (err) {
      WH.toast(err.message || 'Date stats failed', 'error');
    }
  }
  if (dashStatsLoad) dashStatsLoad.onclick = loadDashDateStats;
  if (document.getElementById('dashStatsDate')) loadDashDateStats();

  window.setPlayerStatus = async function (id, status) {
    const ok = await WH.confirm(
      status === 'suspended' ? 'Suspend this player?' : 'Reactivate this player?',
      'Player status'
    );
    if (!ok) return;
    await run(status === 'suspended' ? 'Player suspended' : 'Player reactivated', () =>
      WH.api('/users/' + id, { method: 'PATCH', body: JSON.stringify({ status }) })
    );
  };

  window.deleteUser = async function (id, email) {
    const ok = await WH.confirm('Delete account for ' + email + '?', 'Delete user');
    if (!ok) return;
    await run('User deleted', () => WH.api('/users/' + id, { method: 'DELETE' }));
  };

  window.resetPlayerPassword = async function (id, email) {
    const out = await WH.promptFields('Reset password — ' + email, 'Enter a new password (min 6 chars).', [
      { id: 'password', label: 'New password', type: 'text', value: 'reset123' }
    ]);
    if (!out || !out.password || out.password.length < 6) {
      if (out) WH.toast('Password must be at least 6 characters', 'error');
      return;
    }
    await run('Password reset', () =>
      WH.api('/users/' + id, { method: 'PATCH', body: JSON.stringify({ password: out.password }) })
    );
  };

  window.deleteGame = async function (id, title) {
    const ok = await WH.confirm('Delete game "' + title + '"?', 'Delete game');
    if (!ok) return;
    await run('Game deleted', () => WH.api('/games/' + id, { method: 'DELETE' }));
  };

  window.deleteGateway = async function (id) {
    const ok = await WH.confirm('Delete this payment gateway?', 'Delete gateway');
    if (!ok) return;
    await run('Gateway deleted', () => WH.api('/gateways/' + id, { method: 'DELETE' }));
  };

  window.editGatewayById = function (id) {
    const gw = GW_MAP[id];
    if (!gw) return WH.toast('Gateway not found — refresh page', 'error');
    document.getElementById('gwModalTitle').textContent = 'Edit Gateway';
    document.getElementById('gwEditId').value = gw.public_id || '';
    const form = document.getElementById('gwForm');
    if (!form) return;
    form.name.value = gw.name || '';
    form.subtitle.value = gw.subtitle || '';
    form.tag.value = gw.tag || '';
    form.phone.value = gw.phone || '';
    form.redirect_url.value = gw.redirect_url || '';
    document.getElementById('gwQrField').value = gw.qr_image || '';
    const prev = document.getElementById('gwQrPreview');
    if (prev && gw.qr_image) { prev.src = gw.qr_image; prev.style.display = 'block'; }
    else if (prev) { prev.style.display = 'none'; prev.removeAttribute('src'); }
    const theme = gw.theme || 'chime';
    const radio = form.querySelector('[name=theme][value="' + theme + '"]');
    if (radio) radio.checked = true;
    document.getElementById('gwWithdrawActive').checked = !!gw.is_withdraw_active;
    form.require_name_on_tag.checked = gw.require_name_on_tag !== false;
    form.require_tag.checked = gw.require_tag !== false;
    form.require_phone_on_tag.checked = gw.require_phone_on_tag !== false;
    form.require_email_on_tag.checked = !!gw.require_email_on_tag;
    document.getElementById('gwRequireWrap').style.display = gw.is_withdraw_active ? 'grid' : 'none';
    syncGwThemeUi();
    openDeskModal('gwCreateModal');
  };

  window.deleteDistributor = async function (id, name) {
    const ok = await WH.confirm('Delete distributor "' + name + '"?', 'Delete distributor');
    if (!ok) return;
    await run('Distributor deleted', () => WH.api('/distributors/' + id, { method: 'DELETE' }));
  };

  window.deleteAgent = async function (id, name) {
    const ok = await WH.confirm('Delete affiliate "' + name + '"?', 'Delete affiliate');
    if (!ok) return;
    await run('Affiliate deleted', () => WH.api('/agents/' + id, { method: 'DELETE' }));
  };

  window.deletePromo = async function (id) {
    const ok = await WH.confirm('Delete this promotion?', 'Delete promotion');
    if (!ok) return;
    await run('Promotion deleted', () => WH.api('/promotions/' + id, { method: 'DELETE' }));
  };

  window.restoreDeleted = async function (email) {
    const ok = await WH.confirm('Restore ' + email + '?', 'Restore player');
    if (!ok) return;
    await run('Player restored', () =>
      WH.api('/admin/deleted-players/' + encodeURIComponent(email) + '/restore', { method: 'POST', body: '{}' })
    );
  };

  window.approveTx = async function (id, status) {
    const ok = await WH.confirm('Set transaction to ' + status + '?', 'Update transaction');
    if (!ok) return;
    await run('Transaction updated', () =>
      WH.api('/transactions/' + id, { method: 'PATCH', body: JSON.stringify({ status }) })
    );
  };

  window.partialPay = async function (id) {
    return window.partialPayout(id, 0);
  };

  window.approveCampaign = async function (id) {
    const out = await WH.promptFields('Approve campaign', 'Optional tracking link for the agent.', [
      { id: 'tracking_link', label: 'Tracking link (optional)', placeholder: 'https://…' }
    ]);
    if (out === null) return;
    await run('Campaign approved', () =>
      WH.api('/campaign-requests/' + id, {
        method: 'PATCH',
        body: JSON.stringify({
          status: 'APPROVED',
          tracking_link: (out.tracking_link || '').trim() || undefined
        })
      })
    );
  };

  window.rejectCampaign = async function (id) {
    const ok = await WH.confirm('Reject this campaign request?', 'Reject campaign');
    if (!ok) return;
    await run('Campaign rejected', () =>
      WH.api('/campaign-requests/' + id, {
        method: 'PATCH',
        body: JSON.stringify({ status: 'REJECTED' })
      })
    );
  };

  const gameForm = document.getElementById('gameForm');
  if (gameForm) {
    gameForm.onsubmit = async (e) => {
      e.preventDefault();
      const body = Object.fromEntries(new FormData(e.target));
      body.available_coins = Number(body.available_coins || 10000);
      body.badge = body.badge || 'none';
      if (!String(body.title || '').trim()) {
        WH.toast('Game title required', 'error');
        return;
      }
      if (!String(body.link || '').trim()) {
        WH.toast('Play link required', 'error');
        return;
      }
      if (!String(body.image || '').trim()) {
        WH.toast('Upload a game cover image', 'error');
        return;
      }
      try {
        await WH.api('/games', { method: 'POST', body: JSON.stringify(body) });
        WH.toast('Game added');
        closeDeskModal('gameCreateModal');
        softRefreshGames();
      } catch (err) {
        WH.toast(err.message || 'Failed', 'error');
      }
    };
  }

  window.openEditGameModal = function(game) {
    document.getElementById('editGamePublicId').value = game.public_id || '';
    document.getElementById('editGameTitle').value = game.title || '';
    document.getElementById('editGameBadge').value = game.badge || 'none';
    document.getElementById('editGameLink').value = game.link || '';
    document.getElementById('editGameOpenPanelLink').value = game.open_panel_link || '';
    document.getElementById('editGameCoins').value = game.available_coins || 0;
    document.getElementById('editGameImageField').value = game.image || '';
    const prev = document.getElementById('editGameImagePreview');
    if (game.image) {
      prev.src = game.image;
      prev.style.display = 'block';
    } else {
      prev.style.display = 'none';
    }
    openDeskModal('gameEditModal');
  };

  document.getElementById('editGameImageFile')?.addEventListener('change', async (e) => {
    try {
      const dataUrl = await readFileAsDataUrl(e.target.files?.[0]);
      document.getElementById('editGameImageField').value = dataUrl;
      const prev = document.getElementById('editGameImagePreview');
      if (prev) { prev.src = dataUrl; prev.style.display = 'block'; }
    } catch (err) { WH.toast(err.message || 'Upload failed', 'error'); }
  });

  const gameEditForm = document.getElementById('gameEditForm');
  if (gameEditForm) {
    gameEditForm.onsubmit = async (e) => {
      e.preventDefault();
      const publicId = document.getElementById('editGamePublicId').value;
      const body = Object.fromEntries(new FormData(e.target));
      body.available_coins = Number(body.available_coins || 0);
      body.badge = body.badge || 'none';
      if (!String(body.title || '').trim()) return WH.toast('Title required', 'error');
      if (!String(body.link || '').trim()) return WH.toast('Link required', 'error');

      const submitBtn = e.target.querySelector('button[type="submit"]');
      WH.setBtnLoading(submitBtn, true, 'UPDATING…');
      try {
        await WH.api('/games/' + publicId, { method: 'PATCH', body: JSON.stringify(body) });
        WH.toast('Game updated successfully');
        closeDeskModal('gameEditModal');
        softRefreshGames();
      } catch (err) {
        WH.setBtnLoading(submitBtn, false);
        WH.toast(err.message || 'Could not update game', 'error');
      }
    };
  }

  const gwForm = document.getElementById('gwForm');
  if (gwForm) {
    gwForm.onsubmit = async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const body = Object.fromEntries(fd.entries());
      ['is_withdraw_active','require_name_on_tag','require_tag','require_phone_on_tag','require_email_on_tag'].forEach((k) => {
        body[k] = fd.get(k) === '1' || fd.get(k) === 'on';
      });
      const theme = body.theme || 'chime';
      const linkPay = theme === 'cashapp' || theme === 'stripe';
      if (linkPay) {
        if (!String(body.redirect_url || '').trim()) {
          WH.toast('Pay redirect URL required for Cash App / Stripe', 'error');
          return;
        }
        body.tag = String(body.tag || '').trim() || (theme + '-pay');
        body.phone = '';
        body.qr_image = '';
      } else {
        if (!String(body.tag || '').trim()) {
          WH.toast('Payment tag / address required', 'error');
          return;
        }
        if (!String(body.qr_image || '').trim()) {
          WH.toast('Upload QR image for this gateway', 'error');
          return;
        }
      }
      if (!body.is_withdraw_active) {
        body.require_name_on_tag = false;
        body.require_tag = false;
        body.require_phone_on_tag = false;
        body.require_email_on_tag = false;
      }
      const editId = document.getElementById('gwEditId')?.value || '';
      try {
        if (editId) {
          await WH.api('/gateways/' + editId, { method: 'PATCH', body: JSON.stringify(body) });
          WH.toast('Gateway updated');
        } else {
          await WH.api('/gateways', { method: 'POST', body: JSON.stringify(body) });
          WH.toast('Gateway added');
        }
        closeDeskModal('gwCreateModal');
        softRefreshGateways();
      } catch (err) {
        WH.toast(err.message || 'Failed', 'error');
      }
    };
  }

  const distForm = document.getElementById('distForm');
  if (distForm) {
    distForm.onsubmit = async (e) => {
      e.preventDefault();
      const body = Object.fromEntries(new FormData(e.target));
      body.commission_rate = Number(body.commission_rate || 0);
      body.website_commission_rate = Number(body.website_commission_rate || 0);
      await run('Distributor created', () =>
        WH.api('/distributors', { method: 'POST', body: JSON.stringify(body) })
      );
    };
  }

  const agentForm = document.getElementById('agentForm');
  if (agentForm) {
    agentForm.onsubmit = async (e) => {
      e.preventDefault();
      const body = Object.fromEntries(new FormData(e.target));
      body.commission_rate = Number(body.commission_rate || 0);
      await run('Agent created', () =>
        WH.api('/agents', { method: 'POST', body: JSON.stringify(body) })
      );
    };
  }

  const playerCreateForm = document.getElementById('playerCreateForm');
  if (playerCreateForm) {
    playerCreateForm.onsubmit = async (e) => {
      e.preventDefault();
      const body = Object.fromEntries(new FormData(e.target));
      body.role = 'user';
      await run('Player created', () => WH.api('/users', { method: 'POST', body: JSON.stringify(body) }));
    };
  }

  const promoForm = document.getElementById('promoForm');
  if (promoForm) {
    promoForm.onsubmit = async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const body = Object.fromEntries(fd.entries());
      body.freeplay_amount = Number(body.freeplay_amount || 0);
      body.bonus_percent = Number(body.bonus_percent || 0);
      body.send_email = fd.get('send_email') === '1';
      body.send_push = fd.get('send_push') === '1';
      try {
        const r = await WH.api('/promotions', { method: 'POST', body: JSON.stringify(body) });
        const reach = r.reach || {};
        WH.toast('Promo created · players ' + (reach.players || 0) + ' · emailed ' + (reach.emailed || 0));
        promoForm.reset();
      } catch (err) {
        WH.toast(err.message || 'Failed', 'error');
      }
    };
  }

  const staffForm = document.getElementById('staffForm');
  if (staffForm) {
    staffForm.onsubmit = async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const roles = fd.getAll('roles[]').filter(Boolean);
      const allowed_game_ids = fd.getAll('allowed_game_ids[]').filter(Boolean);
      const body = {
        name: fd.get('name'),
        email: fd.get('email'),
        password: fd.get('password'),
        role: roles[0] || 'support_admin',
        roles,
        allowed_game_ids
      };
      if (!roles.length) {
        WH.toast('Select at least one role', 'error');
        return;
      }
      await run('Staff created', () => WH.api('/users', { method: 'POST', body: JSON.stringify(body) }));
    };
  }

  const settingsForm = document.getElementById('settingsForm');
  if (settingsForm) {
    settingsForm.onsubmit = async (e) => {
      e.preventDefault();
      const body = Object.fromEntries(new FormData(e.target));
      const numericKeys = [
        'first_deposit_bonus','regular_deposit_bonus','referral_bonus','signup_freeplay',
        'freeplay_cashout_cap','freeplay_min_request','repeat_freeplay_deposit_threshold',
        'affiliate_platform_commission_rate','ad_budget_limit'
      ];
      numericKeys.forEach((k) => { if (body[k] !== undefined && body[k] !== '') body[k] = Number(body[k]); });
      try {
        await WH.api('/settings/global', { method: 'PUT', body: JSON.stringify(body) });
        WH.toast('Settings saved');
      } catch (err) {
        WH.toast(err.message || 'Save failed', 'error');
      }
    };
  }

  const usdtQrFile = document.getElementById('usdtQrFile');
  if (usdtQrFile) {
    usdtQrFile.onchange = async (e) => {
      try {
        const dataUrl = await readAdminFile(e.target.files?.[0]);
        const field = document.getElementById('usdtQrField');
        if (field) field.value = dataUrl;
        const prev = document.getElementById('usdtQrPreview');
        if (prev) { prev.src = dataUrl; prev.style.display = 'block'; }
        WH.toast('USDT QR ready');
      } catch (err) {
        WH.toast(err.message || 'Upload failed', 'error');
        e.target.value = '';
      }
    };
  }

  function applyFrontendSettings(s) {
    if (!s) return;
  const feMap = {
      landing_welcome: 'fe_landing_welcome',
      landing_grab: 'fe_landing_grab',
      lobby_hero_promo: 'fe_lobby_hero_promo',
      lobby_trust_badge_1: 'fe_lobby_trust_badge_1',
      lobby_trust_badge_2: 'fe_lobby_trust_badge_2',
      lobby_trust_badge_3: 'fe_lobby_trust_badge_3',
      lobby_freeplay_value: 'fe_lobby_freeplay_value',
      lobby_freeplay_label: 'fe_lobby_freeplay_label',
      lobby_freeplay_condition: 'fe_lobby_freeplay_condition',
      minimum_deposit_limit: 'fe_minimum_deposit_limit',
      minimum_withdrawal_limit: 'fe_minimum_withdrawal_limit',
      withdraw_notice: 'fe_withdraw_notice',
      cashout_notice: 'fe_cashout_notice',
      android_app_url: 'fe_android_app_url',
      ios_app_url: 'fe_ios_app_url',
      get_app_enabled: 'fe_get_app_enabled',
      withdraw_require_game_screenshot: 'fe_withdraw_require_game_screenshot',
      withdraw_require_tag_qr_screenshot: 'fe_withdraw_require_tag_qr_screenshot',
      notification_sound_url: 'fe_notification_sound_url',
      logo_url: 'fe_logo_url',
      login_bg_url: 'fe_login_bg_url',
      lobby_hero_side_image: 'fe_lobby_hero_side_image'
    };
    Object.keys(feMap).forEach((k) => {
      const el = document.getElementById(feMap[k]);
      if (!el || s[k] == null) return;
      if (el.tagName === 'SELECT') {
        el.value = (s[k] === true || s[k] === 1 || s[k] === '1') ? '1' : (s[k] === false || s[k] === 0 || s[k] === '0' ? '0' : String(s[k]));
      } else {
        el.value = s[k];
      }
    });
  }

  const feReloadBtn = document.getElementById('feReloadBtn');
  if (feReloadBtn) {
    feReloadBtn.onclick = async () => {
      try {
        const d = await WH.api('/settings/frontend');
        applyFrontendSettings(d.settings || d);
        WH.toast('Frontend settings loaded');
      } catch (err) {
        WH.toast(err.message || 'Load failed', 'error');
      }
    };
  }

  document.getElementById('feSoundTestBtn')?.addEventListener('click', () => {
    const url = document.getElementById('fe_notification_sound_url')?.value || '';
    WH.notificationSoundUrl = url;
    WH.playNotificationSound(url);
    WH.toast('Playing notification sound', 'info');
  });
  document.getElementById('feSoundFile')?.addEventListener('change', async (e) => {
    try {
      const file = e.target.files?.[0];
      if (!file) return;
      if (file.size > 2 * 1024 * 1024) throw new Error('Sound must be under 2MB');
      const dataUrl = await readAdminFile(file);
      const field = document.getElementById('fe_notification_sound_url');
      if (field) field.value = dataUrl.replace(/^data:video\/[^;]+;/, 'data:audio/mpeg;');
      WH.toast('Sound file loaded — save frontend to keep it');
    } catch (err) {
      WH.toast(err.message || 'Upload failed', 'error');
      e.target.value = '';
    }
  });

  const frontendForm = document.getElementById('frontendForm');
  if (frontendForm) {
    frontendForm.onsubmit = async (e) => {
      e.preventDefault();
      try {
        const body = Object.fromEntries(new FormData(e.target));
        if (body.minimum_deposit_limit !== undefined) body.minimum_deposit_limit = Number(body.minimum_deposit_limit);
        if (body.minimum_withdrawal_limit !== undefined) body.minimum_withdrawal_limit = Number(body.minimum_withdrawal_limit);
        ['get_app_enabled', 'withdraw_require_game_screenshot', 'withdraw_require_tag_qr_screenshot', 'lobby_hero_side_enabled', 'info_page_enabled'].forEach((k) => {
          if (body[k] !== undefined) body[k] = body[k] === '1' || body[k] === 1 || body[k] === true || body[k] === 'true';
        });
        [['marquee_payouts_json','marquee_payouts'],['cashout_rules_json','cashout_rules'],['lobby_cashout_trust_items_json','lobby_cashout_trust_items'],['proof_screenshots_json','proof_screenshots']].forEach(([from, to]) => {
          if (body[from] !== undefined) {
            try { body[to] = JSON.parse(body[from] || '[]'); } catch (_) { body[to] = []; }
            delete body[from];
          }
        });
        await WH.api('/settings/frontend', { method: 'PUT', body: JSON.stringify(body) });
        WH.toast('Frontend saved');
      } catch (err) {
        WH.toast(err.message || 'Save failed', 'error');
      }
    };
  }

  const feLogoFile = document.getElementById('feLogoFile');
  if (feLogoFile) {
    feLogoFile.onchange = async (e) => {
      try {
        const dataUrl = await readAdminFile(e.target.files?.[0]);
        const field = document.getElementById('fe_logo_url');
        if (field) field.value = dataUrl;
        const prev = document.getElementById('feLogoPreview');
        if (prev) { prev.src = dataUrl; prev.style.display = 'block'; }
        WH.toast('Logo ready');
      } catch (err) {
        WH.toast(err.message || 'Upload failed', 'error');
        e.target.value = '';
      }
    };
  }

  const feBgFile = document.getElementById('feBgFile');
  if (feBgFile) {
    feBgFile.onchange = async (e) => {
      try {
        const dataUrl = await readAdminFile(e.target.files?.[0]);
        const field = document.getElementById('fe_login_bg_url');
        if (field) field.value = dataUrl;
        const prev = document.getElementById('feBgPreview');
        if (prev) { prev.src = dataUrl; prev.style.display = 'block'; }
        WH.toast('Login BG ready');
      } catch (err) {
        WH.toast(err.message || 'Upload failed', 'error');
        e.target.value = '';
      }
    };
  }

  const feFaviconFile = document.getElementById('feFaviconFile');
  if (feFaviconFile) {
    feFaviconFile.onchange = async (e) => {
      try {
        const dataUrl = await readAdminFile(e.target.files?.[0]);
        const field = document.getElementById('fe_favicon_url');
        if (field) field.value = dataUrl;
        const prev = document.getElementById('feFaviconPreview');
        if (prev) { prev.src = dataUrl; prev.style.display = 'block'; }
        WH.toast('Favicon ready');
      } catch (err) {
        WH.toast(err.message || 'Upload failed', 'error');
        e.target.value = '';
      }
    };
  }

  // --- Simple CMS Builders Sync ---
  function initCMSBuilders() {
    const marqueeContainer = document.getElementById('marqueePayoutsContainer');
    const cashoutRulesContainer = document.getElementById('cashoutRulesContainer');
    const trustItemsContainer = document.getElementById('trustItemsContainer');

    if (!marqueeContainer) return;

    let payouts = [];
    let rules = [];
    let trust = [];

    try { payouts = JSON.parse(document.getElementById('fe_marquee_payouts_json')?.value || '[]'); } catch (_) {}
    try { rules = JSON.parse(document.getElementById('fe_cashout_rules_json')?.value || '[]'); } catch (_) {}
    try { trust = JSON.parse(document.getElementById('fe_lobby_cashout_trust_items_json')?.value || '[]'); } catch (_) {}

    if (!Array.isArray(payouts)) payouts = [];
    if (!Array.isArray(rules)) rules = [];
    if (!Array.isArray(trust)) trust = [];

    function renderMarquee() {
      marqueeContainer.innerHTML = '';
      payouts.forEach((item, idx) => {
        const div = document.createElement('div');
        div.style.cssText = 'display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;background:rgba(255,255,255,.04);padding:.5rem;border-radius:8px';
        div.innerHTML = `
          <input placeholder="Winner Name (e.g. Alex M.)" value="${e(item.name || '')}" data-idx="${idx}" data-field="name" style="flex:1;min-width:140px;background:rgba(0,0,0,.35);border:1px solid var(--line);color:#fff;border-radius:6px;padding:.4rem .6rem">
          <input placeholder="Amount (e.g. $250.00)" value="${e(item.amount || '')}" data-idx="${idx}" data-field="amount" style="width:110px;background:rgba(0,0,0,.35);border:1px solid var(--line);color:#fff;border-radius:6px;padding:.4rem .6rem">
          <input placeholder="Text (e.g. 5m ago)" value="${e(item.text || '')}" data-idx="${idx}" data-field="text" style="width:100px;background:rgba(0,0,0,.35);border:1px solid var(--line);color:#fff;border-radius:6px;padding:.4rem .6rem">
          <button type="button" class="wh-btn-sm danger" onclick="removeMarqueeRow(${idx})"><i class="fa-solid fa-trash"></i></button>
        `;
        marqueeContainer.appendChild(div);
      });
      syncJSON();
    }

    function renderRules() {
      cashoutRulesContainer.innerHTML = '';
      rules.forEach((item, idx) => {
        const title = typeof item === 'string' ? item : (item.title || item.rule || '');
        const desc = typeof item === 'object' ? (item.desc || item.text || '') : '';
        const div = document.createElement('div');
        div.style.cssText = 'display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;background:rgba(255,255,255,.04);padding:.5rem;border-radius:8px';
        div.innerHTML = `
          <input placeholder="Rule Title" value="${e(title)}" data-idx="${idx}" data-field="title" style="flex:1;min-width:160px;background:rgba(0,0,0,.35);border:1px solid var(--line);color:#fff;border-radius:6px;padding:.4rem .6rem">
          <input placeholder="Description (optional)" value="${e(desc)}" data-idx="${idx}" data-field="desc" style="flex:2;min-width:200px;background:rgba(0,0,0,.35);border:1px solid var(--line);color:#fff;border-radius:6px;padding:.4rem .6rem">
          <button type="button" class="wh-btn-sm danger" onclick="removeRuleRow(${idx})"><i class="fa-solid fa-trash"></i></button>
        `;
        cashoutRulesContainer.appendChild(div);
      });
      syncJSON();
    }

    function renderTrust() {
      trustItemsContainer.innerHTML = '';
      trust.forEach((item, idx) => {
        const div = document.createElement('div');
        div.style.cssText = 'display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;background:rgba(255,255,255,.04);padding:.5rem;border-radius:8px';
        div.innerHTML = `
          <input placeholder="Icon (e.g. fa-shield)" value="${e(item.icon || '')}" data-idx="${idx}" data-field="icon" style="width:110px;background:rgba(0,0,0,.35);border:1px solid var(--line);color:#fff;border-radius:6px;padding:.4rem .6rem">
          <input placeholder="Title" value="${e(item.title || '')}" data-idx="${idx}" data-field="title" style="flex:1;min-width:140px;background:rgba(0,0,0,.35);border:1px solid var(--line);color:#fff;border-radius:6px;padding:.4rem .6rem">
          <input placeholder="Desc" value="${e(item.desc || item.text || '')}" data-idx="${idx}" data-field="desc" style="flex:2;min-width:180px;background:rgba(0,0,0,.35);border:1px solid var(--line);color:#fff;border-radius:6px;padding:.4rem .6rem">
          <button type="button" class="wh-btn-sm danger" onclick="removeTrustRow(${idx})"><i class="fa-solid fa-trash"></i></button>
        `;
        trustItemsContainer.appendChild(div);
      });
      syncJSON();
    }

    function syncJSON() {
      document.getElementById('fe_marquee_payouts_json').value = JSON.stringify(payouts);
      document.getElementById('fe_cashout_rules_json').value = JSON.stringify(rules);
      document.getElementById('fe_lobby_cashout_trust_items_json').value = JSON.stringify(trust);
    }

    window.removeMarqueeRow = (idx) => { payouts.splice(idx, 1); renderMarquee(); };
    window.removeRuleRow = (idx) => { rules.splice(idx, 1); renderRules(); };
    window.removeTrustRow = (idx) => { trust.splice(idx, 1); renderTrust(); };

    document.getElementById('addMarqueePayoutBtn')?.addEventListener('click', () => {
      payouts.push({ name: 'Player ' + (payouts.length + 1), amount: '$100.00', text: 'Just now' });
      renderMarquee();
    });

    document.getElementById('addCashoutRuleBtn')?.addEventListener('click', () => {
      rules.push({ title: 'New Rule', desc: '' });
      renderRules();
    });

    document.getElementById('addTrustItemBtn')?.addEventListener('click', () => {
      trust.push({ icon: 'fa-shield-halved', title: 'Verified Payout', desc: '' });
      renderTrust();
    });

    marqueeContainer.addEventListener('input', (ev) => {
      const idx = ev.target.dataset.idx;
      const field = ev.target.dataset.field;
      if (idx !== undefined && field && payouts[idx]) {
        payouts[idx][field] = ev.target.value;
        syncJSON();
      }
    });

    cashoutRulesContainer.addEventListener('input', (ev) => {
      const idx = ev.target.dataset.idx;
      const field = ev.target.dataset.field;
      if (idx !== undefined && field) {
        if (!rules[idx] || typeof rules[idx] !== 'object') rules[idx] = { title: '', desc: '' };
        rules[idx][field] = ev.target.value;
        syncJSON();
      }
    });

    trustItemsContainer.addEventListener('input', (ev) => {
      const idx = ev.target.dataset.idx;
      const field = ev.target.dataset.field;
      if (idx !== undefined && field && trust[idx]) {
        trust[idx][field] = ev.target.value;
        syncJSON();
      }
    });

    renderMarquee();
    renderRules();
    renderTrust();
  }

  initCMSBuilders();

  function filterTxSearch() {
    const q = (document.getElementById('txSearchQ')?.value || '').trim().toLowerCase();
    const type = document.getElementById('txSearchType')?.value || '';
    const status = document.getElementById('txSearchStatus')?.value || '';
    document.querySelectorAll('#txSearchTable tbody tr[data-search]').forEach((tr) => {
      const okQ = !q || tr.dataset.search.includes(q);
      const okT = !type || tr.dataset.type === type;
      const okS = !status || tr.dataset.status === status;
      tr.style.display = okQ && okT && okS ? '' : 'none';
    });
  }
  ['txSearchQ', 'txSearchType', 'txSearchStatus'].forEach((id) => {
    const el = document.getElementById(id);
    if (el) {
      el.addEventListener('input', filterTxSearch);
      el.addEventListener('change', filterTxSearch);
    }
  });
  const txSearchFetch = document.getElementById('txSearchFetch');
  if (txSearchFetch) {
    txSearchFetch.onclick = async () => {
      try {
        const d = await WH.api('/transactions');
        const items = d.items || d.transactions || [];
        const tbody = document.querySelector('#txSearchTable tbody');
        if (!tbody) return;
        tbody.textContent = '';
        if (!items.length) {
          const tr = document.createElement('tr');
          tr.innerHTML = '<td colspan="7" style="color:var(--mute)">No transactions</td>';
          tbody.appendChild(tr);
          return;
        }
        items.forEach((tx) => {
          const tr = document.createElement('tr');
          const search = ((tx.user_email || '') + ' ' + (tx.game_title || '') + ' ' + (tx.code || '') + ' ' + (tx.gateway || '')).toLowerCase();
          tr.dataset.search = search;
          tr.dataset.type = tx.type || '';
          tr.dataset.status = tx.status || '';
          tr.innerHTML =
            '<td>' + (tx.type || '') + '</td>' +
            '<td>' + (tx.user_email || '') + '</td>' +
            '<td>$' + Number(tx.amount || 0).toFixed(2) + '</td>' +
            '<td><span class="wh-badge">' + (tx.status || '') + '</span></td>' +
            '<td>' + (tx.game_title || '') + '</td>' +
            '<td>' + (tx.code || '') + '</td>' +
            '<td style="font-size:.78rem;white-space:nowrap">' + (tx.created_at || '') + '</td>';
          tbody.appendChild(tr);
        });
        filterTxSearch();
        WH.toast('Transactions refreshed');
      } catch (err) {
        WH.toast(err.message || 'Fetch failed', 'error');
      }
    };
  }

  const sendPush = document.getElementById('sendPush');
  if (sendPush) {
    sendPush.onclick = async () => {
      const msg = document.getElementById('pushMsg');
      try {
        const r = await WH.api('/push/broadcast', {
          method: 'POST',
          body: JSON.stringify({
            title: document.getElementById('pushTitle').value,
            body: document.getElementById('pushBody').value
          })
        });
        if (msg) msg.textContent = r.message || 'Queued';
        WH.toast('Broadcast queued');
      } catch (e) {
        if (msg) msg.textContent = e.message;
        WH.toast(e.message || 'Push failed', 'error');
      }
    };
  }

  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.onclick = async () => {
      try {
        await WH.api('/auth/logout', { method: 'POST', body: '{}' });
      } catch (_) {}
      location.href = '/admin/login';
    };
  }

  const playerSearch = document.getElementById('playerSearch');
  if (playerSearch) {
    playerSearch.oninput = () => {
      const q = playerSearch.value.trim().toLowerCase();
      document.querySelectorAll('#playersTable tbody tr[data-search]').forEach((tr) => {
        tr.style.display = !q || tr.dataset.search.includes(q) ? '' : 'none';
      });
    };
  }

  function filterLedger() {
    const q = (document.getElementById('ledgerSearch')?.value || '').trim().toLowerCase();
    const type = document.getElementById('ledgerType')?.value || '';
    const status = document.getElementById('ledgerStatus')?.value || '';
    document.querySelectorAll('#ledgerTable tbody tr[data-search]').forEach((tr) => {
      const okQ = !q || tr.dataset.search.includes(q);
      const okT = !type || tr.dataset.type === type;
      const okS = !status || tr.dataset.status === status;
      tr.style.display = okQ && okT && okS ? '' : 'none';
    });
  }
  ['ledgerSearch', 'ledgerType', 'ledgerStatus'].forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', filterLedger);
    if (el) el.addEventListener('change', filterLedger);
  });

  const threadsEl = document.getElementById('supportThreadsJson');
  let supportThreads = [];
  if (threadsEl) {
    try { supportThreads = JSON.parse(threadsEl.textContent || '[]'); } catch (_) { supportThreads = []; }
  }
  const threadMap = Object.fromEntries(supportThreads.map((t) => [t.email, t.messages || []]));
  let activeSupportEmail = '';
  let supportPollTimer = null;

  function renderSupportMessages(msgs) {
    const body = document.getElementById('supportThreadBody');
    if (!body) return;
    body.textContent = '';
    if (!msgs.length) {
      const p = document.createElement('p');
      p.style.color = 'var(--mute)';
      p.textContent = 'No messages in thread';
      body.appendChild(p);
      return;
    }
    msgs.forEach((m) => {
      const row = document.createElement('div');
      row.style.cssText = 'padding:.65rem 0;border-bottom:1px solid var(--line)';
      const badge = document.createElement('span');
      badge.className = 'wh-badge';
      badge.textContent = m.sender_type || 'player';
      const when = document.createElement('div');
      when.style.cssText = 'color:var(--mute);font-size:.72rem;margin:.15rem 0';
      when.textContent = m.created_at || '';
      row.appendChild(badge);
      row.appendChild(when);
      const msgText = String(m.message || '').trim();
      const hasImg = !!(m.attachment || (m.has_attachment && m.attachment));
      // Hide placeholder "Attachment" when image is present (Jackpot-style)
      if (msgText && !(hasImg && /^attachment$/i.test(msgText))) {
        const text = document.createElement('div');
        text.style.marginTop = '.25rem';
        text.textContent = msgText;
        row.appendChild(text);
      }
      if (m.attachment) {
        const img = document.createElement('img');
        img.src = m.attachment;
        img.alt = 'Attachment';
        img.style.cssText = 'display:block;max-width:220px;margin-top:.45rem;border-radius:10px;border:1px solid var(--line);cursor:zoom-in';
        img.onclick = () => {
          const lb = document.getElementById('shotLightbox');
          const lbImg = document.getElementById('shotLightboxImg');
          if (lbImg) lbImg.src = m.attachment;
          if (lb) lb.classList.add('is-on');
        };
        row.appendChild(img);
      } else if (m.has_attachment) {
        const hint = document.createElement('div');
        hint.style.cssText = 'color:var(--mute);font-size:.78rem;margin-top:.35rem';
        hint.textContent = 'Image attachment (loading…)';
        row.appendChild(hint);
      }
      body.appendChild(row);
    });
    body.scrollTop = body.scrollHeight;
  }

  async function fetchSupportThread(email, silent) {
    if (!email) return;
    try {
      const d = await WH.api('/support?email=' + encodeURIComponent(email));
      const msgs = (d.items || []).slice().reverse();
      threadMap[email] = msgs;
      if (activeSupportEmail === email) renderSupportMessages(msgs);
      // Refresh left list timestamps / unread feel
      softRefreshSupportList();
    } catch (e) {
      if (!silent) WH.toast(e.message || 'Could not load thread', 'error');
    }
  }

  function softRefreshSupportList() {
    // Rebuild thread buttons from threadMap keys if empty list grows via API later
    const wrap = document.querySelector('#pane-support .wh-tile');
    if (!wrap) return;
  }

  function openSupportThread(email) {
    activeSupportEmail = email;
    document.querySelectorAll('.support-thread-btn').forEach((b) => {
      b.style.background = b.dataset.email === email ? 'rgba(62,224,178,.14)' : 'transparent';
    });
    const meta = document.getElementById('supportThreadMeta');
    const form = document.getElementById('supportReplyForm');
    const emailInput = document.getElementById('supportReplyEmail');
    if (meta) meta.textContent = email;
    if (emailInput) emailInput.value = email;
    if (form) form.style.display = 'grid';
    // Show cached instantly, then always fetch fresh (includes attachment images)
    renderSupportMessages(threadMap[email] || []);
    fetchSupportThread(email, true);
    if (supportPollTimer) clearInterval(supportPollTimer);
    supportPollTimer = setInterval(() => {
      if (activeSupportEmail) fetchSupportThread(activeSupportEmail, true);
    }, 2500);
  }

  document.querySelectorAll('.support-thread-btn').forEach((btn) => {
    btn.onclick = () => openSupportThread(btn.dataset.email);
  });

  let supportReplyAttachment = '';
  document.getElementById('supportReplyFile')?.addEventListener('change', async (e) => {
    try {
      supportReplyAttachment = await readAdminFile(e.target.files?.[0]);
      const prev = document.getElementById('supportReplyPreview');
      if (prev) { prev.src = supportReplyAttachment; prev.style.display = 'block'; }
      WH.toast('Attachment ready');
    } catch (err) {
      supportReplyAttachment = '';
      WH.toast(err.message || 'Upload failed', 'error');
      e.target.value = '';
    }
  });

  const supportReplyForm = document.getElementById('supportReplyForm');
  if (supportReplyForm) {
    supportReplyForm.onsubmit = async (e) => {
      e.preventDefault();
      const email = document.getElementById('supportReplyEmail')?.value;
      const message = document.getElementById('supportReplyMsg')?.value?.trim();
      if (!email || (!message && !supportReplyAttachment)) return;
      try {
        await WH.api('/support', {
          method: 'POST',
          body: JSON.stringify({
            user_email: email,
            message: message || 'Attachment',
            attachment: supportReplyAttachment || undefined,
            sender_type: 'admin'
          })
        });
        WH.toast('Reply sent');
        supportReplyAttachment = '';
        document.getElementById('supportReplyMsg').value = '';
        const prev = document.getElementById('supportReplyPreview');
        if (prev) { prev.style.display = 'none'; prev.removeAttribute('src'); }
        const file = document.getElementById('supportReplyFile');
        if (file) file.value = '';
        await fetchSupportThread(email, true);
      } catch (err) {
        WH.toast(err.message || 'Reply failed', 'error');
      }
    };
  }

  const roleListJs = {!! json_encode($roleList) !!};
  const isFullAdminJs = {!! json_encode($isFullAdmin) !!};
  let prevAlertCounts = null;
  WH.initDesktopNotifications();

  function setNavBadge(pane, count) {
    const el = document.querySelector('[data-badge="' + pane + '"]');
    if (!el) return;
    const n = Number(count) || 0;
    if (n > 0) {
      el.hidden = false;
      el.textContent = n > 99 ? '99+' : String(n);
    } else {
      el.hidden = true;
    }
  }

  async function refreshStats() {
    try {
      const d = await WH.api('/admin/stats');
      const s = d.stats || {};
      const set = (id, v) => { const el = document.getElementById(id); if (el != null && v != null) el.textContent = v; };
      set('sDep', s.pendingDeposits);
      set('sWd', s.pendingWithdraws);
      set('sCoins', s.pendingCoins);
      set('sReq', s.pendingRequests);
      set('sSupport', s.unreadSupport);
      set('sPlayers', s.players);
      const ledgerCount = Number(s.pendingTransactionsCount != null
        ? s.pendingTransactionsCount
        : ((s.pendingDeposits || 0) + (s.pendingWithdraws || 0)));
      setNavBadge('ledger', ledgerCount);
      setNavBadge('coins', s.pendingCoins);
      setNavBadge('requests', s.pendingRequests);
      setNavBadge('support', s.unreadSupport);

      const counts = {
        ledger: ledgerCount,
        deposits: Number(s.pendingDeposits) || 0,
        withdraws: Number(s.pendingWithdraws) || 0,
        coins: Number(s.pendingCoins) || 0,
        requests: Number(s.pendingRequests) || 0,
        chats: Number(s.unreadSupport) || 0
      };
      if (!prevAlertCounts) {
        prevAlertCounts = counts;
        return;
      }
      const roles = roleListJs.map((r) => String(r).toLowerCase());
      const isFull = isFullAdminJs || roles.includes('admin') || roles.includes('operation_admin');
      const canRequests = isFull || roles.includes('coins_admin');
      const canTx = isFull || roles.includes('financial_admin');
      const canCoins = isFull || roles.includes('coins_admin');
      const canChats = isFull || roles.includes('support_admin');
      const prev = prevAlertCounts;
      const hasNewRequest = canRequests && counts.requests > prev.requests;
      const hasNewDep = canTx && counts.deposits > prev.deposits;
      const hasNewWd = canTx && counts.withdraws > prev.withdraws;
      const hasNewCoin = canCoins && counts.coins > prev.coins;
      const hasNewChat = canChats && counts.chats > prev.chats;
      if (hasNewRequest || hasNewDep || hasNewWd || hasNewCoin || hasNewChat) {
        WH.playNotificationSound();
        const parts = [];
        if (hasNewRequest) parts.push('account request');
        if (hasNewDep) parts.push('deposit');
        if (hasNewWd) parts.push('withdrawal');
        if (hasNewCoin) parts.push('coins request');
        if (hasNewChat) parts.push('support message');
        const bump = (hasNewRequest ? 1 : 0) + (hasNewDep ? 1 : 0) + (hasNewWd ? 1 : 0) + (hasNewCoin ? 1 : 0) + (hasNewChat ? 1 : 0);
        const active = document.querySelector('.wh-aside button[data-pane].is-on');
        const notifyUrl = currentTabUrl(active ? active.dataset.pane : 'dashboard');
        WH.notifyStaffActivity('Winning Heaven — New activity', 'New ' + parts.join(', ') + ' received.', bump, notifyUrl);
        WH.toast('New ' + parts.join(', '), 'info');
        // Soft-refresh open queue tab
        const openPane = active && active.dataset.pane;
        if (openPane === 'ledger' && (hasNewDep || hasNewWd)) softRefreshLedger();
        if (openPane === 'coins' && hasNewCoin) softRefreshCoins();
        if (openPane === 'requests' && hasNewRequest) softRefreshRequests();
        if (openPane === 'support' && hasNewChat && activeSupportEmail) fetchSupportThread(activeSupportEmail, true);
        if (hasNewChat) softRefreshSupportSidebar();
      }
      prevAlertCounts = counts;
    } catch (_) {}
  }

  async function softRefreshSupportSidebar() {
    try {
      const d = await WH.api('/support');
      const items = d.items || [];
      const byEmail = {};
      items.forEach((m) => {
        const e = String(m.user_email || '').toLowerCase();
        if (!e) return;
        if (!byEmail[e]) byEmail[e] = [];
        byEmail[e].push(m);
      });
      Object.keys(byEmail).forEach((e) => {
        threadMap[e] = byEmail[e].slice().sort((a, b) => String(a.created_at).localeCompare(String(b.created_at)));
      });
      const list = document.querySelector('#pane-support .wh-tile');
      if (!list) return;
      const emails = Object.keys(byEmail).sort();
      if (!emails.length) return;
      // Rebuild thread buttons if new emails appeared
      const existing = new Set([...document.querySelectorAll('.support-thread-btn')].map((b) => b.dataset.email));
      emails.forEach((email) => {
        if (existing.has(email)) return;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'support-thread-btn';
        btn.dataset.email = email;
        btn.style.cssText = 'display:block;width:100%;text-align:left;border:0;background:transparent;color:var(--ink);padding:.65rem .55rem;border-radius:10px;cursor:pointer;font-weight:600';
        btn.textContent = email;
        btn.onclick = () => openSupportThread(email);
        list.prepend(btn);
      });
    } catch (_) {}
  }

  // Wake coins queue when finance approves (BroadcastChannel)
  if (bc) {
    bc.onmessage = (ev) => {
      const msg = ev.data || {};
      if (msg.type === 'coins') {
        softRefreshCoins();
        refreshStats();
      }
      if (msg.type === 'transactions') {
        softRefreshLedger();
        refreshStats();
      }
    };
  }

  refreshStats();
  setInterval(refreshStats, 2000);
})();
</script>
@endpush

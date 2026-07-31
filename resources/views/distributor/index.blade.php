@extends('layouts.app')
@section('title', 'Distributor — Winning Heaven')
@section('content')
@php
  $bg = $frontend['login_bg_url'] ?? '/brand/bg.png';
  $logo = $frontend['logo_url'] ?? '/brand/logo.png';
@endphp

{{-- ===================== LOGIN ===================== --}}
<div class="wh-auth" id="loginScreen" style="--wh-bg:url('{{ asset(ltrim($bg,'/')) }}')">
  <div class="wh-auth__sky"></div>
  <div class="wh-auth__top">
    <div class="wh-brand-mark">
      <img src="{{ asset(ltrim($logo,'/')) }}" alt="">
      <div><strong>Winning Heaven</strong><span>Distributor portal</span></div>
    </div>
    <a class="wh-chip" href="/login">Player</a>
  </div>
  <div class="wh-auth__hero">
    <h1>Distributor<br><em>desk</em></h1>
    <p>Type A / B network — players, commissions, and ops in one place.</p>
  </div>
  <div class="wh-sheet" style="width:100%;max-width:420px">
    <form id="distLogin">
      <div class="wh-field">
        <label>Email</label>
        <div class="box"><i class="fa-solid fa-envelope"></i><input type="email" name="email" required placeholder="dist@winningheaven.com" autocomplete="username"></div>
      </div>
      <div class="wh-field">
        <label>Password</label>
        <div class="box"><i class="fa-solid fa-lock"></i><input type="password" name="password" required placeholder="••••••••" autocomplete="current-password"></div>
      </div>
      <p id="loginErr" style="display:none;color:#ff6b7a;font-size:.8rem;margin:0 0 .75rem"></p>
      <button class="wh-primary" type="submit" id="loginBtn">Enter portal</button>
    </form>
  </div>
</div>

{{-- ===================== PORTAL SHELL ===================== --}}
<div class="wh-admin" id="portalShell" style="display:none">
  <aside class="wh-aside">
    <div class="brand">
      <img src="{{ asset(ltrim($logo,'/')) }}" alt="">
      <div>
        <strong style="display:block">Winning Heaven</strong>
        <span style="color:var(--mute);font-size:.75rem" id="asideSub">Distributor</span>
      </div>
    </div>
    <nav class="wh-aside-nav" id="asideNav"></nav>
    <div class="wh-aside-foot">
      <div style="padding:.35rem;color:var(--mute);font-size:.75rem" id="asideMeta"></div>
      <a class="nav" href="{{ $frontend['android_app_url'] ?? '/downloads/winning-heaven-distributor.apk' }}" style="font-size:.82rem">
        <i class="fa-solid fa-download"></i> Download App
      </a>
      <button type="button" id="logoutBtn">Logout</button>
    </div>
  </aside>

  <div class="wh-admin-main">
    {{-- Overview --}}
    <div class="wh-pane" id="pane-overview">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
        <div>
          <h1 style="font-family:var(--font-display);margin:0 0 .35rem">Overview</h1>
          <p style="color:var(--mute);margin:0;font-size:.85rem">Players, deposits, withdrawals, and commission.</p>
        </div>
        <div class="wh-tile" style="padding:.65rem 1rem;display:flex;align-items:center;gap:.6rem;max-width:100%">
          <div style="min-width:0">
            <div style="color:var(--mute);font-size:.7rem;font-weight:700;text-transform:uppercase">Referral link</div>
            <div id="referralUrl" style="font-size:.78rem;color:var(--aqua);font-family:ui-monospace,monospace;word-break:break-all"></div>
          </div>
          <button type="button" class="wh-btn-sm" id="copyReferral">Copy</button>
        </div>
      </div>
      <div class="wh-stats" id="statTiles">
        <div class="wh-tile"><div style="color:var(--mute);font-size:.8rem">Players</div><div style="font-size:1.8rem;font-weight:800" id="stPlayers">—</div></div>
        <div class="wh-tile"><div style="color:var(--mute);font-size:.8rem">Deposits</div><div style="font-size:1.8rem;font-weight:800;color:#2ecc71" id="stDep">—</div></div>
        <div class="wh-tile"><div style="color:var(--mute);font-size:.8rem">Withdrawals</div><div style="font-size:1.8rem;font-weight:800;color:#ff6b7a" id="stWd">—</div></div>
        <div class="wh-tile"><div style="color:var(--mute);font-size:.8rem" id="stCommLabel">Commission</div><div style="font-size:1.8rem;font-weight:800;color:var(--sand)" id="stComm">—</div></div>
      </div>
      <p style="color:var(--mute);font-size:.85rem" id="overviewHint"></p>
    </div>

    {{-- Transaction logs --}}
    <div class="wh-pane" id="pane-tx_logs">
      <h2 style="font-family:var(--font-display)">Transaction logs</h2>
      <p style="color:var(--mute);font-size:.85rem;margin-top:0">All player activity under your distributor ID.</p>
      <div class="wh-tile" style="overflow:auto">
        <table class="wh-table">
          <thead>
            <tr><th>When</th><th>User</th><th>Type</th><th>Amount</th><th>Status</th><th>Gateway</th><th>Code</th></tr>
          </thead>
          <tbody id="txLogsBody"><tr><td colspan="7" style="color:var(--mute)">Loading…</td></tr></tbody>
        </table>
      </div>
    </div>

    {{-- Players --}}
    <div class="wh-pane" id="pane-players">
      <h2 style="font-family:var(--font-display)">Players</h2>
      <p style="color:var(--mute);font-size:.85rem;margin-top:0">Referred players and create accounts under your network.</p>
      <div class="wh-tile" style="margin-bottom:1rem;max-width:480px">
        <h3 style="font-family:var(--font-display);margin:0 0 .75rem;font-size:1.05rem">Create player</h3>
        <form id="createPlayerForm">
          <div class="wh-field"><label>Name</label><div class="box"><input name="name" required placeholder="Player name"></div></div>
          <div class="wh-field"><label>Email</label><div class="box"><input name="email" type="email" required placeholder="player@email.com"></div></div>
          <div class="wh-field"><label>Password</label><div class="box"><input name="password" type="password" required minlength="6" placeholder="min 6 chars"></div></div>
          <button class="wh-cta" type="submit">Create player</button>
        </form>
      </div>
      <div class="wh-tile" style="overflow:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;margin-bottom:.5rem">
          <strong style="font-size:.9rem">Roster</strong>
          <input id="playerSearch" placeholder="Search…" style="background:transparent;border:1px solid var(--line);border-radius:8px;color:var(--ink);padding:.4rem .65rem;font-size:.8rem;max-width:200px">
        </div>
        <table class="wh-table">
          <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Coins</th><th>Joined</th></tr></thead>
          <tbody id="playersBody"><tr><td colspan="5" style="color:var(--mute)">Loading…</td></tr></tbody>
        </table>
      </div>
    </div>

    {{-- Type A: Commission cashout --}}
    <div class="wh-pane" id="pane-cashout">
      <h2 style="font-family:var(--font-display)">Commission cashout</h2>
      <p style="color:var(--mute);font-size:.85rem;margin-top:0">Request a COMMISSION_WITHDRAW payout for your referral earnings.</p>
      <div style="display:grid;grid-template-columns:minmax(260px,380px) 1fr;gap:1rem;align-items:start">
        <div class="wh-tile">
          <div style="color:var(--mute);font-size:.8rem;margin-bottom:.35rem">Estimated commission</div>
          <div style="font-size:1.6rem;font-weight:800;color:var(--sand);margin-bottom:1rem" id="cashoutAvail">$0.00</div>
          <form id="cashoutForm">
            <div class="wh-field"><label>Amount ($)</label><div class="box"><input name="amount" type="number" step="0.01" min="0.01" required placeholder="50.00"></div></div>
            <div class="wh-field"><label>Gateway</label><div class="box"><input name="gateway" required placeholder="Chime / Zelle / CashApp"></div></div>
            <div class="wh-field"><label>Tag / address / code</label><div class="box"><input name="code" required placeholder="payment tag or address"></div></div>
            <button class="wh-cta" type="submit">Submit cashout</button>
          </form>
        </div>
        <div class="wh-tile" style="overflow:auto">
          <h3 style="font-family:var(--font-display);margin:0 0 .75rem;font-size:1.05rem">Cashout history</h3>
          <table class="wh-table">
            <thead><tr><th>When</th><th>Amount</th><th>Gateway</th><th>Code</th><th>Status</th></tr></thead>
            <tbody id="cashoutHistBody"><tr><td colspan="5" style="color:var(--mute)">No cashouts yet</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Type B: Website commission payment --}}
    <div class="wh-pane" id="pane-website_comm">
      <h2 style="font-family:var(--font-display)">Website commission</h2>
      <p style="color:var(--mute);font-size:.85rem;margin-top:0">Submit WEBSITE_COMMISSION_PAYMENT proof to keep your Type B account current.</p>
      <div style="display:grid;grid-template-columns:minmax(260px,380px) 1fr;gap:1rem;align-items:start">
        <div class="wh-tile">
          <div style="color:var(--mute);font-size:.8rem;margin-bottom:.35rem">Your website commission rate</div>
          <div style="font-size:1.4rem;font-weight:800;color:#ff6b7a;margin-bottom:1rem" id="webRateLabel">—</div>
          <form id="websitePayForm">
            <div class="wh-field"><label>Amount ($)</label><div class="box"><input name="amount" type="number" step="0.01" min="0.01" required placeholder="50.00"></div></div>
            <div class="wh-field"><label>TxID / hash / code</label><div class="box"><input name="code" required placeholder="USDT TxHash or reference"></div></div>
            <div class="wh-field">
              <label>Screenshot (optional)</label>
              <input name="screenshot_file" type="file" accept="image/*" style="width:100%;color:var(--mute);font-size:.8rem">
            </div>
            <button class="wh-cta" type="submit">Submit payment proof</button>
          </form>
        </div>
        <div class="wh-tile" style="overflow:auto">
          <h3 style="font-family:var(--font-display);margin:0 0 .75rem;font-size:1.05rem">Payment logs</h3>
          <table class="wh-table">
            <thead><tr><th>When</th><th>Amount</th><th>Code</th><th>Status</th><th>Note</th></tr></thead>
            <tbody id="webPayHistBody"><tr><td colspan="5" style="color:var(--mute)">No payments yet</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Type B: Operations (coins) --}}
    <div class="wh-pane" id="pane-operations">
      <h2 style="font-family:var(--font-display)">Operations queue</h2>
      <p style="color:var(--mute);font-size:.85rem;margin-top:0">Pending coin allotments for your players — complete or hold.</p>
      <div class="wh-tile" style="overflow:auto">
        <table class="wh-table">
          <thead>
            <tr><th>User</th><th>Game</th><th>Coins</th><th>Type</th><th>Status</th><th></th></tr>
          </thead>
          <tbody id="opsBody"><tr><td colspan="6" style="color:var(--mute)">Loading…</td></tr></tbody>
        </table>
      </div>
    </div>

    {{-- Type B: Account requests --}}
    <div class="wh-pane" id="pane-requests">
      <h2 style="font-family:var(--font-display)">Account requests</h2>
      <p style="color:var(--mute);font-size:.85rem;margin-top:0">Issue game credentials for pending player requests.</p>
      <div class="wh-tile" style="overflow:auto">
        <table class="wh-table">
          <thead>
            <tr><th>User</th><th>Game</th><th>Status</th><th></th></tr>
          </thead>
          <tbody id="reqBody"><tr><td colspan="4" style="color:var(--mute)">Loading…</td></tr></tbody>
        </table>
      </div>
    </div>

    {{-- Type B: Ledger --}}
    <div class="wh-pane" id="pane-ledger">
      <h2 style="font-family:var(--font-display)">Ledger</h2>
      <p style="color:var(--mute);font-size:.85rem;margin-top:0">Approve deposits and process withdrawals for your network.</p>
      <div class="wh-tile" style="margin-bottom:1rem;overflow:auto">
        <h3 style="font-family:var(--font-display);margin:0 0 .75rem;font-size:1.05rem">Pending deposits</h3>
        <table class="wh-table">
          <thead><tr><th>User</th><th>Amount</th><th>Gateway</th><th>Game</th><th>Code</th><th></th></tr></thead>
          <tbody id="ledgerDepBody"><tr><td colspan="6" style="color:var(--mute)">Loading…</td></tr></tbody>
        </table>
      </div>
      <div class="wh-tile" style="overflow:auto">
        <h3 style="font-family:var(--font-display);margin:0 0 .75rem;font-size:1.05rem">Pending withdrawals</h3>
        <table class="wh-table">
          <thead><tr><th>User</th><th>Amount</th><th>Gateway</th><th>Code</th><th>Status</th><th></th></tr></thead>
          <tbody id="ledgerWdBody"><tr><td colspan="6" style="color:var(--mute)">Loading…</td></tr></tbody>
        </table>
      </div>
    </div>

    {{-- Type B: My Gateways --}}
    <div class="wh-pane" id="pane-gateways">
      <h2 style="font-family:var(--font-display)">My Gateways</h2>
      <p style="color:var(--mute);font-size:.85rem;margin-top:0">Payment methods for your Type B players — name, tag, phone, QR, and redirect.</p>
      <div class="wh-tile" style="margin-bottom:1rem;max-width:560px">
        <h3 style="font-family:var(--font-display);margin:0 0 .75rem;font-size:1.05rem">Add gateway</h3>
        <form id="gwForm">
          <div class="wh-field"><label>Name</label><div class="box"><input name="name" required placeholder="CashApp / Chime / Zelle"></div></div>
          <div class="wh-field"><label>Tag</label><div class="box"><input name="tag" placeholder="$cashtag or handle"></div></div>
          <div class="wh-field"><label>Phone</label><div class="box"><input name="phone" placeholder="+1…"></div></div>
          <div class="wh-field"><label>Redirect URL</label><div class="box"><input name="redirect_url" placeholder="https://…{amount}"></div></div>
          <div class="wh-field">
            <label>QR image (optional)</label>
            <input name="qr_image_file" type="file" accept="image/*" style="width:100%;color:var(--mute);font-size:.8rem">
          </div>
          <button class="wh-cta" type="submit">Add gateway</button>
        </form>
      </div>
      <div class="wh-tile" style="overflow:auto">
        <table class="wh-table">
          <thead><tr><th>Name</th><th>Tag</th><th>Phone</th><th>Redirect</th><th>QR</th></tr></thead>
          <tbody id="gwBody"><tr><td colspan="5" style="color:var(--mute)">Loading…</td></tr></tbody>
        </table>
      </div>
    </div>

    {{-- Type B: My Staff --}}
    <div class="wh-pane" id="pane-staff">
      <h2 style="font-family:var(--font-display)">My Staff</h2>
      <p style="color:var(--mute);font-size:.85rem;margin-top:0">Create ops accounts under your distributor with role and game access.</p>
      <div class="wh-tile" style="margin-bottom:1rem;max-width:560px">
        <h3 style="font-family:var(--font-display);margin:0 0 .75rem;font-size:1.05rem">Add staff</h3>
        <form id="staffForm">
          <div class="wh-field"><label>Name</label><div class="box"><input name="name" required placeholder="Staff name"></div></div>
          <div class="wh-field"><label>Email</label><div class="box"><input name="email" type="email" required placeholder="staff@email.com"></div></div>
          <div class="wh-field"><label>Password</label><div class="box"><input name="password" type="password" required minlength="6" placeholder="min 6 chars"></div></div>
          <div class="wh-field" style="margin-bottom:.35rem">
            <label>Roles</label>
            <div style="display:flex;flex-wrap:wrap;gap:.65rem;margin-top:.4rem">
              <label style="display:flex;align-items:center;gap:.35rem;font-size:.85rem;color:var(--ink);cursor:pointer">
                <input type="checkbox" name="roles" value="coins_admin"> coins_admin
              </label>
              <label style="display:flex;align-items:center;gap:.35rem;font-size:.85rem;color:var(--ink);cursor:pointer">
                <input type="checkbox" name="roles" value="financial_admin"> financial_admin
              </label>
              <label style="display:flex;align-items:center;gap:.35rem;font-size:.85rem;color:var(--ink);cursor:pointer">
                <input type="checkbox" name="roles" value="support_admin"> support_admin
              </label>
            </div>
          </div>
          <div class="wh-field" style="margin-bottom:.35rem">
            <label>Allowed games</label>
            <div id="staffGamesBox" style="display:flex;flex-direction:column;gap:.4rem;margin-top:.4rem;max-height:180px;overflow:auto;padding:.35rem 0">
              <span style="color:var(--mute);font-size:.8rem">Loading games…</span>
            </div>
          </div>
          <button class="wh-cta" type="submit">Create staff</button>
        </form>
      </div>
      <div class="wh-tile" style="overflow:auto">
        <table class="wh-table">
          <thead><tr><th>Name</th><th>Email</th><th>Roles</th><th>Games</th><th>Status</th></tr></thead>
          <tbody id="staffBody"><tr><td colspan="5" style="color:var(--mute)">Loading…</td></tr></tbody>
        </table>
      </div>
    </div>

    {{-- Type B: Support --}}
    <div class="wh-pane" id="pane-support">
      <h2 style="font-family:var(--font-display)">Support</h2>
      <p style="color:var(--mute);font-size:.85rem;margin-top:0">Player threads for your network — reply as distributor support.</p>
      <div class="wh-bento" style="display:grid;grid-template-columns:minmax(200px,280px) 1fr;gap:.85rem;min-height:420px">
        <div class="wh-tile" style="padding:.5rem;overflow:auto;max-height:70vh" id="supportThreadList">
          <p style="color:var(--mute);padding:.65rem;margin:0">Loading…</p>
        </div>
        <div class="wh-tile" style="display:flex;flex-direction:column;min-height:420px">
          <div id="supportThreadMeta" style="color:var(--mute);font-size:.85rem;margin-bottom:.75rem">Select a thread</div>
          <div id="supportThreadBody" style="flex:1;overflow:auto;max-height:50vh;margin-bottom:.85rem">
            <p style="color:var(--mute);margin:0">Pick a conversation on the left.</p>
          </div>
          <form id="supportReplyForm" style="display:none;gap:.55rem">
            <input type="hidden" id="supportReplyEmail" name="user_email">
            <div class="wh-field" style="margin:0">
              <label>Reply</label>
              <div class="box"><input id="supportReplyMsg" name="message" required placeholder="Type reply…"></div>
            </div>
            <button class="wh-cta" type="submit">Send reply</button>
          </form>
        </div>
      </div>
    </div>

    {{-- Guidelines --}}
    <div class="wh-pane" id="pane-guidelines">
      <h2 style="font-family:var(--font-display)">Guidelines &amp; rules</h2>
      <p style="color:var(--mute);font-size:.85rem;margin-top:0">Limits and commission basics for your referred players.</p>
      <div class="wh-tile" style="display:flex;flex-direction:column;gap:1.1rem">
        <div>
          <h4 style="margin:0 0 .35rem;color:var(--sand);font-size:.9rem">1. Deposit &amp; withdrawal limits</h4>
          <p style="margin:0;color:var(--mute);font-size:.85rem;line-height:1.45">
            Minimum deposit is <strong style="color:var(--ink)">$5.00</strong>.
            Minimum withdrawal is <strong style="color:var(--ink)">$25.00</strong> per standard player request.
          </p>
        </div>
        <div style="border-top:1px solid var(--line);padding-top:1.1rem">
          <h4 style="margin:0 0 .35rem;color:var(--sand);font-size:.9rem">2. Commission</h4>
          <p style="margin:0;color:var(--mute);font-size:.85rem;line-height:1.45">
            Your rate applies to net profit (successful deposits minus withdrawals) from referred players.
            Type A partners cash out commission; Type B partners settle website commission to HQ.
          </p>
        </div>
        <div style="border-top:1px solid var(--line);padding-top:1.1rem">
          <h4 style="margin:0 0 .35rem;color:var(--sand);font-size:.9rem">3. Freeplay max cashout</h4>
          <p style="margin:0;color:var(--mute);font-size:.85rem;line-height:1.45">
            Wins originating from freeplay are capped at <strong style="color:var(--ink)">$30.00</strong> maximum cashout.
            Amounts above that cap cannot be claimed.
          </p>
        </div>
      </div>
    </div>

    <div class="wh-pane" id="pane-promotions">
      <h2 style="font-family:var(--font-display)">Promotions</h2>
      <p style="color:var(--mute);font-size:.85rem;margin-top:0">Active platform promotions visible to your players.</p>
      <div class="wh-tile" style="overflow:auto">
        <table class="wh-table">
          <thead><tr><th>Title</th><th>Type</th><th>Freeplay</th><th>Bonus %</th></tr></thead>
          <tbody id="distPromoBody"><tr><td colspan="4" style="color:var(--mute)">Loading…</td></tr></tbody>
        </table>
      </div>
    </div>

    <div class="wh-pane" id="pane-shift_dashboard">
      <h2 style="font-family:var(--font-display)">Shift Dashboard</h2>
      <p style="color:var(--mute);font-size:.85rem;margin-top:0">Type B ops snapshot — pending requests and coins for your network.</p>
      <div class="wh-stats">
        <div class="wh-tile"><div style="color:var(--mute);font-size:.8rem">Pending requests</div><div style="font-size:1.8rem;font-weight:800" id="distShiftReq">—</div></div>
        <div class="wh-tile"><div style="color:var(--mute);font-size:.8rem">Coins queue</div><div style="font-size:1.8rem;font-weight:800" id="distShiftCoins">—</div></div>
        <div class="wh-tile"><div style="color:var(--mute);font-size:.8rem">Pending ledger</div><div style="font-size:1.8rem;font-weight:800" id="distShiftLedger">—</div></div>
      </div>
    </div>
  </div>
</div>
@endsection
(function () {
  /** @type {{ public_id:string, name:string, type:string, email?:string, commission_rate:number, website_commission_rate:number }|null} */
  let dist = null;
  let lastStats = null;

  const NAV_A = [
    { id: 'overview', label: 'Overview', icon: 'fa-chart-line' },
    { id: 'tx_logs', label: 'Transaction logs', icon: 'fa-clock-rotate-left' },
    { id: 'players', label: 'Players', icon: 'fa-users' },
    { id: 'cashout', label: 'Commission cashout', icon: 'fa-hand-holding-dollar' },
    { id: 'promotions', label: 'Promotions', icon: 'fa-gift' },
    { id: 'guidelines', label: 'Guidelines', icon: 'fa-circle-info' },
  ];
  const NAV_B = [
    { id: 'overview', label: 'Overview', icon: 'fa-chart-line' },
    { id: 'tx_logs', label: 'Transaction logs', icon: 'fa-clock-rotate-left' },
    { id: 'players', label: 'Players', icon: 'fa-users' },
    { id: 'website_comm', label: 'Website commission', icon: 'fa-globe' },
    { id: 'operations', label: 'Operations', icon: 'fa-circle-play' },
    { id: 'requests', label: 'Account requests', icon: 'fa-key' },
    { id: 'shift_dashboard', label: 'Shift Dashboard', icon: 'fa-clock' },
    { id: 'ledger', label: 'Ledger', icon: 'fa-book' },
    { id: 'gateways', label: 'My Gateways', icon: 'fa-qrcode' },
    { id: 'staff', label: 'My Staff', icon: 'fa-user-gear' },
    { id: 'support', label: 'Support', icon: 'fa-headset' },
    { id: 'promotions', label: 'Promotions', icon: 'fa-gift' },
    { id: 'guidelines', label: 'Guidelines', icon: 'fa-circle-info' },
  ];
  const INITIAL_TAB = {!! json_encode($initialTab ?? null) !!};

  /** @type {Array<{public_id:string,title:string}>} */
  let gamesCache = [];
  /** @type {Record<string, Array<any>>} */
  let supportThreadMap = {};

  const $ = (id) => document.getElementById(id);
  const money = (n) => '$' + Number(n || 0).toFixed(2);
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  })[c]);
  const when = (v) => {
    if (!v) return '—';
    try { return new Date(v).toLocaleString(); } catch (_) { return String(v); }
  };
  const statusChip = (st) => {
    const s = String(st || '').toUpperCase();
    let color = 'var(--mute)';
    if (s === 'SUCCESS' || s === 'COMPLETED' || s === 'READY') color = '#2ecc71';
    else if (s === 'FAILED' || s === 'REJECTED') color = '#ff6b7a';
    else if (s.includes('PENDING') || s === 'HOLD') color = 'var(--sand)';
    return '<span style="color:' + color + ';font-weight:700;font-size:.78rem">' + esc(s || '—') + '</span>';
  };

  function referralLink() {
    if (!dist) return '';
    return window.location.origin + '/login?tab=register&dist=' + encodeURIComponent(dist.public_id);
  }

  function navForType() {
    return (String(dist?.type || 'A').toUpperCase() === 'B') ? NAV_B : NAV_A;
  }

  function buildNav(active) {
    const wrap = $('asideNav');
    if (!wrap) return;
    wrap.innerHTML = '';
    navForType().forEach((item) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.dataset.pane = item.id;
      if (item.id === active) btn.classList.add('is-on');
      btn.innerHTML = '<i class="fa-solid ' + item.icon + '" style="width:1rem;margin-right:.45rem;opacity:.85"></i>' + item.label;
      btn.onclick = () => showPane(item.id);
      wrap.appendChild(btn);
    });
  }

  function showPane(pane, push) {
    const allowed = navForType().some((n) => n.id === pane);
    if (!allowed) pane = 'overview';
    document.querySelectorAll('.wh-aside button[data-pane]').forEach((b) => {
      b.classList.toggle('is-on', b.dataset.pane === pane);
    });
    document.querySelectorAll('.wh-pane').forEach((p) => p.classList.remove('is-on'));
    const el = $('pane-' + pane);
    if (el) el.classList.add('is-on');
    if (push !== false) {
      const url = '/distributor/' + pane;
      if (window.location.pathname !== url) history.pushState({ distributorTab: pane }, '', url);
    }
    loadPane(pane);
  }

  window.addEventListener('popstate', (e) => {
    const pane = (e.state && e.state.distributorTab) || (window.location.pathname.split('/').filter(Boolean)[1]) || 'overview';
    showPane(pane, false);
  });

  function showLogin() {
    dist = null;
    lastStats = null;
    $('portalShell').style.display = 'none';
    $('loginScreen').style.display = '';
    $('distLogin')?.reset();
  }

  function showPortal() {
    $('loginScreen').style.display = 'none';
    $('portalShell').style.display = '';
    $('asideSub').textContent = 'Type ' + (dist.type || '?') + ' · Distributor';
    $('asideMeta').textContent = (dist.name || '') + (dist.email ? ' · ' + dist.email : '');
    buildNav(INITIAL_TAB && navForType().some((n) => n.id === INITIAL_TAB) ? INITIAL_TAB : 'overview');
    const link = referralLink();
    $('referralUrl').textContent = link;
    $('webRateLabel').textContent = Number(dist.website_commission_rate || 0) + '% of profit';
    const rate = Number(dist.commission_rate || 0);
    $('stCommLabel').textContent = String(dist.type).toUpperCase() === 'B'
      ? ('Website commission (' + Number(dist.website_commission_rate || 0) + '%)')
      : ('My commission (' + rate + '%)');
    $('overviewHint').textContent = String(dist.type).toUpperCase() === 'B'
      ? 'Type B: settle website commission, manage gateways/staff, and use Operations / Requests / Ledger / Support for day-to-day ops.'
      : 'Type A: cash out earned commission from the Commission cashout tab.';
    const startTab = INITIAL_TAB && navForType().some((n) => n.id === INITIAL_TAB) ? INITIAL_TAB : 'overview';
    showPane(startTab, true);
  }

  async function safeApi(url, opts) {
    return WH.api(url, opts);
  }

  async function loadOverview() {
    if (!dist) return;
    try {
      const r = await safeApi('/distributors/' + encodeURIComponent(dist.public_id) + '/stats');
      const s = r.stats || {};
      lastStats = s;
      $('stPlayers').textContent = String(s.players ?? 0);
      $('stDep').textContent = money(s.deposits);
      $('stWd').textContent = money(s.withdrawals);
      const isB = String(dist.type).toUpperCase() === 'B';
      const commDisplay = isB
        ? (s.website_due != null ? s.website_due : (s.website_commission ?? s.commission))
        : (s.available_commission != null ? s.available_commission : s.commission);
      $('stComm').textContent = money(commDisplay);
      $('cashoutAvail').textContent = money(
        s.available_commission != null ? s.available_commission : s.commission
      );
    } catch (e) {
      WH.toast(e.message || 'Failed to load stats', 'error');
    }
  }

  async function loadTxLogs() {
    const body = $('txLogsBody');
    if (!dist || !body) return;
    body.innerHTML = '<tr><td colspan="7" style="color:var(--mute)">Loading…</td></tr>';
    try {
      const r = await safeApi('/transactions?distributor_id=' + encodeURIComponent(dist.public_id));
      const items = r.items || [];
      if (!items.length) {
        body.innerHTML = '<tr><td colspan="7" style="color:var(--mute)">No transactions</td></tr>';
        return;
      }
      body.innerHTML = items.map((tx) => (
        '<tr>' +
          '<td>' + esc(when(tx.created_at || tx.createdAt)) + '</td>' +
          '<td>' + esc(tx.user_email) + '</td>' +
          '<td>' + esc(tx.type) + '</td>' +
          '<td>' + money(tx.amount) + '</td>' +
          '<td>' + statusChip(tx.status) + '</td>' +
          '<td>' + esc(tx.gateway || '—') + '</td>' +
          '<td style="font-family:ui-monospace,monospace;font-size:.78rem">' + esc(tx.code || '—') + '</td>' +
        '</tr>'
      )).join('');
    } catch (e) {
      body.innerHTML = '<tr><td colspan="7" style="color:#ff6b7a">' + esc(e.message || 'Failed to load') + '</td></tr>';
    }
  }

  async function loadPlayers() {
    const body = $('playersBody');
    if (!dist || !body) return;
    body.innerHTML = '<tr><td colspan="5" style="color:var(--mute)">Loading…</td></tr>';
    try {
      const r = await safeApi('/distributors/' + encodeURIComponent(dist.public_id) + '/players');
      const items = r.items || r.players || [];
      if (!items.length) {
        body.innerHTML = '<tr><td colspan="5" style="color:var(--mute)">No players yet</td></tr>';
        return;
      }
      body.innerHTML = items.map((p) => {
        const search = ((p.name || '') + ' ' + (p.email || '')).toLowerCase();
        return '<tr data-search="' + esc(search) + '">' +
          '<td>' + esc(p.name || '—') + '</td>' +
          '<td>' + esc(p.email || '—') + '</td>' +
          '<td>' + statusChip(p.status || 'active') + '</td>' +
          '<td>' + money(p.coins) + '</td>' +
          '<td>' + esc(when(p.created_at || p.createdAt)) + '</td>' +
        '</tr>';
      }).join('');
    } catch (e) {
      body.innerHTML = '<tr><td colspan="5" style="color:#ff6b7a">' + esc(e.message || 'Failed to load') + '</td></tr>';
    }
  }

  async function loadCashoutHist() {
    const body = $('cashoutHistBody');
    if (!dist || !body) return;
    try {
      const r = await safeApi('/transactions?distributor_id=' + encodeURIComponent(dist.public_id) + '&type=COMMISSION_WITHDRAW');
      const items = r.items || [];
      if (!items.length) {
        body.innerHTML = '<tr><td colspan="5" style="color:var(--mute)">No cashouts yet</td></tr>';
        return;
      }
      body.innerHTML = items.map((tx) => (
        '<tr>' +
          '<td>' + esc(when(tx.created_at || tx.createdAt)) + '</td>' +
          '<td>' + money(tx.amount) + '</td>' +
          '<td>' + esc(tx.gateway || '—') + '</td>' +
          '<td style="font-family:ui-monospace,monospace;font-size:.78rem">' + esc(tx.code || '—') + '</td>' +
          '<td>' + statusChip(tx.status) + '</td>' +
        '</tr>'
      )).join('');
    } catch (e) {
      body.innerHTML = '<tr><td colspan="5" style="color:#ff6b7a">' + esc(e.message || 'Failed') + '</td></tr>';
    }
  }

  async function loadWebPayHist() {
    const body = $('webPayHistBody');
    if (!dist || !body) return;
    try {
      const r = await safeApi('/transactions?distributor_id=' + encodeURIComponent(dist.public_id) + '&type=WEBSITE_COMMISSION_PAYMENT');
      const items = r.items || [];
      if (!items.length) {
        body.innerHTML = '<tr><td colspan="5" style="color:var(--mute)">No payments yet</td></tr>';
        return;
      }
      body.innerHTML = items.map((tx) => (
        '<tr>' +
          '<td>' + esc(when(tx.created_at || tx.createdAt)) + '</td>' +
          '<td>' + money(tx.amount) + '</td>' +
          '<td style="font-family:ui-monospace,monospace;font-size:.78rem">' + esc(tx.code || '—') + '</td>' +
          '<td>' + statusChip(tx.status) + '</td>' +
          '<td>' + esc(tx.note || '—') + '</td>' +
        '</tr>'
      )).join('');
    } catch (e) {
      body.innerHTML = '<tr><td colspan="5" style="color:#ff6b7a">' + esc(e.message || 'Failed') + '</td></tr>';
    }
  }

  async function loadOperations() {
    const body = $('opsBody');
    if (!dist || !body) return;
    body.innerHTML = '<tr><td colspan="6" style="color:var(--mute)">Loading…</td></tr>';
    try {
      const r = await safeApi('/coins-notifications?status=PENDING,HOLD,CLAIM_REQUESTED&distributor_id=' + encodeURIComponent(dist.public_id));
      const items = (r.items || []).filter((n) => !n.distributor_id || n.distributor_id === dist.public_id);
      if (!items.length) {
        body.innerHTML = '<tr><td colspan="6" style="color:var(--mute)">Queue empty</td></tr>';
        return;
      }
      body.innerHTML = items.map((n) => {
        const id = n.public_id || n.id;
        const pending = String(n.status || '').toUpperCase() !== 'COMPLETED';
        return '<tr>' +
          '<td>' + esc(n.user_email) + '</td>' +
          '<td>' + esc(n.game_title || '—') + '</td>' +
          '<td>' + esc(n.total_coins) + '</td>' +
          '<td>' + esc(n.transaction_type || n.type || '—') + '</td>' +
          '<td>' + statusChip(n.status) + '</td>' +
          '<td class="wh-btn-row">' + (pending
            ? '<button type="button" class="wh-btn-sm" data-act="complete-coins" data-id="' + esc(id) + '">Complete</button>' +
              '<button type="button" class="wh-btn-sm danger" data-act="hold-coins" data-id="' + esc(id) + '">Hold</button>'
            : '—') + '</td>' +
        '</tr>';
      }).join('');
    } catch (e) {
      body.innerHTML = '<tr><td colspan="6" style="color:#ff6b7a">' + esc(e.message || 'Failed') + '</td></tr>';
    }
  }

  async function loadRequests() {
    const body = $('reqBody');
    if (!dist || !body) return;
    body.innerHTML = '<tr><td colspan="4" style="color:var(--mute)">Loading…</td></tr>';
    try {
      const r = await safeApi('/account-requests?status=PENDING');
      const items = (r.items || []).filter((a) => !a.distributor_id || a.distributor_id === dist.public_id);
      if (!items.length) {
        body.innerHTML = '<tr><td colspan="4" style="color:var(--mute)">No pending requests</td></tr>';
        return;
      }
      body.innerHTML = items.map((a) => {
        const id = a.public_id || a.id;
        return '<tr>' +
          '<td>' + esc(a.user_email) + '</td>' +
          '<td>' + esc(a.game_title) + '</td>' +
          '<td>' + statusChip(a.status) + '</td>' +
          '<td class="wh-btn-row">' +
            '<button type="button" class="wh-btn-sm" data-act="issue-account" data-id="' + esc(id) + '">Issue</button>' +
            '<button type="button" class="wh-btn-sm danger" data-act="reject-account" data-id="' + esc(id) + '">Reject</button>' +
          '</td>' +
        '</tr>';
      }).join('');
    } catch (e) {
      body.innerHTML = '<tr><td colspan="4" style="color:#ff6b7a">' + esc(e.message || 'Failed') + '</td></tr>';
    }
  }

  async function loadLedger() {
    const depBody = $('ledgerDepBody');
    const wdBody = $('ledgerWdBody');
    if (!dist || !depBody || !wdBody) return;
    depBody.innerHTML = '<tr><td colspan="6" style="color:var(--mute)">Loading…</td></tr>';
    wdBody.innerHTML = '<tr><td colspan="6" style="color:var(--mute)">Loading…</td></tr>';
    try {
      const [depR, wdR] = await Promise.all([
        safeApi('/transactions?distributor_id=' + encodeURIComponent(dist.public_id) + '&type=DEPOSIT&status=PENDING'),
        safeApi('/transactions?distributor_id=' + encodeURIComponent(dist.public_id) + '&type=WITHDRAW&status=PENDING,PENDING_COINS'),
      ]);
      const deps = depR.items || [];
      const wds = wdR.items || [];

      depBody.innerHTML = deps.length ? deps.map((tx) => {
        const id = tx.public_id || tx.id;
        return '<tr>' +
          '<td>' + esc(tx.user_email) + '</td>' +
          '<td>' + money(tx.amount) + '</td>' +
          '<td>' + esc(tx.gateway || '—') + '</td>' +
          '<td>' + esc(tx.game_title || '—') + '</td>' +
          '<td style="font-family:ui-monospace,monospace;font-size:.78rem">' + esc(tx.code || '—') + '</td>' +
          '<td class="wh-btn-row">' +
            '<button type="button" class="wh-btn-sm" data-act="approve-dep" data-id="' + esc(id) + '">Approve</button>' +
            '<button type="button" class="wh-btn-sm danger" data-act="reject-dep" data-id="' + esc(id) + '">Reject</button>' +
          '</td></tr>';
      }).join('') : '<tr><td colspan="6" style="color:var(--mute)">No pending deposits</td></tr>';

      wdBody.innerHTML = wds.length ? wds.map((tx) => {
        const id = tx.public_id || tx.id;
        const amt = Number(tx.amount || 0);
        return '<tr>' +
          '<td>' + esc(tx.user_email) + '</td>' +
          '<td>' + money(amt) + '</td>' +
          '<td>' + esc(tx.gateway || '—') + '</td>' +
          '<td style="font-family:ui-monospace,monospace;font-size:.78rem">' + esc(tx.code || '—') + '</td>' +
          '<td>' + statusChip(tx.status) + '</td>' +
          '<td class="wh-btn-row">' +
            '<button type="button" class="wh-btn-sm" data-act="mark-paid" data-id="' + esc(id) + '" data-amount="' + amt + '">Mark paid</button>' +
            '<button type="button" class="wh-btn-sm danger" data-act="fail-wd" data-id="' + esc(id) + '">Fail</button>' +
          '</td></tr>';
      }).join('') : '<tr><td colspan="6" style="color:var(--mute)">No pending withdrawals</td></tr>';
    } catch (e) {
      depBody.innerHTML = '<tr><td colspan="6" style="color:#ff6b7a">' + esc(e.message || 'Failed') + '</td></tr>';
      wdBody.innerHTML = '<tr><td colspan="6" style="color:#ff6b7a">' + esc(e.message || 'Failed') + '</td></tr>';
    }
  }

  async function loadGateways() {
    const body = $('gwBody');
    if (!dist || !body) return;
    body.innerHTML = '<tr><td colspan="5" style="color:var(--mute)">Loading…</td></tr>';
    try {
      const r = await safeApi('/distributors/' + encodeURIComponent(dist.public_id) + '/gateways');
      const items = r.items || [];
      if (!items.length) {
        body.innerHTML = '<tr><td colspan="5" style="color:var(--mute)">No gateways yet</td></tr>';
        return;
      }
      body.innerHTML = items.map((g) => (
        '<tr>' +
          '<td>' + esc(g.name || '—') + '</td>' +
          '<td>' + esc(g.tag || '—') + '</td>' +
          '<td>' + esc(g.phone || '—') + '</td>' +
          '<td style="font-size:.78rem;word-break:break-all">' + esc(g.redirect_url || '—') + '</td>' +
          '<td>' + (g.qr_image
            ? '<img src="' + esc(g.qr_image) + '" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:6px">'
            : '<span style="color:var(--mute)">—</span>') + '</td>' +
        '</tr>'
      )).join('');
    } catch (e) {
      body.innerHTML = '<tr><td colspan="5" style="color:#ff6b7a">' + esc(e.message || 'Failed') + '</td></tr>';
    }
  }

  async function ensureGames() {
    if (gamesCache.length) return gamesCache;
    try {
      const r = await safeApi('/games');
      gamesCache = r.items || [];
    } catch (_) {
      gamesCache = [];
    }
    return gamesCache;
  }

  async function renderStaffGames() {
    const box = $('staffGamesBox');
    if (!box) return;
    const games = await ensureGames();
    if (!games.length) {
      box.innerHTML = '<span style="color:var(--mute);font-size:.8rem">No games available</span>';
      return;
    }
    box.innerHTML = games.map((g) => {
      const id = g.public_id || g.id;
      return '<label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:var(--ink);cursor:pointer">' +
        '<input type="checkbox" name="allowed_game_ids" value="' + esc(id) + '"> ' +
        esc(g.title || id) +
      '</label>';
    }).join('');
  }

  async function loadStaff() {
    const body = $('staffBody');
    if (!dist || !body) return;
    body.innerHTML = '<tr><td colspan="5" style="color:var(--mute)">Loading…</td></tr>';
    await renderStaffGames();
    try {
      const r = await safeApi('/distributors/' + encodeURIComponent(dist.public_id) + '/staff');
      const items = r.items || [];
      if (!items.length) {
        body.innerHTML = '<tr><td colspan="5" style="color:var(--mute)">No staff yet</td></tr>';
        return;
      }
      const gameTitle = (id) => {
        const g = gamesCache.find((x) => (x.public_id || x.id) === id);
        return g ? g.title : id;
      };
      body.innerHTML = items.map((u) => {
        const roles = Array.isArray(u.roles) ? u.roles : (u.role ? [u.role] : []);
        const games = Array.isArray(u.allowed_game_ids) ? u.allowed_game_ids : [];
        return '<tr>' +
          '<td>' + esc(u.name || '—') + '</td>' +
          '<td>' + esc(u.email || '—') + '</td>' +
          '<td style="font-size:.78rem">' + esc(roles.join(', ') || '—') + '</td>' +
          '<td style="font-size:.78rem">' + esc(games.length ? games.map(gameTitle).join(', ') : 'All / none') + '</td>' +
          '<td>' + statusChip(u.status || 'ACTIVE') + '</td>' +
        '</tr>';
      }).join('');
    } catch (e) {
      body.innerHTML = '<tr><td colspan="5" style="color:#ff6b7a">' + esc(e.message || 'Failed') + '</td></tr>';
    }
  }

  function openSupportThread(email) {
    document.querySelectorAll('#supportThreadList .support-thread-btn').forEach((b) => {
      b.style.background = b.dataset.email === email ? 'rgba(62,224,178,.14)' : 'transparent';
    });
    const meta = $('supportThreadMeta');
    const body = $('supportThreadBody');
    const form = $('supportReplyForm');
    const emailInput = $('supportReplyEmail');
    if (meta) meta.textContent = email;
    if (emailInput) emailInput.value = email;
    if (form) form.style.display = 'grid';
    const msgs = supportThreadMap[email] || [];
    if (!body) return;
    if (!msgs.length) {
      body.innerHTML = '<p style="color:var(--mute);margin:0">No messages in thread</p>';
      return;
    }
    body.innerHTML = msgs.map((m) => {
      const mine = String(m.sender_type || '').toLowerCase() !== 'player';
      return '<div style="margin-bottom:.65rem;padding:.55rem .7rem;border-radius:10px;background:' +
        (mine ? 'rgba(62,224,178,.1)' : 'rgba(255,255,255,.04)') + '">' +
        '<div style="display:flex;justify-content:space-between;gap:.5rem;margin-bottom:.25rem">' +
          '<strong style="font-size:.78rem">' + esc(m.sender_type || 'player') + '</strong>' +
          '<span style="color:var(--mute);font-size:.72rem">' + esc(when(m.created_at)) + '</span>' +
        '</div>' +
        '<div style="font-size:.88rem;line-height:1.4;white-space:pre-wrap">' + esc(m.message || '') + '</div>' +
      '</div>';
    }).join('');
  }

  async function loadSupport() {
    const list = $('supportThreadList');
    if (!dist || !list) return;
    list.innerHTML = '<p style="color:var(--mute);padding:.65rem;margin:0">Loading…</p>';
    supportThreadMap = {};
    try {
      const r = await safeApi('/support');
      let items = r.items || [];
      // Best-effort filter: messages tagged with this distributor, else keep all
      const filtered = items.filter((m) => m.distributor_id && m.distributor_id === dist.public_id);
      if (filtered.length) items = filtered;

      const byEmail = {};
      items.forEach((m) => {
        const email = String(m.user_email || '').toLowerCase();
        if (!email) return;
        if (!byEmail[email]) byEmail[email] = [];
        byEmail[email].push(m);
      });
      // Newest first within each thread (API is latest(); reverse for chat order)
      Object.keys(byEmail).forEach((email) => {
        byEmail[email].sort((a, b) => new Date(a.created_at || 0) - new Date(b.created_at || 0));
      });
      supportThreadMap = byEmail;

      const emails = Object.keys(byEmail).sort((a, b) => {
        const ta = byEmail[a][byEmail[a].length - 1]?.created_at || '';
        const tb = byEmail[b][byEmail[b].length - 1]?.created_at || '';
        return String(tb).localeCompare(String(ta));
      });

      if (!emails.length) {
        list.innerHTML = '<p style="color:var(--mute);padding:.65rem;margin:0">No support threads</p>';
        $('supportThreadMeta').textContent = 'Select a thread';
        $('supportThreadBody').innerHTML = '<p style="color:var(--mute);margin:0">Pick a conversation on the left.</p>';
        $('supportReplyForm').style.display = 'none';
        return;
      }

      list.innerHTML = emails.map((email) => {
        const msgs = byEmail[email];
        const last = msgs[msgs.length - 1];
        const preview = String(last?.message || '').slice(0, 48);
        return '<button type="button" class="support-thread-btn" data-email="' + esc(email) + '" ' +
          'style="display:block;width:100%;text-align:left;border:0;background:transparent;color:var(--ink);padding:.7rem .65rem;border-radius:12px;cursor:pointer;margin-bottom:.2rem">' +
          '<strong style="display:block;font-size:.85rem">' + esc(email) + '</strong>' +
          '<span style="color:var(--mute);font-size:.72rem;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' +
            esc(preview) + '</span></button>';
      }).join('');

      list.querySelectorAll('.support-thread-btn').forEach((btn) => {
        btn.onclick = () => openSupportThread(btn.dataset.email);
      });
    } catch (e) {
      list.innerHTML = '<p style="color:#ff6b7a;padding:.65rem;margin:0">' + esc(e.message || 'Failed') + '</p>';
    }
  }

  async function loadDistPromos() {
    const body = $('distPromoBody');
    if (!body) return;
    try {
      const r = await safeApi('/promotions');
      const items = r.items || [];
      body.innerHTML = items.length ? items.map((p) => (
        '<tr><td>' + esc(p.title || '—') + '</td><td>' + esc(p.promo_type || p.type || '—') + '</td><td>' +
        esc(p.freeplay_amount ?? '—') + '</td><td>' + esc(p.bonus_percent ?? '—') + '</td></tr>'
      )).join('') : '<tr><td colspan="4" style="color:var(--mute)">No promotions</td></tr>';
    } catch (e) {
      body.innerHTML = '<tr><td colspan="4" style="color:#ff6b7a">' + esc(e.message || 'Failed') + '</td></tr>';
    }
  }

  async function loadShiftDash() {
    if (!dist) return;
    try {
      const r = await safeApi('/distributors/' + encodeURIComponent(dist.public_id) + '/stats');
      const s = r.stats || {};
      if ($('distShiftReq')) $('distShiftReq').textContent = String(s.pending_requests ?? s.pendingRequests ?? '—');
      if ($('distShiftCoins')) $('distShiftCoins').textContent = String(s.pending_coins ?? s.pendingCoins ?? '—');
      if ($('distShiftLedger')) $('distShiftLedger').textContent = String(s.pending_transactions ?? s.pendingTransactions ?? '—');
    } catch (_) {}
  }

  function loadPane(pane) {
    if (pane === 'overview') loadOverview();
    else if (pane === 'tx_logs') loadTxLogs();
    else if (pane === 'players') loadPlayers();
    else if (pane === 'cashout') { loadOverview(); loadCashoutHist(); }
    else if (pane === 'website_comm') loadWebPayHist();
    else if (pane === 'operations') loadOperations();
    else if (pane === 'requests') loadRequests();
    else if (pane === 'ledger') loadLedger();
    else if (pane === 'gateways') loadGateways();
    else if (pane === 'staff') loadStaff();
    else if (pane === 'support') loadSupport();
    else if (pane === 'promotions') loadDistPromos();
    else if (pane === 'shift_dashboard') loadShiftDash();
  }

  // —— Login ——
  $('distLogin').onsubmit = async (e) => {
    e.preventDefault();
    const err = $('loginErr');
    const btn = $('loginBtn');
    err.style.display = 'none';
    btn.disabled = true;
    btn.textContent = 'Signing in…';
    try {
      const payload = Object.fromEntries(new FormData(e.target));
      const data = await WH.api('/distributors/login', {
        method: 'POST',
        body: JSON.stringify(payload),
      });
      const d = data.distributor || data;
      dist = {
        public_id: d.public_id || d.id,
        name: d.name,
        type: String(d.type || 'A').toUpperCase(),
        email: d.email || payload.email,
        commission_rate: Number(d.commission_rate ?? d.commissionRate ?? 0),
        website_commission_rate: Number(d.website_commission_rate ?? d.websiteCommissionRate ?? 0),
      };
      if (!dist.public_id) throw new Error('Login response missing public_id');
      WH.toast('Welcome, ' + (dist.name || 'distributor'));
      showPortal();
    } catch (ex) {
      err.textContent = ex.message || 'Login failed';
      err.style.display = 'block';
      WH.toast(ex.message || 'Login failed', 'error');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Enter portal';
    }
  };

  $('logoutBtn').onclick = async () => {
    const ok = await WH.confirm('Sign out of the distributor portal?', 'Logout');
    if (!ok) return;
    showLogin();
    WH.toast('Signed out');
  };

  $('copyReferral').onclick = async () => {
    const link = referralLink();
    if (!link) return;
    try {
      await navigator.clipboard.writeText(link);
      WH.toast('Referral link copied');
    } catch (_) {
      await WH.alert(link, 'Copy this link');
    }
  };

  $('playerSearch')?.addEventListener('input', () => {
    const q = ($('playerSearch').value || '').trim().toLowerCase();
    document.querySelectorAll('#playersBody tr[data-search]').forEach((tr) => {
      tr.style.display = !q || tr.dataset.search.includes(q) ? '' : 'none';
    });
  });

  $('createPlayerForm').onsubmit = async (e) => {
    e.preventDefault();
    if (!dist) return;
    const body = Object.fromEntries(new FormData(e.target));
    try {
      await WH.api('/distributors/' + encodeURIComponent(dist.public_id) + '/players', {
        method: 'POST',
        body: JSON.stringify(body),
      });
      WH.toast('Player created');
      e.target.reset();
      loadPlayers();
      loadOverview();
    } catch (ex) {
      WH.toast(ex.message || 'Create failed', 'error');
    }
  };

  $('cashoutForm').onsubmit = async (e) => {
    e.preventDefault();
    if (!dist) return;
    const fd = Object.fromEntries(new FormData(e.target));
    const amount = Number(fd.amount);
    if (!(amount > 0)) {
      WH.toast('Enter a valid amount', 'error');
      return;
    }
    const ok = await WH.confirm(
      'Submit commission cashout for ' + money(amount) + ' via ' + (fd.gateway || 'gateway') + '?',
      'Confirm cashout'
    );
    if (!ok) return;
    try {
      await WH.api('/distributors/' + encodeURIComponent(dist.public_id) + '/cashout', {
        method: 'POST',
        body: JSON.stringify({ amount, gateway: fd.gateway, code: fd.code }),
      });
      WH.toast('Cashout submitted');
      e.target.reset();
      loadCashoutHist();
      loadOverview();
    } catch (ex) {
      WH.toast(ex.message || 'Cashout failed', 'error');
    }
  };

  function readFileAsDataUrl(file) {
    return new Promise((resolve, reject) => {
      if (!file) return resolve('');
      const reader = new FileReader();
      reader.onload = () => resolve(String(reader.result || ''));
      reader.onerror = () => reject(new Error('Could not read screenshot'));
      reader.readAsDataURL(file);
    });
  }

  $('websitePayForm').onsubmit = async (e) => {
    e.preventDefault();
    if (!dist) return;
    const fd = new FormData(e.target);
    const amount = Number(fd.get('amount'));
    const code = String(fd.get('code') || '').trim();
    if (!(amount > 0) || !code) {
      WH.toast('Amount and code are required', 'error');
      return;
    }
    const ok = await WH.confirm('Submit website commission payment of ' + money(amount) + '?', 'Confirm payment');
    if (!ok) return;
    try {
      const file = fd.get('screenshot_file');
      const screenshot = file && file.size ? await readFileAsDataUrl(file) : '';
      const payload = { amount, code };
      if (screenshot) payload.screenshot = screenshot;
      await WH.api('/distributors/' + encodeURIComponent(dist.public_id) + '/website-pay', {
        method: 'POST',
        body: JSON.stringify(payload),
      });
      WH.toast('Payment proof submitted');
      e.target.reset();
      loadWebPayHist();
    } catch (ex) {
      WH.toast(ex.message || 'Submit failed', 'error');
    }
  };

  $('gwForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!dist) return;
    const fd = new FormData(e.target);
    const name = String(fd.get('name') || '').trim();
    if (!name) {
      WH.toast('Name is required', 'error');
      return;
    }
    try {
      const file = fd.get('qr_image_file');
      const qr_image = file && file.size ? await readFileAsDataUrl(file) : '';
      const payload = {
        name,
        tag: String(fd.get('tag') || '').trim() || null,
        phone: String(fd.get('phone') || '').trim() || null,
        redirect_url: String(fd.get('redirect_url') || '').trim() || null,
      };
      if (qr_image) payload.qr_image = qr_image;
      await WH.api('/distributors/' + encodeURIComponent(dist.public_id) + '/gateways', {
        method: 'POST',
        body: JSON.stringify(payload),
      });
      WH.toast('Gateway added');
      e.target.reset();
      loadGateways();
    } catch (ex) {
      WH.toast(ex.message || 'Gateway create failed', 'error');
    }
  });

  $('staffForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!dist) return;
    const fd = new FormData(e.target);
    const roles = fd.getAll('roles').map(String);
    if (!roles.length) {
      WH.toast('Select at least one role', 'error');
      return;
    }
    const allowed_game_ids = fd.getAll('allowed_game_ids').map(String);
    const body = {
      name: String(fd.get('name') || '').trim(),
      email: String(fd.get('email') || '').trim().toLowerCase(),
      password: String(fd.get('password') || ''),
      roles,
      allowed_game_ids,
    };
    if (!body.name || !body.email || body.password.length < 6) {
      WH.toast('Name, email, and password (min 6) required', 'error');
      return;
    }
    try {
      await WH.api('/distributors/' + encodeURIComponent(dist.public_id) + '/staff', {
        method: 'POST',
        body: JSON.stringify(body),
      });
      WH.toast('Staff created');
      e.target.reset();
      loadStaff();
    } catch (ex) {
      WH.toast(ex.message || 'Staff create failed', 'error');
    }
  });

  $('supportReplyForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!dist) return;
    const email = ($('supportReplyEmail')?.value || '').trim();
    const message = ($('supportReplyMsg')?.value || '').trim();
    if (!email || !message) return;
    try {
      await WH.api('/support', {
        method: 'POST',
        body: JSON.stringify({
          user_email: email,
          message,
          sender_type: 'distributor',
        }),
      });
      WH.toast('Reply sent');
      $('supportReplyMsg').value = '';
      await loadSupport();
      openSupportThread(email);
    } catch (ex) {
      WH.toast(ex.message || 'Reply failed', 'error');
    }
  });

  // Delegated actions for ops / requests / ledger
  document.querySelector('.wh-admin-main')?.addEventListener('click', async (ev) => {
    const btn = ev.target.closest('button[data-act]');
    if (!btn) return;
    const act = btn.dataset.act;
    const id = btn.dataset.id;
    if (!act || !id) return;

    try {
      if (act === 'complete-coins') {
        const ok = await WH.confirm('Mark this coins job as completed / loaded?', 'Complete coins');
        if (!ok) return;
        await WH.api('/coins-notifications/' + encodeURIComponent(id), {
          method: 'PATCH',
          body: JSON.stringify({ status: 'COMPLETED' }),
        });
        WH.toast('Coins marked loaded');
        loadOperations();
      } else if (act === 'hold-coins') {
        const out = await WH.promptFields('Hold coins task', 'Explain why this allotment is on hold.', [
          { id: 'hold_note', label: 'Hold note', type: 'textarea', placeholder: 'Waiting for game ID / pool refill…', value: '' },
        ]);
        if (out === null) return;
        await WH.api('/coins-notifications/' + encodeURIComponent(id), {
          method: 'PATCH',
          body: JSON.stringify({ status: 'HOLD', hold_note: out.hold_note || 'On hold' }),
        });
        WH.toast('Coins on hold');
        loadOperations();
      } else if (act === 'issue-account') {
        const out = await WH.promptFields('Issue game account', 'Enter credentials for the player.', [
          { id: 'username', label: 'Game username', placeholder: 'player_id' },
          { id: 'password', label: 'Game password', type: 'text', placeholder: 'temp password' },
        ]);
        if (!out || !out.username?.trim() || !out.password?.trim()) {
          if (out) WH.toast('Username and password required', 'error');
          return;
        }
        await WH.api('/account-requests/' + encodeURIComponent(id), {
          method: 'PATCH',
          body: JSON.stringify({
            status: 'READY',
            game_account_username: out.username.trim(),
            game_account_password: out.password.trim(),
          }),
        });
        WH.toast('Account issued');
        loadRequests();
      } else if (act === 'reject-account') {
        const out = await WH.promptFields('Reject account request', 'Optional note for the player.', [
          { id: 'note', label: 'Reason', type: 'textarea', placeholder: 'Unable to create account…', value: '' },
        ]);
        if (out === null) return;
        await WH.api('/account-requests/' + encodeURIComponent(id), {
          method: 'PATCH',
          body: JSON.stringify({ status: 'REJECTED', rejection_reason: out.note || 'Rejected' }),
        });
        WH.toast('Request rejected');
        loadRequests();
      } else if (act === 'approve-dep') {
        const ok = await WH.confirm('Approve this deposit? A coins task will be created.', 'Approve deposit');
        if (!ok) return;
        await WH.api('/transactions/' + encodeURIComponent(id), {
          method: 'PATCH',
          body: JSON.stringify({ status: 'SUCCESS' }),
        });
        WH.toast('Deposit approved');
        loadLedger();
      } else if (act === 'reject-dep') {
        const out = await WH.promptFields('Reject deposit', 'Optional note for the player / ledger.', [
          { id: 'note', label: 'Reason', type: 'textarea', placeholder: 'Payment proof invalid…', value: '' },
        ]);
        if (out === null) return;
        await WH.api('/transactions/' + encodeURIComponent(id), {
          method: 'PATCH',
          body: JSON.stringify({ status: 'FAILED', note: out.note || 'Rejected' }),
        });
        WH.toast('Deposit rejected');
        loadLedger();
      } else if (act === 'mark-paid') {
        const amount = Number(btn.dataset.amount || 0);
        const ok = await WH.confirm('Mark this withdrawal as fully paid (' + money(amount) + ')?', 'Mark paid');
        if (!ok) return;
        await WH.api('/transactions/' + encodeURIComponent(id), {
          method: 'PATCH',
          body: JSON.stringify({ status: 'SUCCESS', payout_sent: amount }),
        });
        WH.toast('Marked paid');
        loadLedger();
      } else if (act === 'fail-wd') {
        const out = await WH.promptFields('Fail withdrawal', 'Note for the player / ledger.', [
          { id: 'note', label: 'Reason', type: 'textarea', placeholder: 'Invalid tag / insufficient playthrough…', value: '' },
        ]);
        if (out === null) return;
        await WH.api('/transactions/' + encodeURIComponent(id), {
          method: 'PATCH',
          body: JSON.stringify({ status: 'FAILED', note: out.note || 'Failed' }),
        });
        WH.toast('Withdrawal failed');
        loadLedger();
      }
    } catch (ex) {
      WH.toast(ex.message || 'Action failed', 'error');
    }
  });
})();
</script>
@endpush

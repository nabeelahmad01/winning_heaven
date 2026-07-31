@extends('layouts.app')
@section('title', 'Affiliate — Winning Heaven')
@section('content')
@php
  $bg = $frontend['login_bg_url'] ?? '/brand/bg.png';
  $logo = $frontend['logo_url'] ?? '/brand/logo.png';
@endphp

{{-- ========== AUTH (login / register) ========== --}}
<div class="wh-auth" id="authGate" style="--wh-bg:url('{{ asset(ltrim($bg,'/')) }}')">
  <div class="wh-auth__sky"></div>
  <div class="wh-auth__top">
    <div class="wh-brand-mark">
      <img src="{{ asset(ltrim($logo,'/')) }}" alt="">
      <div><strong>Winning Heaven</strong><span>Affiliate / agent</span></div>
    </div>
    <a class="wh-chip" href="/login">Player</a>
  </div>
  <div class="wh-auth__hero">
    <h1>Affiliate<br><em>hub</em></h1>
    <p>Agents, sub-distributors, campaigns — same network rules.</p>
  </div>
  <div class="wh-sheet" style="max-width:440px;margin:0 auto">
    <div class="wh-tabs">
      <button type="button" class="is-on" data-auth-tab="login">Login</button>
      <button type="button" data-auth-tab="register">Register</button>
    </div>

    <div class="wh-panel is-on" id="auth-login">
      <div class="wh-err" id="loginErr" style="display:none"></div>
      <form id="agentLogin">
        <div class="wh-field">
          <label>Email</label>
          <div class="box"><i class="fa-solid fa-envelope"></i><input type="email" name="email" required placeholder="agent@winningheaven.com"></div>
        </div>
        <div class="wh-field">
          <label>Password</label>
          <div class="box"><i class="fa-solid fa-lock"></i><input type="password" name="password" required placeholder="••••••••"></div>
        </div>
        <button class="wh-primary" type="submit" id="loginBtn">Enter portal</button>
      </form>
    </div>

    <div class="wh-panel" id="auth-register">
      <div class="wh-err" id="regErr" style="display:none"></div>
      <form id="agentRegister">
        <div class="wh-field">
          <label>Full name</label>
          <div class="box"><i class="fa-solid fa-user"></i><input name="name" required placeholder="Your name"></div>
        </div>
        <div class="wh-field">
          <label>Email</label>
          <div class="box"><i class="fa-solid fa-envelope"></i><input type="email" name="email" required placeholder="agent@winningheaven.com"></div>
        </div>
        <div class="wh-field">
          <label>Password</label>
          <div class="box"><i class="fa-solid fa-lock"></i><input type="password" name="password" required minlength="6" placeholder="Min 6 characters"></div>
        </div>
        <div class="wh-field">
          <label>Custom promo code (optional)</label>
          <div class="box"><i class="fa-solid fa-tag"></i><input name="agent_code" placeholder="e.g. MYCODE10"></div>
        </div>
        <p style="color:var(--mute);font-size:.78rem;margin:0 0 .9rem">Registers as sub-distributor at 10% commission.</p>
        <button class="wh-primary" type="submit" id="regBtn">Create account</button>
      </form>
    </div>
  </div>
</div>

{{-- ========== DESK (after login) ========== --}}
<div class="wh-admin" id="agentDesk" style="display:none">
  <aside class="wh-aside">
    <div class="brand">
      <img src="{{ asset(ltrim($logo,'/')) }}" alt="">
      <div>
        <strong style="display:block">Winning Heaven</strong>
        <span style="color:var(--mute);font-size:.75rem">Affiliate Desk</span>
      </div>
    </div>
    <nav class="wh-aside-nav">
      <button type="button" class="is-on" data-pane="dash" data-url="dashboard"><i class="fa-solid fa-house"></i> Dashboard</button>
      <button type="button" data-pane="team" data-url="team"><i class="fa-solid fa-users"></i> Team</button>
      <button type="button" data-pane="daily" data-url="daily_transactions"><i class="fa-solid fa-calendar-day"></i> Daily Transactions</button>
      <button type="button" data-pane="signup" data-url="signup_report"><i class="fa-solid fa-user-plus"></i> Signup Report</button>
      <button type="button" data-pane="cashout" data-url="cashout"><i class="fa-solid fa-money-bill-transfer"></i> Cashout</button>
      <button type="button" data-pane="ads" data-url="ads_request"><i class="fa-solid fa-bullhorn"></i> Ads Request</button>
      <button type="button" data-pane="password" data-url="change_password"><i class="fa-solid fa-key"></i> Change password</button>
    </nav>
    <div class="wh-aside-foot">
      <div style="padding:.35rem;color:var(--mute);font-size:.75rem" id="asideMeta">—</div>
      <button type="button" id="logoutBtn"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
    </div>
  </aside>

  <div class="wh-admin-main">
    {{-- Dashboard --}}
    <div class="wh-pane is-on" id="pane-dash">
      <h1 style="font-family:var(--font-display);margin:0 0 .35rem">Affiliate dashboard</h1>
      <p style="color:var(--mute);margin:0 0 1rem" id="dashSubtitle">Your network performance</p>
      <div class="wh-stats">
        <div class="wh-tile">
          <div style="color:var(--mute);font-size:.8rem">Players</div>
          <div style="font-size:1.8rem;font-weight:800" id="statPlayers">—</div>
        </div>
        <div class="wh-tile">
          <div style="color:var(--mute);font-size:.8rem">Deposits</div>
          <div style="font-size:1.8rem;font-weight:800" id="statDeposits">—</div>
        </div>
        <div class="wh-tile">
          <div style="color:var(--mute);font-size:.8rem">Withdrawals</div>
          <div style="font-size:1.8rem;font-weight:800" id="statWithdrawals">—</div>
        </div>
        <div class="wh-tile">
          <div style="color:var(--mute);font-size:.8rem">Available commission</div>
          <div style="font-size:1.8rem;font-weight:800" id="statCommission">—</div>
        </div>
      </div>

      <div class="wh-tile" style="margin-top:.5rem">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
          <div style="flex:1;min-width:220px">
            <div style="color:var(--mute);font-size:.75rem;text-transform:uppercase;font-weight:700;margin-bottom:.35rem">Invite link</div>
            <code id="inviteLink" style="display:block;word-break:break-all;font-size:.85rem;color:var(--sand)">—</code>
            <p style="color:var(--mute);font-size:.78rem;margin:.55rem 0 0">Share with players — opens register with your agent code.</p>
          </div>
          <button type="button" class="wh-btn-sm" id="copyInviteBtn"><i class="fa-solid fa-copy"></i> Copy</button>
        </div>
      </div>
    </div>

    {{-- Team --}}
    <div class="wh-pane" id="pane-team">
      <h2 style="font-family:var(--font-display);margin:0 0 .35rem">Team</h2>
      <p style="color:var(--mute);margin:0 0 1rem">Create a sub-agent under your code via <code>POST /agents</code> with <code>parent_agent_code</code>.</p>

      <div class="wh-tile" style="margin-bottom:1rem">
        <h3 style="font-family:var(--font-display);margin:0 0 .85rem;font-size:1.05rem">Create sub-agent</h3>
        <form id="teamCreateForm">
          <div class="wh-field">
            <label>Name</label>
            <div class="box"><i class="fa-solid fa-user"></i><input name="name" required placeholder="Sub-agent name"></div>
          </div>
          <div class="wh-field">
            <label>Email</label>
            <div class="box"><i class="fa-solid fa-envelope"></i><input type="email" name="email" required placeholder="sub@winningheaven.com"></div>
          </div>
          <div class="wh-field">
            <label>Password</label>
            <div class="box"><i class="fa-solid fa-lock"></i><input type="password" name="password" required minlength="6" placeholder="Min 6 characters"></div>
          </div>
          <div class="wh-field">
            <label>Promo code (optional)</label>
            <div class="box"><i class="fa-solid fa-tag"></i><input name="agent_code" placeholder="Leave blank to auto-generate"></div>
          </div>
          <input type="hidden" name="account_type" value="agent">
          <button class="wh-primary" type="submit" style="max-width:280px">Create team member</button>
        </form>
      </div>

      <div class="wh-tile">
        <p style="margin:0;color:var(--mute);font-size:.88rem;line-height:1.5">
          <i class="fa-solid fa-circle-info" style="color:var(--aqua);margin-right:.35rem"></i>
          New members inherit your network. They sign in at this affiliate portal with the email and password you set.
          Parent code is sent automatically as <strong id="teamParentNote" style="color:var(--ink)">—</strong>.
        </p>
      </div>
    </div>

    {{-- Daily Transactions --}}
    <div class="wh-pane" id="pane-daily">
      <h2 style="font-family:var(--font-display);margin:0 0 .35rem">Daily transactions</h2>
      <p style="color:var(--mute);margin:0 0 1rem">Player deposit / withdraw activity under your agent code for a single day.</p>
      <div class="wh-tile" style="margin-bottom:1rem;max-width:420px">
        <form id="dailyTxForm" style="display:flex;gap:.75rem;align-items:end;flex-wrap:wrap">
          <div class="wh-field" style="margin:0;flex:1;min-width:160px">
            <label>Date</label>
            <div class="box"><input type="date" name="date" id="dailyTxDate" required></div>
          </div>
          <button class="wh-primary" type="submit" style="max-width:140px;margin:0">Load</button>
        </form>
      </div>
      <div class="wh-tile" style="overflow:auto">
        <table class="wh-table">
          <thead>
            <tr><th>When</th><th>Player</th><th>Type</th><th>Amount</th><th>Status</th><th>Gateway</th></tr>
          </thead>
          <tbody id="dailyTxBody">
            <tr><td colspan="6" style="color:var(--mute)">Pick a date and load</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    {{-- Signup Report --}}
    <div class="wh-pane" id="pane-signup">
      <h2 style="font-family:var(--font-display);margin:0 0 .35rem">Signup report</h2>
      <p style="color:var(--mute);margin:0 0 1rem">Players who registered under your code in a date range.</p>
      <div class="wh-tile" style="margin-bottom:1rem;max-width:520px">
        <form id="signupReportForm" style="display:flex;gap:.75rem;align-items:end;flex-wrap:wrap">
          <div class="wh-field" style="margin:0;flex:1;min-width:140px">
            <label>From</label>
            <div class="box"><input type="date" name="fromDate" id="signupFrom" required></div>
          </div>
          <div class="wh-field" style="margin:0;flex:1;min-width:140px">
            <label>To</label>
            <div class="box"><input type="date" name="toDate" id="signupTo" required></div>
          </div>
          <button class="wh-primary" type="submit" style="max-width:140px;margin:0">Load</button>
        </form>
        <div id="signupSummary" style="display:flex;gap:1.25rem;flex-wrap:wrap;margin-top:1rem;color:var(--mute);font-size:.85rem"></div>
      </div>
      <div class="wh-tile" style="overflow:auto">
        <table class="wh-table">
          <thead>
            <tr><th>Name</th><th>Email</th><th>Joined</th><th>Campaign</th><th>Status</th></tr>
          </thead>
          <tbody id="signupBody">
            <tr><td colspan="5" style="color:var(--mute)">Pick a range and load</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    {{-- Cashout --}}
    <div class="wh-pane" id="pane-cashout">
      <h2 style="font-family:var(--font-display);margin:0 0 .35rem">Commission cashout</h2>
      <p style="color:var(--mute);margin:0 0 1rem">Request a payout of earned commission. Available balance shown on the dashboard.</p>
      <div class="wh-tile" style="max-width:520px">
        <div style="margin-bottom:1rem">
          <span style="color:var(--mute);font-size:.75rem;text-transform:uppercase;font-weight:700">Available commission</span>
          <div style="font-size:1.5rem;font-weight:800" id="cashoutBalance">$0.00</div>
        </div>
        <form id="cashoutForm">
          <div class="wh-field">
            <label>Amount (USD)</label>
            <div class="box"><i class="fa-solid fa-dollar-sign"></i><input type="number" name="amount" step="0.01" min="1" required placeholder="50.00"></div>
          </div>
          <div class="wh-field">
            <label>Gateway / method</label>
            <select name="gateway" required style="width:100%;padding:.8rem;border-radius:12px;background:rgba(0,0,0,.35);color:#fff;border:1px solid var(--line)">
              <option value="CashApp">CashApp</option>
              <option value="Venmo">Venmo</option>
              <option value="Chime">Chime</option>
              <option value="Bank">Bank transfer</option>
              <option value="USDT (TRC20)">USDT (TRC20)</option>
              <option value="USDT (BEP20)">USDT (BEP20)</option>
            </select>
          </div>
          <div class="wh-field">
            <label>Account / wallet / tag</label>
            <div class="box"><i class="fa-solid fa-hashtag"></i><input name="code" required placeholder="Account number, $cashtag, or wallet"></div>
          </div>
          <div class="wh-field">
            <label>Note (optional)</label>
            <div class="box"><i class="fa-solid fa-pen"></i><input name="note" placeholder="Account holder name or memo"></div>
          </div>
          <button class="wh-primary" type="submit" style="max-width:280px">Submit cashout</button>
        </form>
      </div>
    </div>

    {{-- Ads Request --}}
    <div class="wh-pane" id="pane-ads">
      <h2 style="font-family:var(--font-display);margin:0 0 .35rem">Ads request</h2>
      <p style="color:var(--mute);margin:0 0 1rem">Submit a Facebook campaign budget request and track status.</p>

      <div class="wh-tile" style="margin-bottom:1rem;max-width:560px">
        <h3 style="font-family:var(--font-display);margin:0 0 .85rem;font-size:1.05rem">New campaign</h3>
        <form id="campForm">
          <input type="hidden" name="agent_code" id="campAgentCode">
          <div class="wh-field">
            <label>Campaign name</label>
            <div class="box"><i class="fa-solid fa-bullhorn"></i><input name="campaign_name" required placeholder="Summer promo"></div>
          </div>
          <div class="wh-field">
            <label>Budget (USD)</label>
            <div class="box"><i class="fa-solid fa-dollar-sign"></i><input name="budget" type="number" step="0.01" min="1" required placeholder="100"></div>
          </div>
          <div class="wh-field">
            <label>Facebook page link</label>
            <div class="box"><i class="fa-brands fa-facebook"></i><input name="facebook_page_link" type="url" placeholder="https://facebook.com/yourpage"></div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
            <div class="wh-field">
              <label>Start</label>
              <div class="box"><input name="start_date" type="datetime-local"></div>
            </div>
            <div class="wh-field">
              <label>End</label>
              <div class="box"><input name="end_date" type="datetime-local"></div>
            </div>
          </div>
          <div class="wh-field">
            <label>Notes</label>
            <div class="box"><i class="fa-solid fa-note-sticky"></i><input name="notes" placeholder="Targeting, creatives, etc."></div>
          </div>
          <button class="wh-primary" type="submit" style="max-width:280px">Submit campaign request</button>
        </form>
      </div>

      <div class="wh-tile">
        <h3 style="font-family:var(--font-display);margin:0 0 .85rem;font-size:1.05rem">Your requests</h3>
        <div style="overflow-x:auto">
          <table class="wh-table">
            <thead>
              <tr>
                <th>Campaign</th>
                <th>Budget</th>
                <th>Status</th>
                <th>Link</th>
                <th>Submitted</th>
              </tr>
            </thead>
            <tbody id="campList">
              <tr><td colspan="5" style="color:var(--mute)">No campaign requests yet</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Change password --}}
    <div class="wh-pane" id="pane-password">
      <h2 style="font-family:var(--font-display);margin:0 0 .35rem">Change password</h2>
      <p style="color:var(--mute);margin:0 0 1rem">Update your affiliate portal credentials.</p>
      <div class="wh-tile" style="max-width:480px">
        <form id="changePwForm">
          <div class="wh-field">
            <label>Current password</label>
            <div class="box"><i class="fa-solid fa-lock"></i><input type="password" name="current" required></div>
          </div>
          <div class="wh-field">
            <label>New password</label>
            <div class="box"><i class="fa-solid fa-key"></i><input type="password" name="password" required minlength="6"></div>
          </div>
          <div class="wh-field">
            <label>Confirm new password</label>
            <div class="box"><i class="fa-solid fa-key"></i><input type="password" name="confirm" required minlength="6"></div>
          </div>
          <button class="wh-primary" type="submit" style="max-width:280px">Update password</button>
        </form>
        <p style="color:var(--mute);font-size:.78rem;margin:1rem 0 0;line-height:1.45">
          If the password API is unavailable, contact HQ support to reset your access.
        </p>
      </div>
    </div>
  </div>

  <button type="button" class="wh-fab" id="supportFab" title="Support"><i class="fa-solid fa-headset"></i></button>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const SESSION_KEY = 'wh_agent_session';
  let agent = null;

  const money = (n) => '$' + Number(n || 0).toFixed(2);
  const inviteUrl = (code) => location.origin + '/login?tab=register&agent=' + encodeURIComponent(code || '');
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  })[c]);
  const when = (v) => {
    if (!v) return '—';
    try { return new Date(v).toLocaleString(); } catch (_) { return String(v); }
  };
  const todayISO = () => {
    const d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  };
  const daysAgoISO = (n) => {
    const d = new Date();
    d.setDate(d.getDate() - n);
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  };
  const availCommission = (st) =>
    (st && st.available_commission != null) ? st.available_commission : (st?.commission ?? 0);

  function showAuthTab(name) {
    document.querySelectorAll('[data-auth-tab]').forEach((b) => b.classList.toggle('is-on', b.dataset.authTab === name));
    document.querySelectorAll('#authGate .wh-panel').forEach((p) => p.classList.remove('is-on'));
    document.getElementById('auth-' + name)?.classList.add('is-on');
  }

  function showPane(name, push) {
    document.querySelectorAll('#agentDesk [data-pane]').forEach((b) => b.classList.toggle('is-on', b.dataset.pane === name));
    document.querySelectorAll('#agentDesk .wh-pane').forEach((p) => p.classList.remove('is-on'));
    document.getElementById('pane-' + name)?.classList.add('is-on');
    if (push !== false) {
      const btn = document.querySelector('#agentDesk [data-pane="' + name + '"]');
      const urlKey = (btn && btn.dataset.url) || name;
      const url = (name === 'team' && window.__WH_TEAM_CREATE) ? '/affiliate/team/create' : ('/affiliate/' + urlKey);
      if (window.location.pathname !== url) {
        history.replaceState({ affiliateTab: name }, '', url);
      }
    }
    if (name === 'ads') loadCampaigns();
    if (name === 'daily') {
      const inp = document.getElementById('dailyTxDate');
      if (inp && !inp.value) inp.value = todayISO();
    }
    if (name === 'signup') {
      const from = document.getElementById('signupFrom');
      const to = document.getElementById('signupTo');
      if (from && !from.value) from.value = daysAgoISO(30);
      if (to && !to.value) to.value = todayISO();
    }
    if (name === 'cashout') loadStats();
  }

  window.addEventListener('popstate', (e) => {
    const pane = (e.state && e.state.affiliateTab) || (window.location.pathname.split('/').filter(Boolean)[1]) || 'dash';
    const map = { dashboard: 'dash', daily_transactions: 'daily', signup_report: 'signup', ads_request: 'ads', change_password: 'password', team: 'team', cashout: 'cashout' };
    showPane(map[pane] || pane || 'dash', false);
  });

  window.__WH_TEAM_CREATE = {!! json_encode(!empty($teamCreate)) !!};
  const AFF_INITIAL = {!! json_encode($initialTab ?? null) !!};

  function setGate(loggedIn) {
    document.getElementById('authGate').style.display = loggedIn ? 'none' : '';
    document.getElementById('agentDesk').style.display = loggedIn ? '' : 'none';
  }

  function saveSession(a) {
    agent = a;
    localStorage.setItem(SESSION_KEY, JSON.stringify(a));
  }

  function clearSession() {
    agent = null;
    localStorage.removeItem(SESSION_KEY);
  }

  function enterDesk(a) {
    saveSession(a);
    const code = a.agent_code || '';
    document.getElementById('asideMeta').textContent =
      (a.name || 'Agent') + ' · ' + code + (a.account_type ? ' · ' + a.account_type : '');
    document.getElementById('dashSubtitle').textContent =
      (a.name || 'Agent') + ' · code ' + code + (a.commission_rate != null ? ' · ' + a.commission_rate + '% commission' : '');
    document.getElementById('inviteLink').textContent = inviteUrl(code);
    document.getElementById('campAgentCode').value = code;
    document.getElementById('teamParentNote').textContent = code;
    setGate(true);
    const urlMap = { dashboard: 'dash', daily_transactions: 'daily', signup_report: 'signup', ads_request: 'ads', change_password: 'password', team: 'team', cashout: 'cashout', dash: 'dash' };
    const start = urlMap[AFF_INITIAL] || AFF_INITIAL || 'dash';
    showPane(['dash','team','daily','signup','cashout','ads','password'].includes(start) ? start : 'dash', true);
    loadStats();
  }

  async function loadStats() {
    if (!agent?.agent_code) return;
    try {
      const s = await WH.api('/agents/' + encodeURIComponent(agent.agent_code) + '/stats');
      const st = s.stats || {};
      const avail = availCommission(st);
      document.getElementById('statPlayers').textContent = st.players ?? 0;
      document.getElementById('statDeposits').textContent = money(st.deposits);
      document.getElementById('statWithdrawals').textContent = money(st.withdrawals);
      document.getElementById('statCommission').textContent = money(avail);
      document.getElementById('cashoutBalance').textContent = money(avail);
    } catch (e) {
      WH.toast(e.message || 'Could not load stats', 'error');
    }
  }

  async function loadDailyTx(date) {
    if (!agent?.agent_code) return;
    const tbody = document.getElementById('dailyTxBody');
    tbody.innerHTML = '<tr><td colspan="6" style="color:var(--mute)">Loading…</td></tr>';
    try {
      const r = await WH.api(
        '/agents/' + encodeURIComponent(agent.agent_code) + '/daily-transactions?date=' + encodeURIComponent(date)
      );
      const items = r.items || [];
      if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="color:var(--mute)">No transactions on ' + esc(date) + '</td></tr>';
        return;
      }
      tbody.innerHTML = items.map((tx) => (
        '<tr>' +
          '<td>' + esc(when(tx.created_at)) + '</td>' +
          '<td>' + esc(tx.user_email || '—') + '</td>' +
          '<td>' + esc(tx.type || '—') + '</td>' +
          '<td>' + money(tx.amount) + '</td>' +
          '<td><span class="wh-badge">' + esc(tx.status || '—') + '</span></td>' +
          '<td>' + esc(tx.gateway || '—') + '</td>' +
        '</tr>'
      )).join('');
    } catch (e) {
      tbody.innerHTML = '<tr><td colspan="6" style="color:#ff6b7a">' + esc(e.message || 'Failed') + '</td></tr>';
      WH.toast(e.message || 'Daily transactions failed', 'error');
    }
  }

  async function loadSignupReport(fromDate, toDate) {
    if (!agent?.agent_code) return;
    const tbody = document.getElementById('signupBody');
    const summary = document.getElementById('signupSummary');
    tbody.innerHTML = '<tr><td colspan="5" style="color:var(--mute)">Loading…</td></tr>';
    summary.textContent = '';
    try {
      const r = await WH.api(
        '/agents/' + encodeURIComponent(agent.agent_code) +
        '/signup-report?fromDate=' + encodeURIComponent(fromDate) +
        '&toDate=' + encodeURIComponent(toDate)
      );
      const players = r.players || [];
      summary.innerHTML =
        '<span><strong style="color:var(--ink)">' + (r.signups ?? players.length) + '</strong> signups</span>' +
        '<span><strong style="color:var(--ink)">' + (r.deposited_players ?? 0) + '</strong> deposited</span>' +
        '<span style="color:var(--mute)">' + esc(r.from || fromDate) + ' → ' + esc(r.to || toDate) + '</span>';
      if (!players.length) {
        tbody.innerHTML = '<tr><td colspan="5" style="color:var(--mute)">No signups in range</td></tr>';
        return;
      }
      tbody.innerHTML = players.map((p) => (
        '<tr>' +
          '<td>' + esc(p.name || '—') + '</td>' +
          '<td>' + esc(p.email || '—') + '</td>' +
          '<td>' + esc(when(p.created_at)) + '</td>' +
          '<td>' + esc(p.campaign || '—') + '</td>' +
          '<td><span class="wh-badge">' + esc(p.status || '—') + '</span></td>' +
        '</tr>'
      )).join('');
    } catch (e) {
      tbody.innerHTML = '<tr><td colspan="5" style="color:#ff6b7a">' + esc(e.message || 'Failed') + '</td></tr>';
      WH.toast(e.message || 'Signup report failed', 'error');
    }
  }

  async function loadCampaigns() {
    if (!agent?.email) return;
    const tbody = document.getElementById('campList');
    try {
      const r = await WH.api('/campaign-requests?agent_email=' + encodeURIComponent(agent.email));
      const items = r.items || [];
      if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="5" style="color:var(--mute)">No campaign requests yet</td></tr>';
        return;
      }
      tbody.innerHTML = items.map((c) => {
        const link = c.tracking_link
          ? '<a href="' + c.tracking_link + '" target="_blank" rel="noopener" style="color:var(--aqua)">Open</a>'
          : '<span style="color:var(--mute)">—</span>';
        const when = c.created_at ? new Date(c.created_at).toLocaleDateString() : '—';
        return '<tr>' +
          '<td>' + (c.campaign_name || '—') + '</td>' +
          '<td>' + money(c.budget) + '</td>' +
          '<td><span class="wh-badge">' + (c.status || 'PENDING') + '</span></td>' +
          '<td>' + link + '</td>' +
          '<td>' + when + '</td>' +
          '</tr>';
      }).join('');
    } catch (e) {
      tbody.innerHTML = '<tr><td colspan="5" style="color:var(--mute)">Unable to load campaigns</td></tr>';
      WH.toast(e.message || 'Campaign list failed', 'error');
    }
  }

  document.querySelectorAll('[data-auth-tab]').forEach((b) => {
    b.addEventListener('click', () => showAuthTab(b.dataset.authTab));
  });

  document.querySelectorAll('#agentDesk [data-pane]').forEach((b) => {
    b.addEventListener('click', () => showPane(b.dataset.pane));
  });

  document.getElementById('agentLogin').onsubmit = async (e) => {
    e.preventDefault();
    const err = document.getElementById('loginErr');
    err.style.display = 'none';
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    try {
      const data = await WH.api('/agents/login', {
        method: 'POST',
        body: JSON.stringify(Object.fromEntries(new FormData(e.target)))
      });
      enterDesk(data.agent);
      WH.toast('Welcome back');
    } catch (ex) {
      err.style.display = 'block';
      err.textContent = ex.data?.error || ex.message || 'Login failed';
    } finally {
      btn.disabled = false;
    }
  };

  document.getElementById('agentRegister').onsubmit = async (e) => {
    e.preventDefault();
    const err = document.getElementById('regErr');
    err.style.display = 'none';
    const btn = document.getElementById('regBtn');
    btn.disabled = true;
    const fd = Object.fromEntries(new FormData(e.target));
    const body = {
      name: (fd.name || '').trim(),
      email: (fd.email || '').trim().toLowerCase(),
      password: fd.password,
      account_type: 'sub-distributor',
      commission_rate: 10
    };
    if (fd.agent_code?.trim()) body.agent_code = fd.agent_code.trim();
    try {
      await WH.api('/agents', { method: 'POST', body: JSON.stringify(body) });
      const login = await WH.api('/agents/login', {
        method: 'POST',
        body: JSON.stringify({ email: body.email, password: body.password })
      });
      enterDesk(login.agent);
      WH.toast('Account created');
    } catch (ex) {
      err.style.display = 'block';
      const errs = ex.data?.errors;
      err.textContent = errs
        ? Object.values(errs).flat().join(' ')
        : (ex.data?.error || ex.message || 'Registration failed');
    } finally {
      btn.disabled = false;
    }
  };

  document.getElementById('copyInviteBtn').onclick = () => {
    const link = document.getElementById('inviteLink').textContent;
    if (!link || link === '—') return;
    navigator.clipboard?.writeText(link).then(() => WH.toast('Invite link copied')).catch(() => {
      WH.prompt('Copy this link', link, 'Invite link');
    });
  };

  document.getElementById('teamCreateForm').onsubmit = async (e) => {
    e.preventDefault();
    if (!agent?.agent_code) return WH.toast('Not logged in', 'error');
    const fd = Object.fromEntries(new FormData(e.target));
    const body = {
      name: (fd.name || '').trim(),
      email: (fd.email || '').trim().toLowerCase(),
      password: fd.password,
      account_type: fd.account_type || 'agent',
      parent_agent_code: agent.agent_code
    };
    if (fd.agent_code?.trim()) body.agent_code = fd.agent_code.trim();
    try {
      const r = await WH.api('/agents', { method: 'POST', body: JSON.stringify(body) });
      const code = r.item?.agent_code || '—';
      WH.toast('Team member created · ' + code);
      e.target.reset();
      await WH.alert('Sub-agent created with code ' + code + '. They can sign in on this portal.', 'Team');
    } catch (ex) {
      WH.toast(ex.data?.error || ex.message || 'Create failed', 'error');
    }
  };

  document.getElementById('cashoutForm').onsubmit = async (e) => {
    e.preventDefault();
    if (!agent?.agent_code) return WH.toast('Not logged in', 'error');
    const fd = Object.fromEntries(new FormData(e.target));
    const body = {
      amount: Number(fd.amount),
      gateway: fd.gateway,
      code: (fd.code || '').trim(),
      note: (fd.note || '').trim() || ('Affiliate cashout — ' + (agent.name || agent.email))
    };
    if (!body.amount || body.amount <= 0) return WH.toast('Enter a valid amount', 'error');
    try {
      await WH.api('/agents/' + encodeURIComponent(agent.agent_code) + '/cashout', {
        method: 'POST',
        body: JSON.stringify(body)
      });
      WH.toast('Cashout request submitted');
      e.target.reset();
      loadStats();
    } catch (ex) {
      WH.toast(ex.data?.error || ex.message || 'Cashout failed', 'error');
    }
  };

  document.getElementById('campForm').onsubmit = async (e) => {
    e.preventDefault();
    const fd = Object.fromEntries(new FormData(e.target));
    fd.budget = Number(fd.budget);
    if (!fd.agent_code) fd.agent_code = agent?.agent_code;
    try {
      await WH.api('/campaign-requests', { method: 'POST', body: JSON.stringify(fd) });
      WH.toast('Campaign request sent to HQ');
      e.target.reset();
      document.getElementById('campAgentCode').value = agent?.agent_code || '';
      loadCampaigns();
    } catch (ex) {
      WH.toast(ex.data?.error || ex.message || 'Campaign submit failed', 'error');
    }
  };

  document.getElementById('changePwForm').onsubmit = async (e) => {
    e.preventDefault();
    if (!agent) return WH.toast('Not logged in', 'error');
    const fd = Object.fromEntries(new FormData(e.target));
    if (fd.password !== fd.confirm) return WH.toast('Passwords do not match', 'error');
    if ((fd.password || '').length < 6) return WH.toast('Min 6 characters', 'error');
    try {
      await WH.api('/agents/login', {
        method: 'POST',
        body: JSON.stringify({ email: agent.email, password: fd.current })
      });
    } catch (_) {
      return WH.toast('Current password is incorrect', 'error');
    }
    const id = agent.public_id || agent.agent_code;
    if (!id) return WH.toast('Missing agent id', 'error');
    try {
      await WH.api('/agents/' + encodeURIComponent(id), {
        method: 'PATCH',
        body: JSON.stringify({ password: fd.password })
      });
      WH.toast('Password changed');
      e.target.reset();
    } catch (ex) {
      WH.toast(ex.data?.error || ex.message || 'Password update failed', 'error');
    }
  };

  document.getElementById('dailyTxForm').onsubmit = async (e) => {
    e.preventDefault();
    const date = new FormData(e.target).get('date');
    if (!date) return WH.toast('Pick a date', 'error');
    await loadDailyTx(String(date));
  };

  document.getElementById('signupReportForm').onsubmit = async (e) => {
    e.preventDefault();
    const fd = Object.fromEntries(new FormData(e.target));
    if (!fd.fromDate || !fd.toDate) return WH.toast('Pick from and to dates', 'error');
    await loadSignupReport(String(fd.fromDate), String(fd.toDate));
  };

  document.getElementById('logoutBtn').onclick = () => {
    clearSession();
    setGate(false);
    showAuthTab('login');
    document.getElementById('agentLogin')?.reset();
    WH.toast('Logged out');
  };

  document.getElementById('supportFab').onclick = async () => {
    const out = await WH.promptFields('Support', 'Send a message to HQ support.', [
      { id: 'msg', label: 'Your message', type: 'textarea', placeholder: 'How can we help?' }
    ]);
    if (!out || !out.msg?.trim()) return;
    try {
      await WH.api('/support', {
        method: 'POST',
        body: JSON.stringify({
          message: out.msg.trim(),
          user_email: agent?.email,
          sender_type: 'affiliate'
        })
      });
      WH.toast('Support message sent');
    } catch (e) {
      WH.toast(e.message || 'Support requires auth — message not sent', 'error');
    }
  };

  // Restore session
  try {
    const saved = localStorage.getItem(SESSION_KEY);
    if (saved) enterDesk(JSON.parse(saved));
  } catch (_) {}
})();
</script>
@endpush

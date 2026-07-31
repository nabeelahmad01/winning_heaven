@extends('layouts.app')
@section('title', 'Lobby — Winning Heaven')
@section('content')
@php
  $f = $frontend;
  $bg = $f['login_bg_url'] ?? '/brand/bg.png';
  $logo = $f['logo_url'] ?? '/brand/logo.png';
  $side = $f['lobby_hero_side_image'] ?? '/brand/promo.png';
  $marquees = $f['marquee_payouts'] ?? [];
  $doubled = array_merge($marquees, $marquees);
  $rules = $f['cashout_rules'] ?? [];
  $trust = $f['lobby_cashout_trust_items'] ?? [];
@endphp
<div class="wh-lobby">
  <nav class="wh-nav">
    <div class="wh-brand-mark">
      <img src="{{ asset(ltrim($logo,'/')) }}" alt="Winning Heaven">
      <div>
        <strong>Winning Heaven</strong>
        <span>{{ $user->name }} · {{ $user->referral_code }}</span>
      </div>
    </div>
    <div class="wh-nav__actions">
      @if(!empty($f['get_app_enabled']))
        <button type="button" class="wh-nav__btn wh-nav__btn--mint" id="getAppBtn"><i class="fa-solid fa-mobile-screen-button"></i> Get App</button>
      @endif
      <a class="wh-nav__btn" href="{{ route('referrals') }}"><i class="fa-solid fa-gift"></i> Refer</a>
      @if(!isset($f['info_show_on_lobby']) || !empty($f['info_show_on_lobby']))
      @if(!isset($f['info_page_enabled']) || !empty($f['info_page_enabled']))
      <a class="wh-nav__btn" href="{{ route('info') }}"><i class="fa-solid fa-circle-info"></i> Info</a>
      @endif
      @endif
      <button type="button" class="wh-nav__btn" id="logoutBtn"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
    </div>
  </nav>

  <main class="wh-main" id="lobbyMain">
    {{-- 1. HERO: promo left + freeplay right (Jackpot flow) --}}
    <section class="wh-hero">
      <div class="wh-hero__promo" style="--wh-bg:url('{{ asset(ltrim($bg,'/')) }}')">
        <div class="wh-hero__kicker"><i class="fa-solid fa-sparkles"></i> {{ $f['landing_welcome'] ?? 'WELCOME TO WINNING HEAVEN' }}</div>
        <h1>{{ $f['lobby_hero_promo'] ?? 'GET 300% SIGNUP BONUS ON YOUR FIRST DEPOSIT' }}</h1>
        <p>{{ $f['landing_grab'] ?? 'Grab amazing bonuses and win big!' }}</p>
        <div class="wh-pills">
          <span class="wh-pill"><i class="fa-solid fa-shield-halved"></i> {{ $f['lobby_trust_badge_1'] ?? 'Instant Withdrawals' }}</span>
          <span class="wh-pill"><i class="fa-solid fa-lock"></i> {{ $f['lobby_trust_badge_2'] ?? 'Secure & Safe' }}</span>
          <span class="wh-pill"><i class="fa-solid fa-trophy"></i> {{ $f['lobby_trust_badge_3'] ?? 'Trusted by 1B+ Players' }}</span>
        </div>
      </div>

      <aside class="wh-freeplay">
        <div class="wh-freeplay__art">
          <img src="{{ asset(ltrim($side,'/')) }}" alt="{{ $f['lobby_hero_side_image_alt'] ?? 'Freeplay promo' }}">
        </div>
        <div class="wh-freeplay__foot">
          <div style="display:flex;gap:.65rem;align-items:center">
            <div class="wh-freeplay__amount">{{ $f['lobby_freeplay_value'] ?? '$3' }}</div>
            <div class="wh-freeplay__copy">
              <strong>{{ $f['lobby_freeplay_label'] ?? 'FREEPLAY' }}</strong>
              <span id="fpCond">@if(($freeplayGate['phase'] ?? '') === 'pending')REQUEST PENDING@elseif(($freeplayGate['phase'] ?? '') === 'need_deposit'){{ '$'.number_format($freeplayGate['deposit_total'] ?? 0, 0) }} / $25 TO ELIGIBLE@elseif(!empty($freeplayGate['can_claim']) && empty($freeplayGate['is_first']))READY TO CLAIM@else{{ $f['lobby_freeplay_condition'] ?? 'ON SIGNUP!' }}@endif</span>
            </div>
          </div>
          <button type="button" class="wh-freeplay__claim" id="claimFreeplayTop" title="@if(($freeplayGate['phase'] ?? '')==='pending')Pending…@elseif(empty($freeplayGate['can_claim']))Deposit to unlock@else{{ $f['lobby_freeplay_claim_btn'] ?? 'Claim freeplay' }}@endif" @if(empty($freeplayGate['can_claim'])) disabled @endif><i class="fa-solid fa-gift"></i></button>
        </div>
      </aside>
    </section>

    {{-- 2. SELECT GAME (Jackpot deposit-now-btn style) --}}
    <button type="button" class="wh-select-game" id="selectGameBelowBtn" onclick="document.getElementById('gamesAnchor').scrollIntoView({behavior:'smooth'})">
      <span class="wh-select-game__halo" aria-hidden="true"></span>
      <strong><i class="fa-solid fa-circle-chevron-down"></i> SELECT GAME BELOW</strong>
      <span>Choose a casino portal to request credentials and deposit</span>
    </button>

    {{-- 3. Feature cards --}}
    <section class="wh-features">
      <div class="wh-feature"><i class="fa-solid fa-money-bill-wave"></i><div><h4>$3 Freeplay</h4><p>On Signup</p></div></div>
      <div class="wh-feature"><i class="fa-solid fa-bolt-lightning"></i><div><h4>Instant Payouts</h4><p>Withdraw Anytime</p></div></div>
      <div class="wh-feature"><i class="fa-solid fa-briefcase"></i><div><h4>Low Minimum</h4><p>Start From Just $5</p></div></div>
      <div class="wh-feature"><i class="fa-solid fa-headset"></i><div><h4>24/7 Support</h4><p>We're Here For You</p></div></div>
    </section>

    {{-- 4. Recent withdrawals --}}
    <div class="wh-rail-head"><h2><i class="fa-solid fa-fire" style="color:var(--danger)"></i> Recent Withdrawals</h2><span>Live cashouts</span></div>
    <div class="wh-payrow">
      @foreach($doubled as $p)
        <div class="wh-pay">
          <div class="wh-av">{{ $p['init'] ?? 'WH' }}</div>
          <div>
            <b>{{ $p['name'] ?? '' }} · {{ $p['amount'] ?? '' }}</b>
            <small>{{ $p['time'] ?? '' }} · PAID</small>
          </div>
        </div>
      @endforeach
    </div>

    {{-- 5. Referral --}}
    <div class="wh-invite">
      <div>
        <h3>SHARE WINNING HEAVEN WITH FRIENDS</h3>
        <p>Invite friends with code <strong>{{ $user->referral_code }}</strong> — same referral rules as original.</p>
      </div>
      <a class="wh-cta" href="{{ route('referrals') }}">SHARE NOW →</a>
    </div>

    {{-- 6. Games --}}
    <div class="wh-rail-head" id="gamesAnchor">
      <h2><i class="fa-solid fa-gamepad" style="color:var(--sand)"></i> Our Games</h2>
      <span>Tap a game to play · Freeplay on the card picks that game</span>
    </div>
    <div class="wh-games">
      @forelse($games as $i => $game)
        @php
          $playCycle = ['play-mint','play-sand','play-coral','play-aqua','play-violet'][$i % 5];
          $badge = strtolower((string) ($game->badge ?? 'none'));
        @endphp
        <div class="wh-game" data-title="{{ $game->title }}" data-image="{{ $game->image }}" data-link="{{ $game->link }}">
          @if($badge !== 'none' && $badge !== '')
            <span class="wh-game-badge {{ $badge === 'hot' ? 'is-hot' : 'is-new' }}">{{ strtoupper($badge) }}</span>
          @endif
          @if(!empty($freeplayGate['can_claim']))
            <button type="button" class="wh-game-fp" data-fp-game="{{ $game->title }}" title="Claim freeplay for this game">
              <i class="fa-solid fa-gift"></i> FREEPLAY
            </button>
          @endif
          <button type="button" class="wh-game__hit" data-open-game>
            <div class="wh-game__img">
              @if($game->image)
                <img src="{{ $game->image }}" alt="{{ $game->title }}">
              @else
                <span style="font-family:var(--font-display);font-size:1.5rem">{{ strtoupper(substr($game->title,0,2)) }}</span>
              @endif
            </div>
            <div class="wh-game__meta">
              <strong>{{ $game->title }}</strong>
              <span class="wh-game__play {{ $playCycle }}">PLAY NOW ▶</span>
            </div>
          </button>
        </div>
      @empty
        <p style="color:var(--mute)">No games yet — add from HQ.</p>
      @endforelse
    </div>

    {{-- 7. Rules --}}
    <section class="wh-rules">
      <details>
        <summary><span><i class="fa-solid fa-scroll" style="color:var(--sand)"></i> CASHOUT RULES & PLAY INFO</span><i class="fa-solid fa-chevron-down"></i></summary>
        @foreach($rules as $rule)
          <article><h4>{{ $rule['title'] ?? '' }}</h4><p>{{ $rule['description'] ?? '' }}</p></article>
        @endforeach
      </details>
    </section>

    {{-- 8. Trust --}}
    <section class="wh-trust">
      @foreach($trust as $item)
        <div>
          <i class="fa-solid {{ $item['icon'] ?? 'fa-shield-halved' }}"></i>
          <strong>{{ $item['title'] ?? '' }}</strong>
          <span>{{ $item['description'] ?? '' }}</span>
        </div>
      @endforeach
    </section>
  </main>

  {{-- GAME PORTAL --}}
  <div class="wh-portal" id="gamePortal">
    <div class="wh-portal__card">
      <button type="button" class="wh-nav__btn" id="closePortalBtn"><i class="fa-solid fa-arrow-left"></i> Back to Lobby</button>
      <div style="display:flex;gap:.85rem;align-items:center;margin:1rem 0;flex-wrap:wrap;justify-content:space-between">
        <div style="display:flex;gap:.85rem;align-items:center">
          <img id="portalImg" alt="" style="width:56px;height:56px;border-radius:14px;object-fit:cover;background:#102030;display:none">
          <div>
            <h2 id="portalGameTitle" style="font-family:var(--font-display);margin:0">Game</h2>
            <p style="color:var(--mute);margin:.2rem 0 0;font-size:.85rem">Request ID → Deposit → Withdraw</p>
          </div>
        </div>
        <button type="button" class="wh-portal-fp" id="portalFreeplayBtn" @if(empty($freeplayGate['can_claim'])) disabled style="opacity:.5;cursor:not-allowed" @endif>
          <i class="fa-solid fa-gift"></i> FREEPLAY
        </button>
      </div>

      <div id="stateNeedAccount" class="wh-account-card" style="margin-bottom:1rem">
        <div class="wh-account-card__head">
          <h3 style="margin:0">Need a game account?</h3>
        </div>
        <p style="color:var(--mute);margin:.35rem 0 1rem">Staff will issue username &amp; password after you request.</p>
        <button type="button" class="wh-cta wh-cta--wide" id="reqAccountBtn">REQUEST GAME ACCOUNT</button>
      </div>
      <div id="statePending" class="wh-account-card is-pending" style="margin-bottom:1rem;display:none">
        <div class="wh-account-card__head">
          <h3 style="margin:0">Account pending</h3>
          <span class="wh-ready-badge" style="background:rgba(250,204,21,.15);color:#facc15;border-color:rgba(250,204,21,.4)">WAITING</span>
        </div>
        <div class="wh-pending-hour" aria-hidden="true"></div>
        <p style="color:var(--mute);text-align:center;margin:0">Your request is with HQ. Credentials appear here once approved.</p>
      </div>
      <div id="stateReady" class="wh-account-card is-ready" style="margin-bottom:1rem;display:none">
        <div class="wh-account-card__head">
          <h3 style="margin:0">Game account ready</h3>
          <span class="wh-ready-badge"><i class="fa-solid fa-circle-check"></i> READY</span>
        </div>
        <div class="credentials-row-grid" id="credGrid">
          <div class="cred-block-item">
            <span class="cred-label">Email</span>
            <code id="credEmail">—</code>
            <button type="button" class="wh-btn-sm ghost cred-copy" data-copy="credEmail">Copy</button>
          </div>
          <div class="cred-block-item">
            <span class="cred-label">Username</span>
            <code id="credUser">—</code>
            <button type="button" class="wh-btn-sm ghost cred-copy" data-copy="credUser">Copy</button>
          </div>
          <div class="cred-block-item">
            <span class="cred-label">Password</span>
            <code id="credPass">—</code>
            <button type="button" class="wh-btn-sm ghost cred-copy" data-copy="credPass">Copy</button>
          </div>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.75rem">
          <a id="playLink" href="#" target="_blank" rel="noopener" class="wh-cta" style="display:inline-flex">OPEN GAME</a>
          <button type="button" class="wh-nav__btn" id="copyBothBtn"><i class="fa-solid fa-copy"></i> Copy all</button>
        </div>

        <div class="wh-wallet-notice" style="margin-top:1.15rem">
          <i class="fa-solid fa-triangle-exclamation"></i>
          Deposit only if your in-game balance is under 1. Contact support if you hit issues.
        </div>
        <div class="wallet-actions-split" id="walletSplit" style="margin-top:1rem">
          <div class="wallet-side-box is-dep">
            <h4>DEPOSIT</h4>
            <p>Add funds to this game account</p>
            <div class="wh-field" style="margin:.75rem 0">
              <label>Amount (USD)</label>
              <div class="box"><i class="fa-solid fa-dollar-sign"></i><input type="number" id="walletDepAmount" step="0.01" min="{{ $f['minimum_deposit_limit'] ?? 5 }}" placeholder="Min ${{ $f['minimum_deposit_limit'] ?? 5 }}"></div>
            </div>
            <button type="button" class="wh-cta wh-cta--wide" id="walletDepBtn">DEPOSIT</button>
          </div>
          <div class="wallet-side-box is-wd">
            <h4>WITHDRAW @if(!empty($isFreeplaySession))<span class="wh-badge" style="background:rgba(255,77,109,.18);color:#ff6b7a;margin-left:.35rem">FREEPLAY</span>@endif</h4>
            <p>Cash out from this game @if(!empty($isFreeplaySession))(min ${{ $freeplayMinRequest ?? 100 }}, cap ${{ $freeplayCashoutCap ?? 30 }})@endif</p>
            <div class="wh-field" style="margin:.75rem 0">
              <label>Amount (USD)</label>
              <div class="box"><i class="fa-solid fa-dollar-sign"></i><input type="number" id="walletWdAmount" step="0.01" min="{{ $f['minimum_withdrawal_limit'] ?? 25 }}" placeholder="Min ${{ $f['minimum_withdrawal_limit'] ?? 25 }}"></div>
            </div>
            <button type="button" class="wh-cta wh-cta--wide" id="walletWdBtn" style="background:linear-gradient(135deg,#ff6b7a,#9f1239);color:#fff">WITHDRAW</button>
          </div>
        </div>
      </div>

      {{-- Inline unpaid invoice (replaces portal card content when active) --}}
      <div id="stateInvoice" style="display:none;margin-bottom:1rem"></div>

      <div class="wh-actions" style="display:none">
        <button type="button" class="dep" id="depositBtn">DEPOSIT</button>
        <button type="button" class="wd" id="withdrawBtn">WITHDRAW</button>
      </div>
      <p id="portalMsg" style="color:var(--sand);margin-top:1rem;min-height:1.2em"></p>
      <div class="wh-rail-head" style="margin-top:1rem"><h2 style="font-size:1.05rem">Your requests</h2></div>
      <div id="txList" style="font-size:.85rem;color:var(--mute)"></div>
      <div class="wh-rail-head" style="margin-top:1.25rem"><h2 style="font-size:1.05rem">My cashouts</h2></div>
      <div id="cashoutList" style="font-size:.85rem;color:var(--mute)"></div>
    </div>
  </div>

  {{-- Promotions modal --}}
  <div class="wh-modal" id="promoModal">
    <div class="wh-modal__card">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem">
        <h3 id="promoTitle" style="font-family:var(--font-display);margin:0">Promotion</h3>
        <button type="button" class="wh-nav__btn" id="promoDismissBtn">Later</button>
      </div>
      <div id="promoImgWrap" style="display:none;margin-top:.85rem">
        <img id="promoImg" alt="" style="width:100%;max-height:180px;object-fit:cover;border-radius:12px;border:1px solid var(--line)">
      </div>
      <p id="promoMessage" style="color:var(--mute);margin:1rem 0;line-height:1.45"></p>
      <button type="button" class="wh-cta wh-cta--wide" id="promoClaimBtn"><i class="fa-solid fa-gift"></i> CLAIM OFFER</button>
    </div>
  </div>

  {{-- Gateway picker + withdraw form overlay (Jackpot PaymentMethodModal parity) --}}
  <div class="wh-modal" id="payModal">
    <div class="wh-modal__card" style="max-width:520px">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem">
        <h3 id="payModalTitle" style="font-family:var(--font-display);margin:0">Payment method</h3>
        <button type="button" class="wh-nav__btn" id="closePayBtn">Close</button>
      </div>
      <p id="payModalSub" style="color:var(--mute);font-size:.82rem;margin:.35rem 0 0"></p>
      <div id="payAmountBanner" style="margin-top:1rem;padding:.85rem 1rem;border-radius:14px;background:rgba(62,224,178,.08);border:1px solid rgba(62,224,178,.28);text-align:center">
        <div style="color:var(--mute);font-size:.72rem;text-transform:uppercase">Amount</div>
        <strong id="payAmountDisplay" style="font-family:var(--font-display);font-size:1.85rem;color:var(--sand)">$0.00</strong>
      </div>

      <div class="wh-pay-step is-on" id="payStep2">
        <p style="color:var(--mute);font-size:.85rem;margin:1rem 0 .65rem">Choose a payment method</p>
        <div class="wh-gw-grid" id="payGwGrid"></div>
      </div>

      <div class="wh-pay-step" id="payStep3Withdraw">
        <div style="margin-top:1rem">
          <p style="color:var(--mute);font-size:.85rem;margin:0 0 .75rem">Payout to <strong id="wdGwName">—</strong></p>
          <div class="wh-field"><label>Name on Tag</label><div class="box"><i class="fa-solid fa-user"></i><input id="wdNameOnTag" placeholder="Full name on Cash App / Venmo"></div></div>
          <div class="wh-field"><label>Tag / Address</label><div class="box"><i class="fa-solid fa-hashtag"></i><input id="wdTagCode" placeholder="$yourtag or wallet address"></div></div>
          <div class="wh-field"><label>Phone Number on Tag</label><div class="box"><i class="fa-solid fa-phone"></i><input id="wdPhone" placeholder="Phone on tag" required></div></div>
          <div class="wh-field" id="wdEmailWrap" style="display:none"><label>Email on Tag</label><div class="box"><i class="fa-solid fa-envelope"></i><input id="wdEmailOnTag" type="email" placeholder="email@example.com"></div></div>
          <label class="wh-upload" style="margin-bottom:.75rem;{{ empty($f['withdraw_require_game_screenshot']) ? 'display:none' : '' }}" id="wdGameShotLabel">
            <input type="file" id="wdGameShot" accept="image/*">
            <i class="fa-solid fa-image"></i>
            <div>Upload game balance screenshot</div>
            <img id="wdGamePreview" alt="" style="display:none">
          </label>
          <label class="wh-upload" id="wdTagQrLabel" style="{{ isset($f['withdraw_require_tag_qr_screenshot']) && empty($f['withdraw_require_tag_qr_screenshot']) ? 'display:none' : '' }}">
            <input type="file" id="wdTagQrShot" accept="image/*">
            <i class="fa-solid fa-qrcode"></i>
            <div>Upload tag QR screenshot</div>
            <img id="wdTagPreview" alt="" style="display:none">
          </label>
          <button type="button" class="wh-cta wh-cta--wide" id="wdSubmitBtn" style="margin-top:1rem">CONFIRM WITHDRAW</button>
          <button type="button" class="wh-nav__btn" id="wdBackToGw" style="margin-top:.55rem">← Back</button>
        </div>
      </div>
    </div>
  </div>

  <button type="button" class="wh-support-widget" id="supportFab" title="Live Support">
    <span class="wh-support-widget__inner">
      <i class="fa-solid fa-comment-dots"></i>
      <span>SUPPORT</span>
    </span>
    <span class="wh-fab-badge" id="supportFabBadge">0</span>
  </button>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/wh-lobby-pay.js') }}"></script>
<script>
const GAME_ACCOUNTS = {!! json_encode($jsGameAccounts ?? []) !!};
let ACCOUNT_REQS = {!! json_encode($jsAccountReqs ?? []) !!};
let TXS = {!! json_encode($jsTransactions ?? []) !!};
const GAMES = {!! json_encode($jsGames ?? []) !!};
const GATEWAYS = {!! json_encode($jsGateways ?? []) !!};
const FP_GATE = {!! json_encode($freeplayGate ?? ['can_claim'=>true,'is_first'=>true,'phase'=>'signup']) !!};
const IS_FP_SESSION = {!! json_encode($isFreeplaySession ?? false) !!};
const FP_MIN = {!! json_encode($freeplayMinRequest ?? 100) !!};
const FP_CAP = {!! json_encode($freeplayCashoutCap ?? 30) !!};
const MIN_DEP = {{ (float) ($f['minimum_deposit_limit'] ?? 5) }};
const MIN_WD = {{ (float) ($f['minimum_withdrawal_limit'] ?? 25) }};
const WD_REQUIRE_GAME = {!! json_encode(!empty($f['withdraw_require_game_screenshot'])) !!};
const WD_REQUIRE_TAG_QR = {!! json_encode(!isset($f['withdraw_require_tag_qr_screenshot']) || !empty($f['withdraw_require_tag_qr_screenshot'])) !!};
const USER_EMAIL = {!! json_encode($user->email ?? '') !!};
window.__WH_USER_EMAIL = USER_EMAIL;

let activeGame = null;
let payType = 'DEPOSIT';
let payAmount = 0;
let selectedGw = null;
let depShot = '';
let wdGameShot = '';
let wdTagShot = '';
let invoiceTimer = null;
let invoiceExpiresAt = 0;
let invoiceCode = '';

const PD = window.WHPendingDeposit;

function showPortal(on) {
  const el = document.getElementById('gamePortal');
  el.classList.toggle('is-on', on);
  el.style.display = on ? 'block' : 'none';
  document.getElementById('lobbyMain').style.display = on ? 'none' : 'block';
  document.querySelector('.wh-nav').style.display = on ? 'none' : 'flex';
}
function showPay(on) {
  const el = document.getElementById('payModal');
  el.classList.toggle('is-on', on);
  el.style.display = on ? 'block' : 'none';
}
function setPayStep(id) {
  ['payStep2','payStep3Withdraw'].forEach(s => {
    const el = document.getElementById(s);
    if (el) el.classList.toggle('is-on', s === id);
  });
}
function gameUsername() {
  const acc = GAME_ACCOUNTS.find(a => a.game_title === activeGame?.title);
  return acc?.username || '';
}
function readFileAsDataUrl(file) {
  return new Promise((resolve, reject) => {
    if (!file) return reject(new Error('No file'));
    if (file.size > 2.5 * 1024 * 1024) return reject(new Error('Image must be under 2.5MB'));
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = () => reject(new Error('Could not read file'));
    reader.readAsDataURL(file);
  });
}
function clearInvoiceState(keepPending) {
  if (invoiceTimer) { clearInterval(invoiceTimer); invoiceTimer = null; }
  invoiceExpiresAt = 0;
  invoiceCode = '';
  depShot = '';
  if (!keepPending) PD.clear();
  const inv = document.getElementById('stateInvoice');
  if (inv) { inv.style.display = 'none'; inv.innerHTML = ''; }
  const ready = document.getElementById('stateReady');
  if (ready && gameUsername()) ready.style.display = 'block';
}
function copyText(value, label) {
  if (!value || value === '—') return WH.toast('Nothing to copy', 'error');
  navigator.clipboard?.writeText(String(value)).then(() => WH.toast(label + ' copied')).catch(() => WH.toast(String(value)));
}

async function softRefreshTxs() {
  try {
    const d = await WH.api('/transactions?email=' + encodeURIComponent(USER_EMAIL));
    TXS = (d.items || []).map(t => ({
      type: t.type, amount: t.amount, status: t.status, code: t.code, gateway: t.gateway,
      game_title: t.game_title, is_freeplay_withdraw: !!t.is_freeplay_withdraw,
      payout_hold: Number(t.payout_hold || 0),
      remainder_claim_available_at: t.remainder_claim_available_at || '',
      remainder_requested: !!t.remainder_requested, remainder_status: t.remainder_status,
      public_id: t.public_id, created_at: t.created_at
    }));
    if (activeGame) renderPortalTxs(activeGame.title);
  } catch (_) {}
}

function remainderCountdown(atMs) {
  const left = Math.max(0, atMs - Date.now());
  if (left <= 0) return null;
  const h = Math.floor(left / 3600000);
  const m = Math.floor((left % 3600000) / 60000);
  const s = Math.floor((left % 60000) / 1000);
  return String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
}
function remainderBtnHtml(t) {
  if (!t.payout_hold || Number(t.payout_hold) <= 0 || t.remainder_requested) return '';
  const at = t.remainder_claim_available_at ? new Date(t.remainder_claim_available_at).getTime() : 0;
  // Jackpot: if no unlock time set, claim is available immediately
  if (!at || Date.now() >= at) {
    return `<button type="button" class="wh-btn-sm" data-remainder="${PD.esc(t.public_id)}">Claim remainder $${Number(t.payout_hold).toFixed(2)}</button>`;
  }
  const cd = remainderCountdown(at);
  return `<span class="wh-remainder-timer" data-unlock="${at}" style="color:var(--mute);font-size:.75rem">Remainder unlocks in <strong style="color:var(--sand)">${cd}</strong></span>`;
}
function tickRemainderTimers() {
  document.querySelectorAll('.wh-remainder-timer[data-unlock]').forEach((el) => {
    const at = Number(el.getAttribute('data-unlock'));
    const cd = remainderCountdown(at);
    if (!cd) {
      // Re-render lists so claim button appears
      if (activeGame) renderPortalTxs(activeGame.title);
      return;
    }
    const strong = el.querySelector('strong');
    if (strong) strong.textContent = cd;
  });
}
setInterval(tickRemainderTimers, 1000);
function txCardHtml(t) {
  const meta = [t.gateway, t.code, t.status, t.created_at ? new Date(t.created_at).toLocaleString() : ''].filter(Boolean).join(' · ');
  return `<div class="wh-tx-card">
    <div class="wh-tx-card__top">
      <div>
        <div class="wh-tx-card__amt">$${Number(t.amount).toFixed(2)}</div>
        <div style="font-size:.78rem;font-weight:700;margin-top:.15rem">${PD.esc(t.type || '')}</div>
      </div>
      <span class="wh-badge">${PD.esc(t.status || '')}</span>
    </div>
    <div class="wh-tx-card__meta">${PD.esc(meta)}</div>
    <div class="wh-tx-card__actions">${remainderBtnHtml(t)}</div>
  </div>`;
}
function renderPortalTxs(title) {
  const list = TXS.filter(t => t.game_title === title).slice(0, 12);
  document.getElementById('txList').innerHTML = list.length ? list.map(txCardHtml).join('') : '<p style="color:var(--mute)">No requests yet.</p>';
  document.getElementById('txList').querySelectorAll('[data-remainder]').forEach(btn => {
    btn.onclick = () => { const tx = TXS.find(t => t.public_id === btn.dataset.remainder); if (tx) claimRemainder(tx); };
  });
  renderCashouts(title);
}
function renderCashouts(gameTitle) {
  const el = document.getElementById('cashoutList');
  if (!el) return;
  let list = TXS.filter(t => t.type === 'WITHDRAW');
  if (gameTitle) list = list.filter(t => t.game_title === gameTitle);
  list = list.slice(0, 10);
  el.innerHTML = list.length ? list.map(txCardHtml).join('') : '<p style="color:var(--mute)">No cashouts yet.</p>';
  el.querySelectorAll('[data-remainder]').forEach(btn => {
    btn.onclick = () => { const tx = TXS.find(t => t.public_id === btn.dataset.remainder); if (tx) claimRemainder(tx); };
  });
}
async function claimRemainder(tx) {
  try {
    await WH.api('/transactions', { method:'POST', body: JSON.stringify({
      type: 'WITHDRAW', amount: tx.payout_hold, is_remainder_request: true, parent_tx_id: tx.public_id,
      game_title: tx.game_title, gateway: tx.gateway, code: tx.code
    })});
    WH.toast('Remainder claim submitted');
    await softRefreshTxs();
  } catch (e) { WH.alert(e.data?.error || e.message, 'Remainder'); }
}

function gwThemeStyle(theme) {
  const t = String(theme || '').toLowerCase();
  const map = {
    chime: { bg: '#2ecc71', color: '#041018' },
    cashapp: { bg: '#00d632', color: '#041018' },
    stripe: { bg: '#635bff', color: '#fff' },
    crypto: { bg: 'linear-gradient(135deg,#a855f7,#ec4899)', color: '#fff' },
    zelle: { bg: '#7413dc', color: '#fff' },
    paypal: { bg: '#0079c1', color: '#fff' },
    venmo: { bg: '#008cff', color: '#fff' }
  };
  return map[t] || { bg: 'linear-gradient(135deg,var(--aqua),var(--mint))', color: '#041018' };
}
function renderGwGrid() {
  const grid = document.getElementById('payGwGrid');
  const list = payType === 'WITHDRAW' ? GATEWAYS.filter(g => g.is_withdraw_active !== false) : GATEWAYS;
  if (!list.length) {
    grid.innerHTML = '<p style="color:var(--mute);grid-column:1/-1">No gateways configured. Contact support.</p>';
    return;
  }
  grid.innerHTML = list.map((g, i) => {
    const st = gwThemeStyle(g.theme);
    return `
    <button type="button" class="wh-gw-card" data-idx="${i}" style="border-color:rgba(255,255,255,.12)">
      <strong>${PD.esc(g.name || 'Gateway')}</strong>
      <small style="color:var(--mute)">${PD.esc(g.subtitle || g.theme || '')}</small>
      <span class="wh-gw-continue" style="background:${st.bg};color:${st.color}">CONTINUE WITH ${PD.esc((g.name || '').toUpperCase())}</span>
    </button>`;
  }).join('');
  grid.querySelectorAll('.wh-gw-card').forEach(btn => {
    btn.onclick = () => {
      selectedGw = list[Number(btn.dataset.idx)];
      if (payType === 'DEPOSIT') openDepositInvoice(selectedGw);
      else openWithdrawForm(selectedGw);
    };
  });
}

function startPayFlow(type, amount) {
  payType = type;
  payAmount = Number(amount);
  selectedGw = null;
  document.getElementById('payModalTitle').textContent = type === 'DEPOSIT' ? 'Choose deposit method' : 'Choose withdraw method';
  document.getElementById('payModalSub').textContent = activeGame.title;
  document.getElementById('payAmountDisplay').textContent = '$' + payAmount.toFixed(2);
  setPayStep('payStep2');
  renderGwGrid();
  showPay(true);
}

function openWithdrawForm(gw) {
  selectedGw = gw;
  document.getElementById('wdGwName').textContent = gw.name || '—';
  const emailWrap = document.getElementById('wdEmailWrap');
  if (emailWrap) emailWrap.style.display = gw.require_email_on_tag ? 'block' : 'none';
  setPayStep('payStep3Withdraw');
}

function openDepositInvoice(gw, restore) {
  selectedGw = gw;
  showPay(false);
  const pending = restore || null;
  if (pending) {
    invoiceCode = pending.noteCode;
    invoiceExpiresAt = Number(pending.expiresAt);
    payAmount = Number(pending.amount);
  } else {
    invoiceCode = PD.generateCode();
    invoiceExpiresAt = Date.now() + PD.TTL;
    PD.write({
      userEmail: USER_EMAIL,
      gameTitle: activeGame.title,
      amount: payAmount,
      gateway: gw,
      noteCode: invoiceCode,
      expiresAt: invoiceExpiresAt
    });
  }
  document.getElementById('stateReady').style.display = 'none';
  const linkPay = PD.isLinkPayGateway(gw);
  const payUrl = PD.buildRedirectUrl(gw, payAmount, invoiceCode);
  const inv = document.getElementById('stateInvoice');
  inv.style.display = 'block';

  if (linkPay) {
    inv.innerHTML = `
      <div class="wh-invoice-card">
        <h3 style="text-align:center;font-family:var(--font-display);margin:0 0 1rem">Pay with ${PD.esc(gw.name || 'Link')}</h3>
        <div class="wh-invoice-meta">
          <div><span>Game</span><strong>${PD.esc(activeGame.title)}</strong></div>
          <div><span>Username</span><strong>${PD.esc(gameUsername())}</strong></div>
          <div><span>Amount USD</span><strong>$${Number(payAmount).toFixed(2)}</strong></div>
          <div><span>Status</span><strong class="unpaid">UNPAID</strong></div>
          <div><span>Time left</span><strong id="invTimerLive">${PD.fmtTime(PD.remainingSeconds(invoiceExpiresAt))}</strong></div>
        </div>
        <div class="wh-memo-box">
          <div class="cred-label">Unique Code / Memo</div>
          <code id="invCodeLive">${PD.esc(invoiceCode)}</code>
          <button type="button" class="wh-btn-sm ghost" id="invCopyCodeBtn">Copy</button>
        </div>
        ${payUrl ? `<a href="${PD.esc(payUrl)}" target="_blank" rel="noopener" class="wh-cta wh-cta--wide" style="text-align:center;text-decoration:none;margin-top:.85rem">Pay with ${PD.esc(gw.name || 'Link')}</a>` : ''}
        <button type="button" class="wh-nav__btn" id="invCopySummaryBtn" style="width:100%;justify-content:center;margin-top:.55rem">Copy invoice</button>
        <label class="wh-upload" style="margin-top:1rem">
          <input type="file" id="depScreenshotLive" accept="image/*">
          <i class="fa-solid fa-cloud-arrow-up"></i>
          <div>Upload payment screenshot</div>
          <img id="depShotPreviewLive" alt="" style="display:none">
        </label>
        <div class="invoice-actions-row">
          <button type="button" class="wh-nav__btn" id="depCancelLive">Cancel</button>
          <button type="button" class="wh-cta" id="depSubmitLive">I HAVE PAID</button>
        </div>
      </div>`;
  } else {
    inv.innerHTML = `
      <div class="wh-invoice-card">
        <div class="invoice-grid-split">
          <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.85rem">
              <span class="unpaid-badge">UNPAID</span>
              <strong id="invTimerLive" style="font-family:var(--font-display);font-size:1.25rem">${PD.fmtTime(PD.remainingSeconds(invoiceExpiresAt))}</strong>
            </div>
            <div style="font-family:var(--font-display);font-size:1.75rem;color:var(--sand);margin-bottom:.85rem">$${Number(payAmount).toFixed(2)}</div>
            <p style="color:#ff6b7a;font-size:.78rem;margin:0 0 .85rem">Include memo code in your payment note or the deposit may be delayed.</p>
            <div class="tag-details-box">
              <div class="tag-field-row"><span>Payment tag</span><strong>${PD.esc(gw.tag || '—')}</strong>
                <button type="button" class="wh-btn-sm ghost" data-copy-val="${PD.esc(gw.tag || '')}">Copy</button></div>
              <div class="tag-field-row"><span>Phone / network</span><strong>${PD.esc(gw.phone || '—')}</strong>
                <button type="button" class="wh-btn-sm ghost" data-copy-val="${PD.esc(gw.phone || '')}">Copy</button></div>
              <div class="tag-field-row"><span>Memo code</span><code id="invCodeLive">${PD.esc(invoiceCode)}</code>
                <button type="button" class="wh-btn-sm ghost" id="invCopyCodeBtn">Copy</button></div>
              <div class="tag-field-row"><span>Gateway</span><strong>${PD.esc(gw.name || '')}</strong></div>
            </div>
            <label class="wh-upload" style="margin-top:1rem">
              <input type="file" id="depScreenshotLive" accept="image/*">
              <i class="fa-solid fa-cloud-arrow-up"></i>
              <div>Upload payment screenshot</div>
              <img id="depShotPreviewLive" alt="" style="display:none">
            </label>
            <div class="invoice-actions-row">
              <button type="button" class="wh-nav__btn" id="depCancelLive">Cancel</button>
              <button type="button" class="wh-cta" id="depSubmitLive">I HAVE PAID</button>
            </div>
          </div>
          <div class="qr-container">
            ${gw.qr_image ? `<img src="${PD.esc(gw.qr_image)}" alt="QR" style="width:100%;max-width:280px;border-radius:14px;border:1px solid var(--line)">` : '<div style="color:var(--mute);text-align:center;padding:2rem">No QR uploaded</div>'}
          </div>
        </div>
      </div>`;
  }

  if (invoiceTimer) clearInterval(invoiceTimer);
  invoiceTimer = setInterval(() => {
    const left = PD.remainingSeconds(invoiceExpiresAt);
    const el = document.getElementById('invTimerLive');
    if (el) el.textContent = PD.fmtTime(left);
    if (left <= 0) {
      clearInvoiceState(false);
      WH.toast('Deposit session expired', 'error');
    }
  }, 1000);

  document.getElementById('invCopyCodeBtn')?.addEventListener('click', () => copyText(invoiceCode, 'Memo code'));
  document.getElementById('invCopySummaryBtn')?.addEventListener('click', () => {
    copyText(`Pay $${Number(payAmount).toFixed(2)} via ${gw.name}\nMemo: ${invoiceCode}\nGame: ${activeGame.title}`, 'Invoice');
  });
  inv.querySelectorAll('[data-copy-val]').forEach(btn => {
    btn.addEventListener('click', () => copyText(btn.getAttribute('data-copy-val'), 'Copied'));
  });
  document.getElementById('depScreenshotLive')?.addEventListener('change', async (e) => {
    try {
      depShot = await readFileAsDataUrl(e.target.files?.[0]);
      const prev = document.getElementById('depShotPreviewLive');
      if (prev) { prev.src = depShot; prev.style.display = 'block'; }
    } catch (err) { WH.toast(err.message || 'Upload failed', 'error'); }
  });
  document.getElementById('depCancelLive')?.addEventListener('click', () => {
    clearInvoiceState(false);
    WH.toast('Invoice cancelled');
  });
  document.getElementById('depSubmitLive')?.addEventListener('click', submitDeposit);
}

async function submitDeposit() {
  if (!selectedGw || !invoiceCode) return WH.toast('No active invoice', 'error');
  if (!depShot) return WH.toast('Upload payment screenshot first', 'error');
  if (Date.now() > invoiceExpiresAt) return WH.toast('Invoice expired', 'error');
  WH.setBtnLoading('#depSubmitLive', true, 'SUBMITTING…');
  try {
    await WH.api('/transactions', { method:'POST', body: JSON.stringify({
      type: 'DEPOSIT', amount: payAmount, gateway: selectedGw.name, code: invoiceCode,
      screenshot: depShot, game_title: activeGame.title, game_username: gameUsername()
    })});
    clearInvoiceState(false);
    document.getElementById('portalMsg').textContent = 'DEPOSIT submitted — staff sees it now.';
    WH.toast('Deposit submitted');
    await softRefreshTxs();
  } catch (e) {
    WH.alert(e.data?.error || e.message, 'Deposit');
  } finally {
    WH.setBtnLoading('#depSubmitLive', false);
  }
}

function openGame(title, image, link) {
  activeGame = { title, image, link };
  document.getElementById('portalGameTitle').textContent = title;
  const img = document.getElementById('portalImg');
  if (image) { img.src = image; img.style.display = 'block'; } else img.style.display = 'none';
  const acc = GAME_ACCOUNTS.find(a => a.game_title === title);
  const req = ACCOUNT_REQS.find(r => r.game_title === title && r.status === 'PENDING');
  document.getElementById('stateNeedAccount').style.display = (!acc && !req) ? 'block' : 'none';
  document.getElementById('statePending').style.display = (!acc && req) ? 'block' : 'none';
  document.getElementById('stateReady').style.display = acc ? 'block' : 'none';
  clearInvoiceState(true);
  if (acc) {
    document.getElementById('credEmail').textContent = USER_EMAIL || '—';
    document.getElementById('credUser').textContent = acc.username;
    document.getElementById('credPass').textContent = acc.password;
    // Restore unpaid deposit for this game
    const pending = PD.read(USER_EMAIL);
    if (pending && String(pending.gameTitle || '').toLowerCase() === String(title).toLowerCase()) {
      payAmount = Number(pending.amount);
      openDepositInvoice(pending.gateway || {}, pending);
    }
  }
  const play = document.getElementById('playLink');
  if (link) { play.href = link; play.style.display = 'inline-flex'; } else play.style.display = 'none';
  document.getElementById('portalMsg').textContent = '';
  renderPortalTxs(title);
  showPortal(true);
}

document.querySelectorAll('.cred-copy').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.getAttribute('data-copy');
    copyText(document.getElementById(id)?.textContent, 'Copied');
  });
});
document.getElementById('copyBothBtn')?.addEventListener('click', () => {
  const e = document.getElementById('credEmail')?.textContent || '';
  const u = document.getElementById('credUser')?.textContent || '';
  const p = document.getElementById('credPass')?.textContent || '';
  copyText('Email: ' + e + '\nUsername: ' + u + '\nPassword: ' + p, 'Credentials');
});
document.querySelectorAll('[data-open-game]').forEach((btn) => {
  btn.addEventListener('click', () => {
    const card = btn.closest('.wh-game');
    if (!card) return;
    openGame(card.dataset.title, card.dataset.image, card.dataset.link);
  });
});
async function claimFreeplayForActive(btnTarget) {
  if (!FP_GATE.can_claim) return WH.alert(FP_GATE.message || 'Not eligible for freeplay right now.', 'Freeplay');
  if (!activeGame) {
    WH.alert('Select a game first (use FREEPLAY on a game card, or open a game then claim).', 'Freeplay');
    document.getElementById('gamesAnchor')?.scrollIntoView({ behavior: 'smooth' });
    return;
  }
  if (btnTarget) WH.setBtnLoading(btnTarget, true, 'CLAIMING…');
  try {
    const r = await WH.api('/freeplay/claim', { method:'POST', body: JSON.stringify({ game_title: activeGame.title }) });
    WH.toast('Freeplay $' + (r.amount || 3) + ' queued for ' + activeGame.title + ' — coins staff will load it.');
    FP_GATE.can_claim = false;
    document.querySelectorAll('.wh-game-fp').forEach((b) => b.remove());
    const top = document.getElementById('claimFreeplayTop');
    if (top) {
      top.disabled = true;
      top.style.opacity = '.55';
      top.title = 'Pending…';
      top.innerHTML = '<i class="fa-solid fa-hourglass-half"></i>';
    }
    const pf = document.getElementById('portalFreeplayBtn');
    if (pf) { pf.disabled = true; pf.style.opacity = '.5'; }
  } catch (e) {
    if (btnTarget) WH.setBtnLoading(btnTarget, false);
    WH.alert(e.data?.error || e.message, 'Freeplay');
  }
}
document.querySelectorAll('[data-fp-game]').forEach((btn) => {
  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    const card = btn.closest('.wh-game');
    if (!card) return;
    openGame(card.dataset.title, card.dataset.image, card.dataset.link);
    setTimeout(() => claimFreeplayForActive(btn), 120);
  });
});
document.getElementById('portalFreeplayBtn')?.addEventListener('click', function() { claimFreeplayForActive(this); });
document.getElementById('closePortalBtn').onclick = () => showPortal(false);
document.getElementById('closePayBtn').onclick = () => showPay(false);
document.getElementById('reqAccountBtn').onclick = async function() {
  WH.setBtnLoading(this, true, 'REQUESTING…');
  try {
    await WH.api('/account-requests', { method:'POST', body: JSON.stringify({ game_title: activeGame.title }) });
    document.getElementById('portalMsg').textContent = 'Account request sent.';
    document.getElementById('stateNeedAccount').style.display = 'none';
    document.getElementById('statePending').style.display = 'block';
    ACCOUNT_REQS.push({ game_title: activeGame.title, status: 'PENDING' });
    WH.toast('Account request submitted — wait for HQ approval');
  } catch (e) {
    WH.alert(e.data?.error || e.message, 'Account Request');
  } finally {
    WH.setBtnLoading(this, false);
  }
};

document.getElementById('walletDepBtn')?.addEventListener('click', () => {
  if (!gameUsername()) return WH.toast('Game account required first', 'error');
  const amount = Number(document.getElementById('walletDepAmount').value);
  if (!amount) return WH.toast('Enter deposit amount', 'error');
  if (amount < MIN_DEP) return WH.toast('Minimum deposit is $' + MIN_DEP, 'error');
  startPayFlow('DEPOSIT', amount);
});
document.getElementById('walletWdBtn')?.addEventListener('click', () => {
  if (!gameUsername()) return WH.toast('Game account required first', 'error');
  const amount = Number(document.getElementById('walletWdAmount').value);
  if (!amount) return WH.toast('Enter withdraw amount', 'error');
  if (amount < MIN_WD) return WH.toast('Minimum withdrawal is $' + MIN_WD, 'error');
  if (IS_FP_SESSION && amount < FP_MIN) return WH.toast('Freeplay cashout minimum is $' + FP_MIN, 'error');
  startPayFlow('WITHDRAW', amount);
});
// Legacy hidden buttons still wired
document.getElementById('depositBtn')?.addEventListener('click', () => document.getElementById('walletDepBtn')?.click());
document.getElementById('withdrawBtn')?.addEventListener('click', () => document.getElementById('walletWdBtn')?.click());

document.getElementById('wdBackToGw')?.addEventListener('click', () => setPayStep('payStep2'));
document.getElementById('wdGameShot')?.addEventListener('change', async (e) => {
  try {
    wdGameShot = await readFileAsDataUrl(e.target.files?.[0]);
    const prev = document.getElementById('wdGamePreview');
    if (prev) { prev.src = wdGameShot; prev.style.display = 'block'; }
  } catch (err) { WH.toast(err.message || 'Upload failed', 'error'); }
});
document.getElementById('wdTagQrShot')?.addEventListener('change', async (e) => {
  try {
    wdTagShot = await readFileAsDataUrl(e.target.files?.[0]);
    const prev = document.getElementById('wdTagPreview');
    if (prev) { prev.src = wdTagShot; prev.style.display = 'block'; }
  } catch (err) { WH.toast(err.message || 'Upload failed', 'error'); }
});

document.getElementById('wdSubmitBtn').onclick = async function() {
  if (!selectedGw) return WH.toast('Pick a gateway', 'error');
  const nameOnTag = document.getElementById('wdNameOnTag').value.trim();
  const phone = document.getElementById('wdPhone').value.trim();
  const tag = document.getElementById('wdTagCode').value.trim();
  const emailOnTag = document.getElementById('wdEmailOnTag')?.value.trim() || '';
  if (!nameOnTag) return WH.toast('Enter name on tag', 'error');
  if (!tag) return WH.toast('Enter tag / address', 'error');
  if (!phone) return WH.toast('Phone on tag is required', 'error');
  if (selectedGw.require_email_on_tag && !emailOnTag) return WH.toast('Email on tag is required', 'error');
  if (WD_REQUIRE_GAME && !wdGameShot) return WH.toast('Game balance screenshot required', 'error');
  if (WD_REQUIRE_TAG_QR && !wdTagShot) return WH.toast('Tag QR screenshot required', 'error');
  WH.setBtnLoading(this, true, 'SUBMITTING…');
  try {
    await WH.api('/transactions', { method:'POST', body: JSON.stringify({
      type: 'WITHDRAW', amount: payAmount, gateway: selectedGw.name, code: tag,
      name_on_tag: nameOnTag, phone_on_tag: phone, email_on_tag: emailOnTag || undefined,
      screenshot: wdGameShot || undefined, tag_qr_screenshot: wdTagShot || undefined,
      game_title: activeGame.title, game_username: gameUsername(),
      is_freeplay_withdraw: !!IS_FP_SESSION
    })});
    showPay(false);
    wdGameShot = ''; wdTagShot = '';
    document.getElementById('portalMsg').textContent = 'WITHDRAW submitted — coins staff then finance.';
    WH.toast('Withdraw submitted');
    await softRefreshTxs();
  } catch (e) {
    WH.alert(e.data?.error || e.message, 'Withdraw');
  } finally {
    WH.setBtnLoading(this, false);
  }
};

document.getElementById('claimFreeplayTop').onclick = async function() {
  if (!FP_GATE.can_claim) return WH.alert(FP_GATE.message || 'Not eligible for freeplay right now.', 'Freeplay');
  if (!activeGame) {
    WH.alert(FP_GATE.is_first
      ? 'Tap FREEPLAY on a game card (or open a game) so we know which game to load freeplay on.'
      : 'Select a game first, then claim freeplay.', 'Freeplay');
    document.getElementById('gamesAnchor')?.scrollIntoView({ behavior: 'smooth' });
    return;
  }
  await claimFreeplayForActive(this);
};
document.getElementById('logoutBtn').onclick = async () => { await WH.api('/auth/logout',{method:'POST',body:'{}'}); location.href='/login'; };
document.getElementById('getAppBtn')?.addEventListener('click', () => {
  const android = {!! json_encode($f['android_app_url'] ?? '/downloads/winning-heaven.apk') !!};
  const ios = {!! json_encode($f['ios_app_url'] ?? '') !!};
  if (android) window.open(android, '_blank');
  else WH.alert(ios || 'Install the Winning Heaven app from Android APK or iOS home screen.', 'Get the App');
});
document.getElementById('supportFab').onclick = () => {
  const badge = document.getElementById('supportFabBadge');
  if (badge) { badge.classList.remove('is-on'); badge.textContent = '0'; }
  WH.openSupportChat({ email: USER_EMAIL });
};

/* Real-time background sync for requests & approvals without page refresh */
async function syncLobbyLiveState() {
  if (!USER_EMAIL) return;
  try {
    const [accRes, reqRes] = await Promise.all([
      WH.api('/game-accounts?email=' + encodeURIComponent(USER_EMAIL)),
      WH.api('/account-requests?email=' + encodeURIComponent(USER_EMAIL))
    ]);
    const newAccounts = (accRes.items || []).map(a => ({
      game_title: a.game_title, username: a.username, password: a.password
    }));
    const newReqs = (reqRes.items || []).map(r => ({
      game_title: r.game_title, status: r.status
    }));

    if (activeGame) {
      const oldAcc = (GAME_ACCOUNTS || []).find(a => a.game_title === activeGame.title);
      const newAcc = newAccounts.find(a => a.game_title === activeGame.title);

      if (!oldAcc && newAcc) {
        // HQ approved the request live! Update UI dynamically without page refresh
        GAME_ACCOUNTS = newAccounts;
        ACCOUNT_REQS = newReqs;

        document.getElementById('stateNeedAccount').style.display = 'none';
        document.getElementById('statePending').style.display = 'none';
        document.getElementById('stateReady').style.display = 'block';

        document.getElementById('credEmail').textContent = USER_EMAIL || '—';
        document.getElementById('credUser').textContent = newAcc.username;
        document.getElementById('credPass').textContent = newAcc.password;

        WH.playNotificationSound?.();
        WH.toast('🎉 Account approved for ' + activeGame.title + '! Username: ' + newAcc.username, 'ok', 7000);
      } else {
        GAME_ACCOUNTS = newAccounts;
        ACCOUNT_REQS = newReqs;
      }
    } else {
      GAME_ACCOUNTS = newAccounts;
      ACCOUNT_REQS = newReqs;
    }
    await softRefreshTxs();
  } catch (_) {}
}
setInterval(syncLobbyLiveState, 4000);

/* FAB unread badge — poll quietly when chat closed (Jackpot SupportModal parity) */
(function pollSupportBadge() {
  let lastCount = 0;
  async function tick() {
    const chat = document.getElementById('whChat');
    if (chat && chat.classList.contains('is-on')) return;
    try {
      const d = await WH.api('/support?email=' + encodeURIComponent(USER_EMAIL));
      const items = d.items || [];
      const unread = items.filter((m) => String(m.sender_type || '') !== 'player' && !m.read).length
        || items.filter((m) => String(m.sender_type || '') === 'admin' || String(m.sender_type || '') === 'support').length;
      // Simple: if latest message is from staff and chat closed, show badge
      const latest = items[0];
      const badge = document.getElementById('supportFabBadge');
      if (!badge || !latest) return;
      const fromStaff = ['admin', 'support', 'distributor'].includes(String(latest.sender_type || '').toLowerCase());
      if (fromStaff && items.length > lastCount && lastCount > 0) {
        badge.textContent = '!';
        badge.classList.add('is-on');
        WH.playNotificationSound?.();
      }
      lastCount = items.length;
    } catch (_) {}
  }
  setTimeout(tick, 1500);
  setInterval(tick, 4000);
})();

/* ——— Promotions ——— */
let currentPromo = null;
function dismissedPromoIds() {
  try { return JSON.parse(localStorage.getItem('wh_dismissed_promos') || '[]'); } catch { return []; }
}
function dismissPromo(id, permanent) {
  if (permanent && id) {
    const ids = dismissedPromoIds();
    if (!ids.includes(id)) {
      ids.push(id);
      try { localStorage.setItem('wh_dismissed_promos', JSON.stringify(ids)); } catch (_) {}
    }
  }
  currentPromo = null;
  const modal = document.getElementById('promoModal');
  if (modal) { modal.classList.remove('is-on'); modal.style.display = 'none'; }
}
function showPromo(p) {
  currentPromo = p;
  document.getElementById('promoTitle').textContent = p.title || 'Promotion';
  document.getElementById('promoMessage').textContent = p.message || p.description || '';
  const imgWrap = document.getElementById('promoImgWrap');
  const img = document.getElementById('promoImg');
  if (p.image_url || p.image) {
    img.src = p.image_url || p.image;
    imgWrap.style.display = 'block';
  } else imgWrap.style.display = 'none';
  const modal = document.getElementById('promoModal');
  modal.classList.add('is-on');
  modal.style.display = 'block';
}
document.getElementById('promoDismissBtn')?.addEventListener('click', () => dismissPromo(currentPromo?.public_id, false));
document.getElementById('promoClaimBtn')?.addEventListener('click', async function() {
  if (!currentPromo) return;
  WH.setBtnLoading(this, true, 'CLAIMING…');
  try {
    await WH.api('/promotions/claim', { method: 'POST', body: JSON.stringify({ public_id: currentPromo.public_id }) });
    WH.toast('Promotion claimed');
    dismissPromo(currentPromo.public_id, true);
  } catch (e) {
    WH.toast(e.message || 'Could not claim', 'error');
    dismissPromo(currentPromo?.public_id, true);
  } finally {
    WH.setBtnLoading(this, false);
  }
});
(async function loadPromos() {
  try {
    const email = {!! json_encode($user->email ?? '') !!};
    const data = await WH.api('/promotions' + (email ? ('?email=' + encodeURIComponent(email)) : ''));
    const items = data.items || data.promotions || [];
    if (!items.length) return;
    const dismissed = dismissedPromoIds();
    const unseen = items.filter(p => p.public_id && !dismissed.includes(p.public_id));
    if (unseen.length) showPromo(unseen[0]);
  } catch (_) {}
})();
(async function subscribeVipPrompt() {
  try {
    if (localStorage.getItem('wh_subscribed_once') === '1') return;
    const already = {!! json_encode((bool) ($user->is_subscribed ?? false)) !!};
    if (already) {
      localStorage.setItem('wh_subscribed_once', '1');
      return;
    }
    const ok = await WH.confirm(
      'Get VIP promo alerts in lobby (and email when SMTP is on)? Same subscribe flow as Jackpot.',
      'Winning Heaven VIP'
    );
    localStorage.setItem('wh_subscribed_once', '1');
    if (!ok) return;
    await WH.api('/users/subscribe', { method: 'POST', body: JSON.stringify({ is_subscribed: true }) });
    WH.toast('You are subscribed to promotions');
  } catch (_) {}
})();
</script>

@endpush

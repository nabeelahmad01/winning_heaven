@extends('layouts.app')
@section('title', 'Referrals — Winning Heaven')
@section('content')
@php $f = $frontend; $logo = $f['logo_url'] ?? '/brand/logo.png'; @endphp
<div class="wh-lobby">
  <nav class="wh-nav">
    <div class="wh-brand-mark">
      <img src="{{ asset(ltrim($logo,'/')) }}" alt="">
      <div><strong>Refer center</strong><span>{{ $user->referral_code }}</span></div>
    </div>
    <div class="wh-nav__actions">
      <a class="wh-nav__btn" href="{{ route('lobby') }}"><i class="fa-solid fa-arrow-left"></i> Lobby</a>
    </div>
  </nav>
  <main class="wh-main">
    <section class="wh-tile" style="margin-bottom:1rem">
      <h2 style="font-family:var(--font-display);margin-top:0">Love Winning Heaven?</h2>
      <p style="color:var(--mute)">Share your link. When friends deposit, you earn referral rewards — same rules: referral % on successful deposits, claim into a game as coins allotment.</p>
      <div class="wh-field" style="margin-top:1rem"><label>Your invite link</label>
        <div class="box"><i class="fa-solid fa-link"></i><input id="refLink" readonly value="{{ $referralLink }}"><button type="button" class="wh-eye" id="copyRef" style="color:var(--sand);font-weight:700">Copy</button></div>
      </div>
      <p style="color:var(--mute);font-size:.9rem">Code: <strong style="color:var(--sand)">{{ $user->referral_code }}</strong></p>
    </section>

    <div class="wh-bento">
      <article class="wh-tile">
        <h3>How it works</h3>
        <p style="margin-top:.6rem;color:var(--mute)">1. Share your link</p>
        <p style="margin-top:.4rem;color:var(--mute)">2. Friend signs up & deposits</p>
        <p style="margin-top:.4rem;color:var(--mute)">3. You get referral reward %</p>
        <p style="margin-top:.4rem;color:var(--mute)">4. Claim into a chosen game (coins staff loads)</p>
      </article>
      <article class="wh-tile">
        <h3>Pending rewards</h3>
        @forelse($pending as $p)
          <div style="padding:.65rem 0;border-bottom:1px solid var(--line);font-size:.9rem" data-pending-row="{{ $p->public_id }}">
            <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start;flex-wrap:wrap">
              <div>
                {{ $p->referee_email }} · {{ $p->reward_coins }} coins
                · <span class="wh-badge">{{ $p->status }}</span>
              </div>
              @if(strtoupper((string) $p->status) === 'PENDING')
                <button type="button" class="wh-btn-sm" data-claim-ref="{{ $p->public_id }}" data-coins="{{ $p->reward_coins }}">Claim</button>
              @endif
            </div>
            @if(strtoupper((string) $p->status) === 'PENDING')
              <div class="wh-field" style="margin-top:.55rem;display:none" data-claim-pick="{{ $p->public_id }}">
                <label>Pick a game</label>
                <div class="box">
                  <i class="fa-solid fa-gamepad"></i>
                  <select data-game-select="{{ $p->public_id }}" style="flex:1;background:transparent;border:0;color:inherit;outline:none;font:inherit;padding:.55rem .25rem">
                    <option value="">Select game…</option>
                    @foreach($games as $game)
                      <option value="{{ $game->title }}">{{ $game->title }}</option>
                    @endforeach
                  </select>
                </div>
                <button type="button" class="wh-cta" style="margin-top:.55rem" data-claim-confirm="{{ $p->public_id }}">Confirm claim</button>
              </div>
            @endif
          </div>
        @empty
          <p style="color:var(--mute)">No pending rewards yet.</p>
        @endforelse
      </article>
    </div>

    <div class="wh-rail-head"><h2>Your referrals</h2></div>
    <div class="wh-tile">
      @forelse($referred as $r)
        <div style="padding:.55rem 0;border-bottom:1px solid var(--line)">{{ $r->name }} · {{ $r->email }} · joined {{ $r->created_at }}</div>
      @empty
        <p style="color:var(--mute)">No referred players yet — share your link.</p>
      @endforelse
    </div>
  </main>
</div>
@endsection
@push('scripts')
<script>
document.getElementById('copyRef').onclick = () => {
  const v = document.getElementById('refLink').value;
  navigator.clipboard?.writeText(v); WH.toast('Referral link copied');
};

document.querySelectorAll('[data-claim-ref]').forEach(btn => {
  btn.onclick = () => {
    const id = btn.dataset.claimRef;
    const pick = document.querySelector('[data-claim-pick="' + id + '"]');
    if (pick) pick.style.display = pick.style.display === 'none' ? 'block' : 'none';
  };
});

document.querySelectorAll('[data-claim-confirm]').forEach(btn => {
  btn.onclick = async () => {
    const id = btn.dataset.claimConfirm;
    const sel = document.querySelector('[data-game-select="' + id + '"]');
    const gameTitle = (sel && sel.value || '').trim();
    if (!gameTitle) return WH.toast('Select a game first', 'error');
    btn.disabled = true;
    try {
      await WH.api('/referrals/pending/claim', {
        method: 'POST',
        body: JSON.stringify({ public_id: id, game_title: gameTitle })
      });
      WH.toast('Referral reward claimed for ' + gameTitle);
      const row = document.querySelector('[data-pending-row="' + id + '"]');
      if (row) {
        row.innerHTML = '<div style="color:var(--mute)">Claimed · ' + gameTitle + ' · <span class="wh-badge">CLAIMED</span></div>';
      }
    } catch (e) {
      WH.alert(e.data?.error || e.message, 'Referral claim');
      btn.disabled = false;
    }
  };
});
</script>
@endpush

@php
  $f = $frontend ?? [];
  $logo = $f['logo_url'] ?? '/brand/logo.png';
  $user = $user ?? Auth::user();
  $activeTab = $activeTab ?? 'lobby';
@endphp
<nav class="wh-nav">
  <a class="wh-brand-mark" href="{{ route('lobby') }}" style="text-decoration:none;color:inherit">
    <img src="{{ str_starts_with($logo, 'data:') ? $logo : asset(ltrim($logo,'/')) }}" alt="Winning Heaven">
    <div>
      <strong>Winning Heaven</strong>
      @if($user)
        <span>{{ $user->name }} · {{ $user->referral_code }}</span>
      @else
        <span>VIP Casino Portal</span>
      @endif
    </div>
  </a>
  <div class="wh-nav__actions">
    @if(!empty($f['get_app_enabled']))
      <button type="button" class="wh-nav__btn wh-nav__btn--mint" id="getAppBtn"><i class="fa-solid fa-mobile-screen-button"></i> Get App</button>
    @endif
    <a class="wh-nav__btn {{ $activeTab === 'referrals' ? 'is-active' : '' }}" href="{{ route('referrals') }}"><i class="fa-solid fa-gift"></i> Refer</a>
    @if(Auth::check())
      <button type="button" class="wh-nav__btn" id="logoutBtn"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
    @else
      <a class="wh-nav__btn" href="{{ route('login') }}"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
    @endif
  </div>
</nav>

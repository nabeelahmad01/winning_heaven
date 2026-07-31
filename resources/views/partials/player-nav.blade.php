@php
  $f = $frontend ?? [];
  $logo = $f['logo_url'] ?? '/brand/logo.png';
  $user = $user ?? Auth::user();
  $activeTab = $activeTab ?? 'lobby';
@endphp
<nav class="wh-nav">
  <div class="wh-brand-mark">
    <img src="{{ str_starts_with($logo, 'data:') ? $logo : asset(ltrim($logo,'/')) }}" alt="Winning Heaven">
    <div>
      <strong>Winning Heaven</strong>
      @if($user)
        <span>{{ $user->name }} · {{ $user->referral_code }}</span>
      @else
        <span>VIP Casino Portal</span>
      @endif
    </div>
  </div>
  <div class="wh-nav__actions">
    @if(!empty($f['get_app_enabled']))
      <button type="button" class="wh-nav__btn wh-nav__btn--mint" id="getAppBtn"><i class="fa-solid fa-mobile-screen-button"></i> Get App</button>
    @endif
    <a class="wh-nav__btn {{ $activeTab === 'lobby' ? 'is-active' : '' }}" href="{{ route('lobby') }}"><i class="fa-solid fa-house"></i> Lobby</a>
    <a class="wh-nav__btn {{ $activeTab === 'referrals' ? 'is-active' : '' }}" href="{{ route('referrals') }}"><i class="fa-solid fa-gift"></i> Refer</a>
    @if(!isset($f['info_show_on_lobby']) || !empty($f['info_show_on_lobby']))
    @if(!isset($f['info_page_enabled']) || !empty($f['info_page_enabled']))
    <a class="wh-nav__btn {{ $activeTab === 'info' ? 'is-active' : '' }}" href="{{ route('info') }}"><i class="fa-solid fa-circle-info"></i> Info</a>
    @endif
    @endif
    @if(Auth::check())
      <button type="button" class="wh-nav__btn" id="logoutBtn"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
    @else
      <a class="wh-nav__btn" href="{{ route('login') }}"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
    @endif
  </div>
</nav>

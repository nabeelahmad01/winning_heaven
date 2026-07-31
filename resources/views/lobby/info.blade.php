@extends('layouts.app')
@section('title', 'Info — Winning Heaven')
@section('content')
@php
  $f = $frontend;
  $logo = $f['logo_url'] ?? '/brand/logo.png';
  $enabled = !isset($f['info_page_enabled']) || !empty($f['info_page_enabled']);
  $channels = [
    [
      'id' => 'instagram',
      'on' => !isset($f['info_instagram_enabled']) || !empty($f['info_instagram_enabled']),
      'label' => $f['info_instagram_label'] ?? 'Instagram',
      'handle' => $f['info_instagram_handle'] ?? '@winningheaven',
      'href' => $f['info_instagram_url'] ?? 'https://www.instagram.com/winningheaven',
      'icon' => 'fa-brands fa-instagram',
      'accent' => 'instagram',
    ],
    [
      'id' => 'telegram',
      'on' => !isset($f['info_telegram_enabled']) || !empty($f['info_telegram_enabled']),
      'label' => $f['info_telegram_label'] ?? 'Telegram',
      'handle' => $f['info_telegram_handle'] ?? 't.me/WinningHeaven',
      'href' => $f['info_telegram_url'] ?? 'https://t.me/WinningHeaven',
      'icon' => 'fa-brands fa-telegram',
      'accent' => 'telegram',
    ],
    [
      'id' => 'facebook',
      'on' => !isset($f['info_facebook_enabled']) || !empty($f['info_facebook_enabled']),
      'label' => $f['info_facebook_label'] ?? 'Facebook',
      'handle' => $f['info_facebook_handle'] ?? 'Winning Heaven',
      'href' => $f['info_facebook_url'] ?? 'https://www.facebook.com/winningheaven',
      'icon' => 'fa-brands fa-facebook',
      'accent' => 'facebook',
    ],
    [
      'id' => 'whatsapp',
      'on' => !isset($f['info_whatsapp_enabled']) || !empty($f['info_whatsapp_enabled']),
      'label' => $f['info_whatsapp_label'] ?? 'WhatsApp',
      'handle' => $f['info_whatsapp_handle'] ?? '+1 000 000 0000',
      'href' => $f['info_whatsapp_url'] ?? 'https://wa.me/',
      'icon' => 'fa-brands fa-whatsapp',
      'accent' => 'whatsapp',
    ],
    [
      'id' => 'email',
      'on' => !isset($f['info_email_enabled']) || !empty($f['info_email_enabled']),
      'label' => $f['info_email_label'] ?? 'Email Support',
      'handle' => $f['info_email_handle'] ?? 'support@winningheaven.com',
      'href' => $f['info_email_url'] ?? 'mailto:support@winningheaven.com',
      'icon' => 'fa-solid fa-envelope',
      'accent' => 'email',
    ],
  ];
  $channels = array_values(array_filter($channels, fn ($c) => $c['on'] && trim((string) $c['href']) !== ''));
  $emailCh = collect($channels)->firstWhere('id', 'email');
  $supportEmail = $emailCh['handle'] ?? 'support@winningheaven.com';
  $supportMailto = $emailCh['href'] ?? ('mailto:'.$supportEmail);
@endphp
<div class="wh-lobby" style="background:transparent;padding-bottom:0">
  @include('partials.player-nav', ['activeTab' => 'info'])
</div>
<main class="wh-info">
  <div class="wh-info__glow wh-info__glow--a" aria-hidden="true"></div>
  <div class="wh-info__glow wh-info__glow--b" aria-hidden="true"></div>
  <div class="wh-info__grid" aria-hidden="true"></div>

  <div class="wh-info__inner">
    <header class="wh-info__top">
      <a href="/lobby" class="wh-info__back"><i class="fa-solid fa-chevron-left"></i> Back to lobby</a>
    </header>

    <section class="wh-info__hero">
      <div class="wh-info__logo-wrap">
        <img src="{{ asset(ltrim($logo,'/')) }}" alt="Winning Heaven" class="wh-info__logo">
        <span class="wh-info__logo-ring" aria-hidden="true"></span>
      </div>
      <h1 class="wh-info__brand">
        <span>WINNING</span>
        <span>HEAVEN</span>
      </h1>
      <p class="wh-info__tag">{{ $f['info_tagline'] ?? 'PLAY SMARTER. CASHOUT FASTER.' }}</p>
      <p class="wh-info__lead">{{ $f['info_lead'] ?? 'Official channels for updates, community, and player support. Reach us anytime — we\'re here to help you win big.' }}</p>
    </section>

    @if(!$enabled)
      <section class="wh-info__note">
        <i class="fa-solid fa-circle-info"></i>
        <p>This info page is currently turned off by the administrator.</p>
      </section>
    @else
      <section class="wh-info__channels" aria-label="Contact channels">
        @forelse($channels as $ch)
          <a
            class="wh-info__channel wh-info__channel--{{ $ch['accent'] }}"
            href="{{ $ch['href'] }}"
            @if($ch['id'] !== 'email') target="_blank" rel="noopener noreferrer" @endif
          >
            <span class="wh-info__channel-icon" aria-hidden="true"><i class="{{ $ch['icon'] }}"></i></span>
            <span class="wh-info__channel-copy">
              <strong>{{ $ch['label'] }}</strong>
              <span>{{ $ch['handle'] }}</span>
            </span>
            <i class="fa-solid fa-arrow-up-right-from-square wh-info__channel-arrow" aria-hidden="true"></i>
          </a>
        @empty
          <p class="wh-info__lead" style="text-align:center">No contact channels are enabled right now.</p>
        @endforelse
      </section>

      <section class="wh-info__note">
        <i class="fa-solid fa-headset"></i>
        <p>
          {{ $f['info_support_note'] ?? 'For account help, deposits, or withdrawals, email support and our team will get back to you.' }}
          <a href="{{ $supportMailto }}">{{ $supportEmail }}</a>
        </p>
      </section>
    @endif

    <footer class="wh-info__foot">
      <a href="/login" class="wh-cta wh-info__cta">ENTER LOBBY →</a>
      <div class="wh-info__portals">
        <a href="/admin/login">HQ Staff</a>
        <a href="/distributor">Distributor</a>
        <a href="/affiliate">Affiliate</a>
      </div>
      <p class="wh-info__copy">© {{ date('Y') }} Winning Heaven</p>
    </footer>
  </div>
</main>
@endsection

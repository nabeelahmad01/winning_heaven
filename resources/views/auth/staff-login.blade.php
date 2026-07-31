@extends('layouts.app')
@section('title', $title ?? 'Staff Login')
@section('content')
@php $bg = $frontend['login_bg_url'] ?? '/brand/bg.png'; @endphp
<div class="wh-auth" style="--wh-bg: url('{{ asset(ltrim($bg,'/')) }}')">
  <div class="wh-auth__sky"></div>
  <div class="wh-auth__top">
    <div class="wh-brand-mark">
      <img src="{{ asset('brand/logo.png') }}" alt="">
      <div><strong>Winning Heaven</strong><span>{{ $subtitle ?? 'Staff portal' }}</span></div>
    </div>
    <a class="wh-chip" href="/login">Player login</a>
  </div>
  <div class="wh-auth__hero">
    <h1>{{ $title ?? 'HQ Staff' }}<br><em>Secure desk</em></h1>
    <p>Separate staff gate — same approvals flow, Winning Heaven styling.</p>
  </div>
  <div class="wh-sheet">
    <div class="wh-err" id="loginErr"></div>
    <form id="staffLogin">
      <div class="wh-field"><label>Staff email</label><div class="box"><i class="fa-solid fa-user-shield"></i><input type="email" name="email" required placeholder="admin@winningheaven.com"></div></div>
      <div class="wh-field"><label>Password</label><div class="box"><i class="fa-solid fa-lock"></i><input type="password" name="password" required></div></div>
      <button class="wh-primary" type="submit">Enter HQ</button>
    </form>
  </div>
</div>
@endsection
@push('scripts')
<script>
document.getElementById('staffLogin').onsubmit = async (e) => {
  e.preventDefault();
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const err = document.getElementById('loginErr'); err.style.display='none';
  WH.setBtnLoading(submitBtn, true, 'ENTER HQ…');
  try {
    const data = await WH.api('/auth/login', { method:'POST', body: JSON.stringify(Object.fromEntries(new FormData(e.target))) });
    const u = data.user || {};
    const staff = u.role === 'admin' || (u.roles||[]).length || ['operation_admin','financial_admin','coins_admin','support_admin'].includes(u.role);
    if (!staff) {
      WH.setBtnLoading(submitBtn, false);
      err.style.display='block'; err.textContent = 'Not a staff account'; return;
    }
    location.href = '/admin';
  } catch (ex) {
    WH.setBtnLoading(submitBtn, false);
    err.style.display='block'; err.textContent = ex.data?.errors?.email?.[0] || ex.message;
  }
};
</script>
@endpush

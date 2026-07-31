@extends('layouts.app')
@section('title', 'Winning Heaven — Sign In')
@section('content')
@php
  $bg = $frontend['login_bg_url'] ?? '/brand/auth-casino.png';
  $logo = $frontend['logo_url'] ?? '/brand/logo.png';
  $tab = request('tab', 'login');
  $authVisual = '/brand/auth-casino.png';
@endphp
<div class="wh-auth-mobile-bg" style="--wh-bg:url('{{ asset(ltrim($authVisual,'/')) }}')"></div>
<div class="wh-auth-split">
  <div class="wh-auth-split__visual">
    <img class="wh-auth-split__img" src="{{ asset(ltrim($authVisual,'/')) }}" alt="">
    <div class="wh-auth-split__shade" aria-hidden="true"></div>
    <div class="wh-auth-vip">
      <span class="wh-auth-vip__badge"><i class="fa-solid fa-crown"></i> VIP LOBBY</span>
      <h2>PLAY BIG.<br>CASH OUT FAST.</h2>
      <p>Secure accounts · Instant gateways · 24/7 support</p>
      <div class="wh-auth-vip__grid">
        <div><i class="fa-solid fa-bolt"></i><strong>Instant payouts</strong><span>Withdraw anytime</span></div>
        <div><i class="fa-solid fa-shield-halved"></i><strong>Bank-grade lock</strong><span>Encrypted sessions</span></div>
        <div><i class="fa-solid fa-gift"></i><strong>$3 Freeplay</strong><span>On signup claim</span></div>
        <div><i class="fa-solid fa-headset"></i><strong>Live support</strong><span>Always online</span></div>
      </div>
    </div>
  </div>

  <div class="wh-auth-split__form" style="--wh-bg:url('{{ asset(ltrim($authVisual,'/')) }}')">
    <div class="wh-auth-split__form-inner">
    <div class="wh-welcome">
      <div>
        <div class="wh-welcome__title">{{ $frontend['landing_welcome'] ?? 'WELCOME TO WINNING HEAVEN' }}</div>
        <div class="wh-welcome__sub">{{ $frontend['landing_grab'] ?? 'Grab amazing bonuses and win big!' }}</div>
      </div>
      <div style="display:flex;align-items:center;gap:.5rem">
        @if(!empty($frontend['info_show_on_auth']))
          <a class="wh-chip" href="/info"><i class="fa-solid fa-circle-info"></i> Info</a>
        @endif
        <img src="{{ asset(ltrim($logo,'/')) }}" alt="Winning Heaven">
      </div>
    </div>

    <div class="wh-wordmark">
      <span>WINNING</span>
      <span>HEAVEN</span>
    </div>

    <div class="wh-card wh-card--vip">
      <div class="wh-card__glow"></div>
      <div class="wh-card__body">
        <div class="wh-tabs" id="authTabs">
          <button type="button" class="{{ $tab !== 'register' && $tab !== 'forgot' ? 'is-on' : '' }}" data-tab="login">Login</button>
          <button type="button" class="{{ $tab === 'register' ? 'is-on' : '' }}" data-tab="register">Register</button>
        </div>

        <div class="wh-panel {{ $tab !== 'register' && $tab !== 'forgot' ? 'is-on' : '' }}" id="panel-login">
          <button type="button" class="wh-google" id="googleLoginBtn">
            <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/></svg>
            {{ $frontend['landing_login_with_google'] ?? 'Continue with Google' }}
          </button>
          <div class="wh-warn"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $frontend['landing_messenger_warning'] ?? 'Google sign-in is not supported inside Messenger. Open in Chrome or Safari.' }}</span></div>
          <div class="wh-or">or login with email</div>
          <div class="wh-err" id="loginErr"></div>
          <form id="loginForm">
            <div class="wh-field"><label>Email Address</label><div class="box"><i class="fa-solid fa-envelope"></i><input type="email" name="email" required placeholder="example@email.com" autocomplete="email"></div></div>
            <div class="wh-field"><label>Password</label><div class="box"><i class="fa-solid fa-lock"></i><input type="password" name="password" id="login-password" required placeholder="••••••••" autocomplete="current-password"><button type="button" class="wh-eye" data-target="login-password"><i class="fa-solid fa-eye"></i></button></div></div>
            <button class="wh-primary" type="submit">LOGIN</button>
          </form>
          <div class="wh-linkrow"><button type="button" data-tab="forgot">FORGOT PASSWORD?</button></div>
        </div>

        <div class="wh-panel {{ $tab === 'register' ? 'is-on' : '' }}" id="panel-register">
          <button type="button" class="wh-google" id="googleRegisterBtn">
            <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/></svg>
            {{ $frontend['landing_signup_with_google'] ?? 'Sign up with Google' }}
          </button>
          <div class="wh-warn"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $frontend['landing_messenger_warning'] ?? 'Google sign-in is not supported inside Messenger.' }}</span></div>
          <div class="wh-or">or create account with email</div>
          <div class="wh-err" id="regErr"></div>
          <form id="registerForm">
            <div class="wh-field"><label>Full Name</label><div class="box"><i class="fa-solid fa-user"></i><input name="name" required placeholder="Your name" autocomplete="name"></div></div>
            <div class="wh-field"><label>Email Address</label><div class="box"><i class="fa-solid fa-envelope"></i><input type="email" name="email" required placeholder="example@email.com" autocomplete="email"></div></div>
            <div class="wh-field"><label>Password</label><div class="box"><i class="fa-solid fa-lock"></i><input type="password" name="password" id="reg-password" required minlength="6" autocomplete="new-password"><button type="button" class="wh-eye" data-target="reg-password"><i class="fa-solid fa-eye"></i></button></div></div>
            <div class="wh-field"><label>Referral code (optional)</label><div class="box"><i class="fa-solid fa-gift"></i><input name="referral_code" placeholder="Friend code"></div></div>
            <button class="wh-primary" type="submit">CONTINUE → VERIFY EMAIL</button>
          </form>
        </div>

        <div class="wh-panel {{ $tab === 'forgot' ? 'is-on' : '' }}" id="panel-forgot">
          <p class="wh-auth-hint">Enter your email — we’ll send a 6-digit recovery code.</p>
          <div class="wh-err" id="forgotErr"></div>
          <form id="forgotForm">
            <div class="wh-field"><label>Email</label><div class="box"><i class="fa-solid fa-envelope"></i><input type="email" name="email" required autocomplete="email"></div></div>
            <button class="wh-primary" type="submit">SEND CODE</button>
          </form>
          <div class="wh-linkrow"><button type="button" data-tab="login">BACK TO LOGIN</button></div>
        </div>

        <div class="wh-panel" id="panel-otp">
          <p class="wh-auth-hint" id="otpHint">Enter the 6-digit code we emailed you.</p>
          <div class="wh-err" id="otpErr"></div>
          <div class="wh-otp" id="otpBoxes">
            @for($i = 0; $i < 6; $i++)
              <input class="wh-otp__box" type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code" data-otp-i="{{ $i }}">
            @endfor
          </div>
          <button class="wh-primary" type="button" id="otpVerifyBtn">VERIFY CODE</button>
          <div class="wh-linkrow">
            <button type="button" id="otpResendBtn" disabled>RESEND CODE</button>
            <button type="button" id="otpBackBtn">BACK</button>
          </div>
        </div>

        <div class="wh-panel" id="panel-reset">
          <p class="wh-auth-hint">Create a new password for your account.</p>
          <div class="wh-err" id="resetErr"></div>
          <form id="resetForm">
            <div class="wh-field"><label>New password</label><div class="box"><i class="fa-solid fa-lock"></i><input type="password" name="password" id="reset-password" required minlength="6" autocomplete="new-password"><button type="button" class="wh-eye" data-target="reset-password"><i class="fa-solid fa-eye"></i></button></div></div>
            <div class="wh-field"><label>Confirm password</label><div class="box"><i class="fa-solid fa-lock"></i><input type="password" name="password_confirm" id="reset-password2" required minlength="6" autocomplete="new-password"></div></div>
            <button class="wh-primary" type="submit">UPDATE PASSWORD</button>
          </form>
        </div>
      </div>
    </div>
    </div>
  </div>
</div>
@endsection
@push('scripts')
<script>
function showTab(name) {
  const tabs = document.getElementById('authTabs');
  if (tabs) tabs.style.display = (name === 'otp' || name === 'reset') ? 'none' : '';
  document.querySelectorAll('.wh-tabs button').forEach(b => b.classList.toggle('is-on', b.dataset.tab === name));
  document.querySelectorAll('.wh-panel').forEach(p => p.classList.remove('is-on'));
  document.getElementById('panel-' + name)?.classList.add('is-on');
}
document.querySelectorAll('[data-tab]').forEach(b => b.addEventListener('click', () => showTab(b.dataset.tab)));
document.querySelectorAll('.wh-eye').forEach(btn => btn.onclick = () => {
  const i = document.getElementById(btn.dataset.target); if (i) i.type = i.type === 'password' ? 'text' : 'password';
});

let otpPurpose = 'register';
let otpEmail = '';
let otpName = '';
let pendingRegister = null;
let verifiedToken = '';
let resendLeft = 0;
let resendTimer = null;

function otpValue() {
  return Array.from(document.querySelectorAll('.wh-otp__box')).map(i => i.value).join('');
}
function clearOtpBoxes() {
  document.querySelectorAll('.wh-otp__box').forEach(i => { i.value = ''; });
  document.querySelector('.wh-otp__box')?.focus();
}
document.querySelectorAll('.wh-otp__box').forEach((box) => {
  box.addEventListener('input', () => {
    box.value = box.value.replace(/\D/g, '').slice(0, 1);
    const i = Number(box.dataset.otpI);
    if (box.value && i < 5) document.querySelector('.wh-otp__box[data-otp-i="' + (i + 1) + '"]')?.focus();
  });
  box.addEventListener('keydown', (e) => {
    const i = Number(box.dataset.otpI);
    if (e.key === 'Backspace' && !box.value && i > 0) {
      document.querySelector('.wh-otp__box[data-otp-i="' + (i - 1) + '"]')?.focus();
    }
  });
  box.addEventListener('paste', (e) => {
    const t = (e.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 6);
    if (t.length < 2) return;
    e.preventDefault();
    document.querySelectorAll('.wh-otp__box').forEach((el, idx) => { el.value = t[idx] || ''; });
  });
});

function startResendCountdown(sec) {
  resendLeft = sec;
  const btn = document.getElementById('otpResendBtn');
  clearInterval(resendTimer);
  const tick = () => {
    if (resendLeft <= 0) {
      btn.disabled = false;
      btn.textContent = 'RESEND CODE';
      clearInterval(resendTimer);
      return;
    }
    btn.disabled = true;
    btn.textContent = 'RESEND IN ' + resendLeft + 's';
    resendLeft -= 1;
  };
  tick();
  resendTimer = setInterval(tick, 1000);
}

async function sendOtp(purpose, email, name) {
  return WH.api('/auth/send-otp', {
    method: 'POST',
    body: JSON.stringify({ purpose, email, name: name || undefined })
  });
}

async function openOtp(purpose, email, name) {
  otpPurpose = purpose;
  otpEmail = email;
  otpName = name || '';
  verifiedToken = '';
  clearOtpBoxes();
  document.getElementById('otpErr').style.display = 'none';
  document.getElementById('otpHint').textContent = purpose === 'register'
    ? 'We sent a 6-digit code to ' + email + ' — verify to finish signup.'
    : 'Enter the recovery code sent to ' + email + '.';
  showTab('otp');
  startResendCountdown(30);
}

document.getElementById('otpBackBtn').onclick = () => {
  showTab(otpPurpose === 'register' ? 'register' : 'forgot');
};
document.getElementById('otpResendBtn').onclick = async () => {
  try {
    const r = await sendOtp(otpPurpose, otpEmail, otpName);
    WH.toast(r.message || 'Code resent');
    if (r.debug_otp) WH.toast('Dev OTP: ' + r.debug_otp, 'info');
    startResendCountdown(30);
  } catch (ex) {
    WH.toast(ex.data?.error || ex.message, 'error');
  }
};
document.getElementById('otpVerifyBtn').onclick = async (e) => {
  const btn = e.currentTarget;
  const err = document.getElementById('otpErr');
  err.style.display = 'none';
  const code = otpValue();
  if (code.length !== 6) {
    err.style.display = 'block'; err.textContent = 'Enter all 6 digits.';
    return;
  }
  WH.setBtnLoading(btn, true, 'VERIFYING…');
  try {
    const r = await WH.api('/auth/verify-otp', {
      method: 'POST',
      body: JSON.stringify({ email: otpEmail, purpose: otpPurpose, otp: code })
    });
    verifiedToken = r.verified_token;
    if (otpPurpose === 'register') {
      const body = { ...pendingRegister, verified_token: verifiedToken };
      await WH.api('/auth/register', { method: 'POST', body: JSON.stringify(body) });
      location.href = '/lobby';
    } else {
      WH.setBtnLoading(btn, false);
      showTab('reset');
    }
  } catch (ex) {
    WH.setBtnLoading(btn, false);
    err.style.display = 'block';
    err.textContent = ex.data?.error || ex.message;
  }
};

const handleGoogleClick = (btn) => {
  const clientId = {!! json_encode(config('services.google.client_id', '')) !!};
  if (!clientId) {
    WH.alert('Set GOOGLE_CLIENT_ID in .env, then reload. Google Identity Services will appear on these buttons.', 'Google Sign-in');
    return;
  }
  if (!window.google?.accounts?.id) {
    WH.toast('Loading Google…', 'info');
    return;
  }
  WH.setBtnLoading(btn, true, 'Connecting to Google…');
  try {
    window.google.accounts.id.prompt();
  } catch (_) {}
  setTimeout(() => {
    WH.setBtnLoading(document.getElementById('googleLoginBtn'), false);
    WH.setBtnLoading(document.getElementById('googleRegisterBtn'), false);
  }, 3500);
};
document.getElementById('googleLoginBtn').onclick = function() { handleGoogleClick(this); };
document.getElementById('googleRegisterBtn').onclick = function() { handleGoogleClick(this); };

(function initGoogle() {
  const clientId = {!! json_encode(config('services.google.client_id', '')) !!};
  if (!clientId) return;
  const s = document.createElement('script');
  s.src = 'https://accounts.google.com/gsi/client';
  s.async = true;
  s.onload = () => {
    try {
      google.accounts.id.initialize({
        client_id: clientId,
        callback: async (resp) => {
          WH.setBtnLoading('#googleLoginBtn', true, 'Logging in with Google…');
          WH.setBtnLoading('#googleRegisterBtn', true, 'Signing up with Google…');
          try {
            const params = new URLSearchParams(location.search);
            await WH.api('/auth/google', {
              method: 'POST',
              body: JSON.stringify({
                credential: resp.credential,
                referral_code: params.get('ref') || undefined,
                distributor_id: params.get('dist') || undefined,
                agent_code: params.get('agent') || undefined,
              })
            });
            location.href = '/lobby';
          } catch (e) {
            WH.setBtnLoading('#googleLoginBtn', false);
            WH.setBtnLoading('#googleRegisterBtn', false);
            WH.alert(e.message || 'Google sign-in failed', 'Google');
          }
        }
      });
    } catch (_) {}
  };
  document.head.appendChild(s);
})();

const params = new URLSearchParams(location.search);
const preRef = params.get('ref');
if (preRef) {
  const refInput = document.querySelector('#registerForm [name=referral_code]');
  if (refInput) refInput.value = preRef;
  if (params.get('tab') === 'register') showTab('register');
}
const preDist = params.get('dist');
const preAgent = params.get('agent');
if (preDist || preAgent) {
  const form = document.getElementById('registerForm');
  if (form && preDist) {
    let h = form.querySelector('[name=distributor_id]');
    if (!h) { h = document.createElement('input'); h.type='hidden'; h.name='distributor_id'; form.appendChild(h); }
    h.value = preDist;
  }
  if (form && preAgent) {
    let h = form.querySelector('[name=agent_code]');
    if (!h) { h = document.createElement('input'); h.type='hidden'; h.name='agent_code'; form.appendChild(h); }
    h.value = preAgent;
  }
  if (params.get('tab') === 'register') showTab('register');
}

document.getElementById('loginForm').onsubmit = async (e) => {
  e.preventDefault();
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const err = document.getElementById('loginErr'); err.style.display='none';
  WH.setBtnLoading(submitBtn, true, 'LOGGING IN…');
  try {
    const data = await WH.api('/auth/login', { method:'POST', body: JSON.stringify(Object.fromEntries(new FormData(e.target))) });
    const role = data.user?.role || '';
    location.href = (role === 'admin' || (data.user?.roles||[]).length) ? '/admin' : '/lobby';
  } catch (ex) {
    WH.setBtnLoading(submitBtn, false);
    err.style.display='block'; err.textContent = ex.data?.errors?.email?.[0] || ex.data?.error || ex.message;
  }
};

document.getElementById('registerForm').onsubmit = async (e) => {
  e.preventDefault();
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const err = document.getElementById('regErr'); err.style.display='none';
  const body = Object.fromEntries(new FormData(e.target));
  WH.setBtnLoading(submitBtn, true, 'SENDING CODE…');
  try {
    const check = await WH.api('/auth/register?email=' + encodeURIComponent(body.email));
    if (check.exists) {
      WH.setBtnLoading(submitBtn, false);
      err.style.display = 'block';
      err.textContent = 'Email already registered. Please login.';
      return;
    }
    pendingRegister = body;
    const r = await sendOtp('register', body.email, body.name);
    WH.toast(r.message || 'Code sent');
    if (r.debug_otp) WH.toast('Dev OTP: ' + r.debug_otp, 'info');
    WH.setBtnLoading(submitBtn, false);
    await openOtp('register', body.email, body.name);
  } catch (ex) {
    WH.setBtnLoading(submitBtn, false);
    err.style.display='block';
    err.textContent = ex.data?.error || (ex.data?.errors ? JSON.stringify(ex.data.errors) : ex.message);
  }
};

document.getElementById('forgotForm').onsubmit = async (e) => {
  e.preventDefault();
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const err = document.getElementById('forgotErr'); err.style.display='none';
  const email = new FormData(e.target).get('email');
  WH.setBtnLoading(submitBtn, true, 'SENDING CODE…');
  try {
    const r = await sendOtp('reset', email);
    WH.toast(r.message || 'Code sent');
    if (r.debug_otp) WH.toast('Dev OTP: ' + r.debug_otp, 'info');
    WH.setBtnLoading(submitBtn, false);
    await openOtp('reset', email);
  } catch (ex) {
    WH.setBtnLoading(submitBtn, false);
    err.style.display='block';
    err.textContent = ex.data?.error || ex.message;
  }
};

document.getElementById('resetForm').onsubmit = async (e) => {
  e.preventDefault();
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const err = document.getElementById('resetErr'); err.style.display='none';
  const fd = new FormData(e.target);
  const password = fd.get('password');
  const confirm = fd.get('password_confirm');
  if (password !== confirm) {
    err.style.display='block'; err.textContent = 'Passwords do not match.';
    return;
  }
  WH.setBtnLoading(submitBtn, true, 'UPDATING…');
  try {
    await WH.api('/auth/reset-password', {
      method: 'POST',
      body: JSON.stringify({ email: otpEmail, password, verified_token: verifiedToken })
    });
    WH.toast('Password updated — login now');
    WH.setBtnLoading(submitBtn, false);
    showTab('login');
  } catch (ex) {
    WH.setBtnLoading(submitBtn, false);
    err.style.display='block';
    err.textContent = ex.data?.error || ex.message;
  }
};
</script>
@endpush

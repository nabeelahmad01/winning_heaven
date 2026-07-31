<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="theme-color" content="#07131f">
  @php
    $frontend = $frontend ?? [];
    $fav = !empty($frontend['favicon_url']) ? $frontend['favicon_url'] : ((!empty($frontend['logo_url'])) ? $frontend['logo_url'] : '/brand/logo.png');
    $favUrl = str_starts_with($fav, 'data:') ? $fav : asset(ltrim($fav, '/'));
    $splashLogo = ($frontend['logo_url'] ?? null) ?: '/brand/logo.png';
    $reqUri = request()->path();
    if (str_starts_with($reqUri, 'admin') || str_starts_with($reqUri, 'finance') || str_starts_with($reqUri, 'operations') || str_starts_with($reqUri, 'coins') || str_starts_with($reqUri, 'support-staff') || str_starts_with($reqUri, 'boss')) {
      $pwaManifest = asset('manifest-admin.json');
      $pwaTitle = 'Winning Heaven HQ';
    } elseif (str_starts_with($reqUri, 'distributor')) {
      $pwaManifest = asset('manifest-distributor.json');
      $pwaTitle = 'Winning Heaven Distributor';
    } elseif (str_starts_with($reqUri, 'affiliate')) {
      $pwaManifest = asset('manifest-affiliate.json');
      $pwaTitle = 'Winning Heaven Affiliate';
    } else {
      $pwaManifest = asset('manifest.json');
      $pwaTitle = 'Winning Heaven';
    }
  @endphp
  <title>@yield('title', $pwaTitle)</title>
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="{{ $pwaTitle }}">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="application-name" content="{{ $pwaTitle }}">
  <link rel="manifest" href="{{ $pwaManifest }}">
  <link rel="icon" type="image/png" href="{{ $favUrl }}">
  <link rel="shortcut icon" href="{{ $favUrl }}">
  <link rel="apple-touch-icon" href="{{ $favUrl }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/wh-fresh.css') }}">
  @stack('head')
</head>
<body>
  <div class="wh-splash is-on" id="whSplash" aria-hidden="false">
    <div class="wh-splash__medal">
      <img src="{{ str_starts_with($splashLogo, 'data:') ? $splashLogo : asset(ltrim($splashLogo, '/')) }}" alt="Winning Heaven">
    </div>
    <p class="wh-splash__brand">WINNING<span>HEAVEN</span></p>
    <p class="wh-splash__sub">Initializing…</p>
    <div class="wh-splash__spin" aria-hidden="true"></div>
  </div>

  @yield('content')

  <div class="wh-ui-backdrop" id="whUiBackdrop" aria-hidden="true">
    <div class="wh-ui-modal" role="dialog" aria-modal="true" aria-labelledby="whUiTitle">
      <div class="wh-ui-modal__bar"></div>
      <div class="wh-ui-modal__body">
        <h3 class="wh-ui-modal__title" id="whUiTitle">Notice</h3>
        <p class="wh-ui-modal__text" id="whUiText"></p>
        <div class="wh-ui-modal__fields" id="whUiFields" style="display:none"></div>
        <div class="wh-ui-modal__actions" id="whUiActions"></div>
      </div>
    </div>
  </div>
  <div class="wh-toast-wrap" id="whToastWrap" aria-live="polite"></div>

  <div class="wh-chat" id="whChat" aria-hidden="true">
    <div class="wh-chat__panel">
      <div class="wh-chat__head">
        <div>
          <strong>Live Support</strong>
          <span id="whChatSub">Winning Heaven</span>
        </div>
        <button type="button" class="wh-chat__close" id="whChatClose" aria-label="Close chat">&times;</button>
      </div>
      <div class="wh-chat__body" id="whChatBody"></div>
      <form class="wh-chat__form" id="whChatForm">
        <label class="wh-chat__attach" title="Attach image">
          <i class="fa-solid fa-paperclip"></i>
          <input type="file" id="whChatFile" accept="image/*" hidden>
        </label>
        <input type="text" id="whChatInput" placeholder="Type a message…" autocomplete="off">
        <button type="submit" class="wh-cta" id="whChatSend"><i class="fa-solid fa-paper-plane"></i></button>
      </form>
      <div class="wh-chat__preview" id="whChatPreview" style="display:none">
        <img id="whChatPreviewImg" alt="">
        <button type="button" id="whChatPreviewClear">&times;</button>
      </div>
    </div>
  </div>
  <div class="wh-lightbox" id="whChatLightbox" onclick="this.classList.remove('is-on')">
    <img id="whChatLightboxImg" alt="Attachment">
  </div>

  <script>
    window.WH = {
      csrf: document.querySelector('meta[name="csrf-token"]').content,
      notificationSoundUrl: {!! json_encode($frontend['notification_sound_url'] ?? '') !!},
      _audioUnlocked: false,
      _audioCtx: null,
      _lastSoundAt: 0,
      _toastTimer: null,
      _titleFlashTimer: null,
      _originalTitle: null,
      _unseen: 0,
      _originalFavicon: null,
      setBtnLoading(btn, isLoading, loadingText = 'Processing…') {
        if (typeof btn === 'string') btn = document.querySelector(btn);
        if (!btn) return;
        if (isLoading) {
          if (btn.dataset.origHtml === undefined) {
            btn.dataset.origHtml = btn.innerHTML;
          }
          btn.disabled = true;
          btn.classList.add('is-loading');
          btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin" style="margin-right:.4rem"></i>${loadingText}`;
        } else {
          btn.disabled = false;
          btn.classList.remove('is-loading');
          if (btn.dataset.origHtml !== undefined) {
            btn.innerHTML = btn.dataset.origHtml;
            delete btn.dataset.origHtml;
          }
        }
      },
      async api(url, opts = {}) {
        const headers = Object.assign({
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': this.csrf,
          'X-Requested-With': 'XMLHttpRequest'
        }, opts.headers || {});
        const res = await fetch('/api' + url, Object.assign({}, opts, { headers, credentials: 'same-origin' }));
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw Object.assign(new Error(data.error || data.message || 'Request failed'), { data, status: res.status });
        return data;
      },
      hideSplash(delay = 450) {
        const el = document.getElementById('whSplash');
        if (!el) return;
        setTimeout(() => {
          el.classList.remove('is-on');
          el.setAttribute('aria-hidden', 'true');
        }, delay);
      },
      toast(message, type = 'ok', duration = 5000) {
        const wrap = document.getElementById('whToastWrap');
        if (!wrap) return;
        wrap.innerHTML = '';
        if (this._toastTimer) clearTimeout(this._toastTimer);
        const el = document.createElement('div');
        const kind = type === 'error' ? 'error' : (type === 'info' ? 'info' : 'success');
        el.className = 'notification-banner ' + kind;
        el.innerHTML = '<span></span><button type="button" aria-label="Close">&times;</button>';
        el.querySelector('span').textContent = message;
        el.querySelector('button').onclick = () => el.remove();
        wrap.appendChild(el);
        this._toastTimer = setTimeout(() => {
          el.classList.add('hide');
          setTimeout(() => el.remove(), 280);
        }, duration);
      },
      initAudioUnlock() {
        if (this._audioUnlocked) return;
        const unlock = () => {
          try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (Ctx) {
              this._audioCtx = this._audioCtx || new Ctx();
              if (this._audioCtx.state === 'suspended') this._audioCtx.resume().catch(() => {});
              const buffer = this._audioCtx.createBuffer(1, 1, 22050);
              const source = this._audioCtx.createBufferSource();
              source.buffer = buffer;
              source.connect(this._audioCtx.destination);
              source.start(0);
            }
          } catch (_) {}
          this._audioUnlocked = true;
          window.removeEventListener('pointerdown', unlock);
          window.removeEventListener('keydown', unlock);
          window.removeEventListener('touchstart', unlock);
        };
        window.addEventListener('pointerdown', unlock, { passive: true });
        window.addEventListener('keydown', unlock);
        window.addEventListener('touchstart', unlock, { passive: true });
      },
      playNotificationSound(customUrl) {
        const now = Date.now();
        if (now - this._lastSoundAt < 1800) return false;
        this._lastSoundAt = now;
        const url = customUrl || this.notificationSoundUrl;
        const playSynth = () => {
          try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            this._audioCtx = this._audioCtx || new Ctx();
            if (this._audioCtx.state === 'suspended') this._audioCtx.resume().catch(() => {});
            const ctx = this._audioCtx;
            const tone = (freq, start, dur) => {
              const osc = ctx.createOscillator();
              const gain = ctx.createGain();
              osc.connect(gain); gain.connect(ctx.destination);
              osc.type = 'sine';
              osc.frequency.setValueAtTime(freq, start);
              gain.gain.setValueAtTime(0.12, start);
              gain.gain.exponentialRampToValueAtTime(0.001, start + dur);
              osc.start(start); osc.stop(start + dur);
            };
            const t0 = ctx.currentTime;
            tone(523.25, t0, 0.12);
            tone(659.25, t0 + 0.08, 0.25);
          } catch (_) {}
        };
        try {
          if (url) {
            const clean = String(url).replace(/^data:video\/[^;]+;/, 'data:audio/mpeg;');
            const audio = new Audio(clean);
            audio.play().catch(() => playSynth());
            return true;
          }
          playSynth();
          return true;
        } catch (_) {
          playSynth();
          return true;
        }
      },
      showGetAppModal() {
        const fieldsHtml = `
          <div style="text-align:left;padding:.2rem 0;">
            <div style="background:rgba(62,224,178,0.08);border:1px solid rgba(62,224,178,0.25);border-radius:12px;padding:1rem;margin-bottom:1rem;">
              <h4 style="color:#3ee0b2;margin:0 0 .4rem 0;display:flex;align-items:center;gap:.5rem;font-size:1rem;">
                <i class="fa-brands fa-android" style="font-size:1.3rem;"></i> Android App — Winning Heaven
              </h4>
              <p style="color:#cbd5e1;font-size:0.85rem;margin:0 0 .8rem 0;line-height:1.4;">
                Download official Android APK. Auto-syncs live updates with zero reinstallations needed.
              </p>
              <a href="/downloads/WinningHeaven.apk" download class="wh-cta" style="display:inline-flex;align-items:center;gap:.5rem;text-decoration:none;padding:.6rem 1.2rem;font-size:.9rem;border-radius:8px;font-weight:bold;">
                <i class="fa-solid fa-download"></i> Download WinningHeaven.apk
              </a>
            </div>
            <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:1rem;">
              <h4 style="color:#e2e8f0;margin:0 0 .4rem 0;display:flex;align-items:center;gap:.5rem;font-size:1rem;">
                <i class="fa-brands fa-apple" style="font-size:1.3rem;"></i> iPhone / iOS App Setup
              </h4>
              <ol style="color:#94a3b8;font-size:0.85rem;margin:0;padding-left:1.2rem;line-height:1.6;">
                <li>Open <strong>winningheaven.com</strong> in Safari.</li>
                <li>Tap the <strong>Share</strong> button at bottom.</li>
                <li>Tap <strong>Add to Home Screen</strong>.</li>
              </ol>
            </div>
          </div>
        `;
        return this._openModal({
          title: 'Download Winning Heaven App',
          text: '',
          fieldsHtml,
          actions: [{ label: 'Close', primary: true, onClick: () => this._closeModal(true) }]
        });
      },
      initDesktopNotifications() {
        this.initAudioUnlock();
        if ('serviceWorker' in navigator) {
          navigator.serviceWorker.register('/sw.js').then((reg) => {
            if ('Notification' in window && Notification.permission === 'default') {
              Notification.requestPermission().then((permission) => {
                if (permission === 'granted' && reg.pushManager) {
                  reg.pushManager.subscribe({ userVisibleOnly: true }).then((sub) => {
                    fetch('/api/push/subscribe', {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                      body: JSON.stringify({ endpoint: sub.endpoint, subscription: sub.toJSON() })
                    }).catch(() => {});
                  }).catch(() => {});
                }
              }).catch(() => {});
            }
          }).catch(() => {});
        }
        const clear = () => {
          this._unseen = 0;
          if (this._titleFlashTimer) { clearInterval(this._titleFlashTimer); this._titleFlashTimer = null; }
          if (this._originalTitle !== null) document.title = this._originalTitle;
          if (this._originalFavicon) {
            const link = document.querySelector("link[rel~='icon']");
            if (link) link.href = this._originalFavicon;
          }
        };
        window.addEventListener('focus', () => { if (document.visibilityState === 'visible') clear(); });
        document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible' && document.hasFocus()) clear(); });
        if ('Notification' in window && Notification.permission === 'default') {
          try { Notification.requestPermission().catch(() => {}); } catch (_) {}
        }
      },
      notifyStaffActivity(title, body, count, url) {
        if (document.visibilityState === 'visible' && document.hasFocus()) return;
        this._unseen = Math.max(this._unseen, count || 1);
        if (this._originalTitle === null) this._originalTitle = document.title;
        if (!this._titleFlashTimer) {
          let toggle = false;
          this._titleFlashTimer = setInterval(() => {
            document.title = toggle ? this._originalTitle : ('🔔 (' + this._unseen + ') New request!');
            toggle = !toggle;
          }, 1000);
        }
        try {
          let link = document.querySelector("link[rel~='icon']");
          if (!link) { link = document.createElement('link'); link.rel = 'icon'; document.head.appendChild(link); }
          if (this._originalFavicon === null) this._originalFavicon = link.getAttribute('href') || '/brand/logo.png';
          const canvas = document.createElement('canvas');
          canvas.width = 64; canvas.height = 64;
          const ctx = canvas.getContext('2d');
          if (ctx) {
            ctx.fillStyle = '#07131f'; ctx.fillRect(0, 0, 64, 64);
            ctx.fillStyle = '#3ee0b2'; ctx.font = 'bold 34px sans-serif';
            ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.fillText('W', 26, 34);
            ctx.beginPath(); ctx.arc(46, 18, 17, 0, 2 * Math.PI); ctx.fillStyle = '#f43f5e'; ctx.fill();
            ctx.fillStyle = '#fff'; ctx.font = 'bold 22px sans-serif';
            ctx.fillText(this._unseen > 9 ? '9+' : String(this._unseen), 46, 19);
            link.href = canvas.toDataURL('image/png');
          }
        } catch (_) {}
        try {
          if (!('Notification' in window) || Notification.permission !== 'granted') return;
          const targetUrl = url || (typeof window !== 'undefined' ? (window.location.pathname + window.location.search) : '/admin') || '/admin';
          const opts = { body: body || 'New activity in HQ', tag: 'wh-staff-alert', renotify: true, data: { url: targetUrl } };
          if (navigator.serviceWorker) {
            navigator.serviceWorker.getRegistration().then((reg) => {
              if (reg && reg.showNotification) reg.showNotification(title || 'Winning Heaven', opts);
              else new Notification(title || 'Winning Heaven', opts);
            }).catch(() => new Notification(title || 'Winning Heaven', opts));
          } else {
            new Notification(title || 'Winning Heaven', opts);
          }
        } catch (_) {}
      },
      _modalResolve: null,
      _closeModal(result) {
        const bd = document.getElementById('whUiBackdrop');
        if (bd) {
          bd.classList.remove('is-on');
          bd.setAttribute('aria-hidden', 'true');
        }
        const r = this._modalResolve;
        this._modalResolve = null;
        if (r) r(result);
      },
      _openModal({ title, text, fieldsHtml, actions }) {
        return new Promise((resolve) => {
          this._modalResolve = resolve;
          document.getElementById('whUiTitle').textContent = title || 'Notice';
          document.getElementById('whUiText').textContent = text || '';
          document.getElementById('whUiText').style.display = text ? 'block' : 'none';
          const fields = document.getElementById('whUiFields');
          if (fieldsHtml) {
            fields.style.display = 'grid';
            fields.innerHTML = fieldsHtml;
          } else {
            fields.style.display = 'none';
            fields.innerHTML = '';
          }
          const actionsEl = document.getElementById('whUiActions');
          actionsEl.innerHTML = '';
          (actions || []).forEach((a) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = a.primary ? 'wh-ui-btn-ok' : 'wh-ui-btn-ghost';
            b.textContent = a.label;
            b.onclick = () => a.onClick();
            actionsEl.appendChild(b);
          });
          const bd = document.getElementById('whUiBackdrop');
          bd.classList.add('is-on');
          bd.setAttribute('aria-hidden', 'false');
          const first = fields.querySelector('input, textarea, select');
          if (first) setTimeout(() => first.focus(), 40);
        });
      },
      alert(message, title = 'Notice') {
        return this._openModal({
          title,
          text: message,
          actions: [{ label: 'OK', primary: true, onClick: () => this._closeModal(true) }]
        });
      },
      confirm(message, title = 'Confirm') {
        return this._openModal({
          title,
          text: message,
          actions: [
            { label: 'Cancel', onClick: () => this._closeModal(false) },
            { label: 'Confirm', primary: true, onClick: () => this._closeModal(true) }
          ]
        });
      },
      prompt(message, defaultValue = '', title = 'Enter value') {
        const id = 'whPromptInput';
        return this._openModal({
          title,
          text: message,
          fieldsHtml: '<label class="wh-field"><span class="sr-only">Value</span><input id="' + id + '" value="' + String(defaultValue).replace(/"/g, '&quot;') + '" /></label>',
          actions: [
            { label: 'Cancel', onClick: () => this._closeModal(null) },
            { label: 'OK', primary: true, onClick: () => this._closeModal(document.getElementById(id)?.value ?? '') }
          ]
        });
      },
      promptFields(title, text, fieldDefs) {
        const html = fieldDefs.map((f) => {
          const type = f.type || 'text';
          if (type === 'textarea') {
            return '<label class="wh-field"><span>' + f.label + '</span><textarea id="' + f.id + '" rows="3" placeholder="' + (f.placeholder || '') + '">' + (f.value || '') + '</textarea></label>';
          }
          return '<label class="wh-field"><span>' + f.label + '</span><input id="' + f.id + '" type="' + type + '" value="' + String(f.value || '').replace(/"/g, '&quot;') + '" placeholder="' + (f.placeholder || '') + '" /></label>';
        }).join('');
        return this._openModal({
          title,
          text,
          fieldsHtml: html,
          actions: [
            { label: 'Cancel', onClick: () => this._closeModal(null) },
            {
              label: 'Submit',
              primary: true,
              onClick: () => {
                const out = {};
                fieldDefs.forEach((f) => { out[f.id] = document.getElementById(f.id)?.value ?? ''; });
                this._closeModal(out);
              }
            }
          ]
        });
      },
      _chatPoll: null,
      _chatAttachment: '',
      _chatEmail: '',
      openSupportChat(opts = {}) {
        const chat = document.getElementById('whChat');
        if (!chat) return;
        this._chatEmail = opts.email || '';
        document.getElementById('whChatSub').textContent = this._chatEmail || 'Winning Heaven';
        chat.classList.add('is-on');
        chat.setAttribute('aria-hidden', 'false');
        this._chatAttachment = '';
        const prev = document.getElementById('whChatPreview');
        if (prev) prev.style.display = 'none';
        this.refreshSupportChat();
        if (this._chatPoll) clearInterval(this._chatPoll);
        this._chatPoll = setInterval(() => this.refreshSupportChat(), 3000);
      },
      closeSupportChat() {
        const chat = document.getElementById('whChat');
        if (chat) {
          chat.classList.remove('is-on');
          chat.setAttribute('aria-hidden', 'true');
        }
        if (this._chatPoll) { clearInterval(this._chatPoll); this._chatPoll = null; }
      },
      async refreshSupportChat() {
        const body = document.getElementById('whChatBody');
        if (!body) return;
        try {
          const url = this._chatEmail ? ('/support?email=' + encodeURIComponent(this._chatEmail)) : '/support';
          const d = await this.api(url);
          const items = (d.items || []).slice().reverse();
          body.innerHTML = '';
          if (!items.length) {
            body.innerHTML = '<p class="wh-chat__empty">No messages yet. Say hello!</p>';
            return;
          }
          items.forEach((m) => {
            const mine = (m.sender_type || '') === 'player' || (m.sender_email && m.sender_email === (window.__WH_USER_EMAIL || ''));
            const row = document.createElement('div');
            row.className = 'wh-chat__msg' + (mine ? ' is-mine' : ' is-theirs');
            const meta = document.createElement('div');
            meta.className = 'wh-chat__meta';
            meta.textContent = (m.sender_type || 'player') + ' · ' + (m.created_at || '');
            row.appendChild(meta);
            if (m.message) {
              const msgText = String(m.message || '').trim();
              const hasImg = !!m.attachment;
              // Don't show placeholder word when image is attached
              if (!(hasImg && /^attachment$/i.test(msgText))) {
                const text = document.createElement('div');
                text.textContent = msgText;
                row.appendChild(text);
              }
            }
            if (m.attachment) {
              const img = document.createElement('img');
              img.src = m.attachment;
              img.alt = 'Attachment';
              img.className = 'wh-chat__att';
              img.onclick = () => {
                const lb = document.getElementById('whChatLightbox');
                const lbImg = document.getElementById('whChatLightboxImg');
                if (lbImg) lbImg.src = m.attachment;
                if (lb) lb.classList.add('is-on');
              };
              row.appendChild(img);
            }
            body.appendChild(row);
          });
          body.scrollTop = body.scrollHeight;
        } catch (_) {}
      },
      async sendSupportChat(message) {
        const payload = {
          message: message || (this._chatAttachment ? 'Attachment' : ''),
          attachment: this._chatAttachment || undefined,
          sender_type: 'player'
        };
        if (this._chatEmail) payload.user_email = this._chatEmail;
        await this.api('/support', { method: 'POST', body: JSON.stringify(payload) });
        this._chatAttachment = '';
        const prev = document.getElementById('whChatPreview');
        if (prev) prev.style.display = 'none';
        document.getElementById('whChatInput').value = '';
        await this.refreshSupportChat();
      }
    };
    document.getElementById('whUiBackdrop')?.addEventListener('click', (e) => {
      if (e.target.id === 'whUiBackdrop') WH._closeModal(null);
    });
    document.getElementById('whChatClose')?.addEventListener('click', () => WH.closeSupportChat());
    document.getElementById('whChatFile')?.addEventListener('change', (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      if (file.size > 2 * 1024 * 1024) { WH.toast('Image must be under 2MB', 'error'); e.target.value = ''; return; }
      const reader = new FileReader();
      reader.onload = () => {
        WH._chatAttachment = reader.result;
        const prev = document.getElementById('whChatPreview');
        const img = document.getElementById('whChatPreviewImg');
        if (img) img.src = reader.result;
        if (prev) prev.style.display = 'flex';
      };
      reader.readAsDataURL(file);
      e.target.value = '';
    });
    document.getElementById('whChatPreviewClear')?.addEventListener('click', () => {
      WH._chatAttachment = '';
      const prev = document.getElementById('whChatPreview');
      if (prev) prev.style.display = 'none';
    });
    document.getElementById('whChatForm')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const input = document.getElementById('whChatInput');
      const msg = (input?.value || '').trim();
      if (!msg && !WH._chatAttachment) return;
      try {
        await WH.sendSupportChat(msg);
      } catch (err) {
        WH.toast(err.message || 'Could not send', 'error');
      }
    });
    WH.initAudioUnlock();
    window.addEventListener('load', () => WH.hideSplash(500));
    setTimeout(() => WH.hideSplash(0), 2500);
  </script>
  @stack('scripts')
</body>
</html>

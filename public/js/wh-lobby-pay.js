/**
 * Winning Heaven lobby deposit/withdraw — Jackpot flow parity (WH branding).
 * Expects globals set by lobby blade: GATEWAYS, GAME_ACCOUNTS, ACCOUNT_REQS, TXS,
 * MIN_DEP, MIN_WD, FP_*, USER_EMAIL, WD_REQUIRE_GAME, WD_REQUIRE_TAG_QR
 */
(function (global) {
  const STORAGE_KEY = 'wh_pending_deposit';
  const DEPOSIT_CODE_TTL_MS = 10 * 60 * 1000;
  const CODE_WORDS = [
    'Book','Car','Rocky','Apple','Tiger','Lion','Sky','Tree','Star','Moon','Sun',
    'River','Bird','Fish','Ring','King','Queen','Royal','Club','Jack','Gold','Card',
    'Play','Game','Win','Luck','Cash','Ace','Diamond','Heart','Spade','Crown','Ruby'
  ];

  function readPendingDeposit(email) {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return null;
      const data = JSON.parse(raw);
      if (!data?.noteCode || !data?.expiresAt) { localStorage.removeItem(STORAGE_KEY); return null; }
      if (Date.now() >= Number(data.expiresAt)) { localStorage.removeItem(STORAGE_KEY); return null; }
      if (email && data.userEmail && data.userEmail !== String(email).trim().toLowerCase()) return null;
      return data;
    } catch { return null; }
  }
  function writePendingDeposit(payload) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({
        userEmail: String(payload.userEmail || '').trim().toLowerCase(),
        gameTitle: payload.gameTitle,
        amount: payload.amount,
        gateway: payload.gateway,
        noteCode: payload.noteCode,
        expiresAt: payload.expiresAt
      }));
    } catch (_) {}
  }
  function clearPendingDeposit() {
    try { localStorage.removeItem(STORAGE_KEY); } catch (_) {}
  }
  function generateDepositNoteCode() {
    const w = CODE_WORDS[Math.floor(Math.random() * CODE_WORDS.length)];
    return w + String(Math.floor(100 + Math.random() * 900));
  }
  function remainingSeconds(expiresAt) {
    return Math.max(0, Math.ceil((Number(expiresAt) - Date.now()) / 1000));
  }
  function isLinkPayGateway(g) {
    const theme = String(g?.theme || '').toLowerCase();
    return !!(g?.redirect_url) || theme === 'cashapp' || theme === 'stripe';
  }
  function buildRedirectUrl(gw, amount, code) {
    const raw = String(gw?.redirect_url || '').trim();
    if (!raw) return '';
    return raw
      .replace(/\{amount\}/gi, Number(amount).toFixed(2))
      .replace(/\{code\}/gi, String(code || ''))
      .replace(/\{tag\}/gi, String(gw?.tag || ''));
  }
  function fmtTime(sec) {
    const m = Math.floor(sec / 60);
    const s = Math.max(0, sec % 60);
    return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
  }
  function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
  }

  global.WHPendingDeposit = {
    read: readPendingDeposit,
    write: writePendingDeposit,
    clear: clearPendingDeposit,
    generateCode: generateDepositNoteCode,
    remainingSeconds,
    isLinkPayGateway,
    buildRedirectUrl,
    fmtTime,
    esc,
    TTL: DEPOSIT_CODE_TTL_MS
  };
})(window);

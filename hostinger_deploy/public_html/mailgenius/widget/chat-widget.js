/*!
 * MailGenius Pro — Chat Widget
 * Embed: <script src="URL/widget/chat-widget.js" data-key="API_KEY"></script>
 */
(function() {
  'use strict';

  const script  = document.currentScript;
  const API_KEY = script?.dataset.key ?? '';
  const BASE    = script?.src.replace('/widget/chat-widget.js', '') ?? '';

  let session   = null;   // { session_id, token }
  let lastMsgId = 0;
  let isOpen    = false;
  let pollTimer = null;
  let config    = {};

  // ── Fetch config ───────────────────────────────────────────
  async function loadConfig() {
    try {
      const res = await fetch(`${BASE}/api/chat.php?action=widget_config&key=${encodeURIComponent(API_KEY)}`);
      config    = await res.json();
      if (config.error) { console.warn('MailGenius widget: ' + config.error); return; }
      injectStyles();
      buildWidget();
    } catch(e) {
      console.warn('MailGenius widget error:', e);
    }
  }

  // ── Inject styles ──────────────────────────────────────────
  function injectStyles() {
    const primary = config.primary_color || '#4f46e5';
    const pos     = config.position === 'bottom-left' ? 'left:20px' : 'right:20px';

    const css = `
      #mg-chat-bubble { position:fixed;bottom:20px;${pos};z-index:999999;cursor:pointer; }
      #mg-chat-btn { width:56px;height:56px;border-radius:50%;background:${primary};border:none;color:#fff;font-size:24px;box-shadow:0 4px 20px rgba(0,0,0,.3);display:flex;align-items:center;justify-content:center;transition:transform .2s,box-shadow .2s; }
      #mg-chat-btn:hover { transform:scale(1.08);box-shadow:0 6px 28px rgba(0,0,0,.4); }
      #mg-chat-badge { position:absolute;top:-3px;right:-3px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;display:none; }
      #mg-chat-window { position:fixed;bottom:88px;${pos};z-index:999998;width:350px;max-height:520px;background:#fff;border-radius:16px;box-shadow:0 8px 40px rgba(0,0,0,.2);display:flex;flex-direction:column;overflow:hidden;transform:scale(0) translateY(20px);transform-origin:bottom right;transition:transform .25s cubic-bezier(.34,1.56,.64,1),opacity .25s;opacity:0;pointer-events:none; }
      #mg-chat-window.open { transform:scale(1) translateY(0);opacity:1;pointer-events:all; }
      #mg-chat-header { background:${primary};color:#fff;padding:16px;display:flex;align-items:center;gap:12px; }
      #mg-chat-header .mg-avatar { width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:18px; }
      #mg-chat-header h4 { margin:0;font-size:15px;font-weight:700;font-family:system-ui,sans-serif; }
      #mg-chat-header p  { margin:0;font-size:12px;opacity:.85;font-family:system-ui,sans-serif; }
      #mg-chat-header .mg-close { margin-left:auto;background:none;border:none;color:#fff;font-size:20px;cursor:pointer;opacity:.8;padding:0; }
      #mg-chat-header .mg-close:hover { opacity:1; }
      #mg-chat-form { padding:16px;background:#f8fafc;border-bottom:1px solid #e2e8f0; }
      #mg-chat-form input { width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:system-ui,sans-serif;outline:none;margin-bottom:8px;box-sizing:border-box; }
      #mg-chat-form input:focus { border-color:${primary}; }
      #mg-chat-form button { width:100%;padding:9px;background:${primary};color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:system-ui,sans-serif; }
      #mg-messages { flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;font-family:system-ui,sans-serif; }
      #mg-messages::-webkit-scrollbar { width:4px; }
      #mg-messages::-webkit-scrollbar-thumb { background:#cbd5e1;border-radius:99px; }
      .mg-bubble { max-width:80%;padding:9px 13px;border-radius:14px;font-size:13.5px;line-height:1.5; }
      .mg-bubble.visitor { background:${primary};color:#fff;align-self:flex-end;border-bottom-right-radius:4px; }
      .mg-bubble.agent,.mg-bubble.bot { background:#f1f5f9;color:#1e293b;align-self:flex-start;border-bottom-left-radius:4px; }
      .mg-bubble.system { background:transparent;color:#94a3b8;font-size:11px;text-align:center;align-self:center;border:1px dashed #e2e8f0; }
      .mg-bubble-meta { font-size:10px;opacity:.6;margin-top:4px; }
      .mg-qr-wrap { display:flex;flex-wrap:wrap;gap:6px;margin-top:6px; }
      .mg-qr-btn { padding:5px 12px;border-radius:20px;border:1.5px solid ${primary};color:${primary};background:#fff;font-size:12px;cursor:pointer;font-family:system-ui,sans-serif; }
      .mg-qr-btn:hover { background:${primary};color:#fff; }
      #mg-input-area { padding:10px 12px;border-top:1px solid #e2e8f0;display:flex;gap:8px;align-items:flex-end; }
      #mg-input-area textarea { flex:1;border:1.5px solid #e2e8f0;border-radius:10px;padding:8px 12px;font-size:13px;resize:none;max-height:80px;outline:none;font-family:system-ui,sans-serif; }
      #mg-input-area textarea:focus { border-color:${primary}; }
      #mg-send-btn { background:${primary};border:none;color:#fff;width:38px;height:38px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
      #mg-send-btn:hover { filter:brightness(1.1); }
      #mg-typing { font-size:11px;color:#94a3b8;padding:4px 16px;display:none; }
    `;
    const style = document.createElement('style');
    style.textContent = css;
    document.head.appendChild(style);
  }

  // ── Build widget DOM ───────────────────────────────────────
  function buildWidget() {
    const root = document.createElement('div');
    root.innerHTML = `
      <div id="mg-chat-bubble">
        <button id="mg-chat-btn" aria-label="Abrir chat">💬</button>
        <div id="mg-chat-badge">0</div>
      </div>
      <div id="mg-chat-window" role="dialog" aria-label="Chat de soporte">
        <div id="mg-chat-header">
          <div class="mg-avatar">💬</div>
          <div>
            <h4>Soporte en vivo</h4>
            <p id="mg-status-text">En línea · Responde en minutos</p>
          </div>
          <button class="mg-close" id="mg-close-btn" aria-label="Cerrar">×</button>
        </div>
        <div id="mg-chat-form">
          <input type="text"  id="mg-visitor-name"  placeholder="Tu nombre (opcional)">
          <input type="email" id="mg-visitor-email" placeholder="Tu email (opcional)">
          <button id="mg-start-btn">Iniciar conversación</button>
        </div>
        <div id="mg-messages" style="display:none"></div>
        <div id="mg-typing">Agente está escribiendo...</div>
        <div id="mg-input-area" style="display:none">
          <textarea id="mg-message-input" placeholder="Escribe un mensaje..." rows="1"></textarea>
          <button id="mg-send-btn">➤</button>
        </div>
      </div>
    `;
    document.body.appendChild(root);
    bindEvents();
  }

  function bindEvents() {
    document.getElementById('mg-chat-btn')?.addEventListener('click', toggleWidget);
    document.getElementById('mg-close-btn')?.addEventListener('click', toggleWidget);
    document.getElementById('mg-start-btn')?.addEventListener('click', startSession);
    document.getElementById('mg-send-btn')?.addEventListener('click', sendMessage);
    document.getElementById('mg-message-input')?.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });
    document.getElementById('mg-message-input')?.addEventListener('input', function() {
      this.style.height = 'auto';
      this.style.height = Math.min(this.scrollHeight, 80) + 'px';
    });
  }

  function toggleWidget() {
    isOpen = !isOpen;
    const win  = document.getElementById('mg-chat-window');
    const btn  = document.getElementById('mg-chat-btn');
    win.classList.toggle('open', isOpen);
    btn.textContent = isOpen ? '✕' : '💬';
    if (isOpen) clearBadge();
  }

  // ── Session ────────────────────────────────────────────────
  async function startSession() {
    const name  = document.getElementById('mg-visitor-name')?.value.trim();
    const email = document.getElementById('mg-visitor-email')?.value.trim();

    document.getElementById('mg-start-btn').textContent = 'Conectando...';
    document.getElementById('mg-start-btn').disabled    = true;

    try {
      const fd = new FormData();
      fd.append('action',   'create_session');
      fd.append('name',     name  || 'Visitante');
      fd.append('email',    email || '');
      fd.append('page_url', window.location.href);
      fd.append('referrer', document.referrer);

      const res  = await fetch(`${BASE}/api/chat.php`, { method: 'POST', body: fd });
      const data = await res.json();

      if (data.session_id && data.token) {
        session = data;
        sessionStorage.setItem('mg_session', JSON.stringify(data));
        showChatUI();
        startPolling();
        loadMessages();
      }
    } catch(e) {
      console.warn('Error iniciando sesión:', e);
      document.getElementById('mg-start-btn').textContent = 'Reintentar';
      document.getElementById('mg-start-btn').disabled    = false;
    }
  }

  function showChatUI() {
    document.getElementById('mg-chat-form').style.display   = 'none';
    document.getElementById('mg-messages').style.display    = 'flex';
    document.getElementById('mg-input-area').style.display  = 'flex';
  }

  // ── Messages ───────────────────────────────────────────────
  async function loadMessages() {
    if (!session) return;
    const res  = await fetch(`${BASE}/api/chat.php?action=get_messages_public&token=${session.token}&since=${lastMsgId}`);
    const data = await res.json();
    if (data.messages?.length) {
      data.messages.forEach(m => appendMsg(m));
      lastMsgId = data.messages[data.messages.length - 1].id;
      scrollToBottom();
      if (!isOpen) showBadge(data.messages.filter(m => m.sender_type !== 'visitor').length);
    }
  }

  function appendMsg(m) {
    const list = document.getElementById('mg-messages');
    if (!list) return;
    const meta = (() => { try { return JSON.parse(m.metadata || '{}'); } catch(e) { return {}; } })();

    const wrap = document.createElement('div');
    wrap.style.cssText = `display:flex;flex-direction:column;align-items:${m.sender_type === 'visitor' ? 'flex-end' : 'flex-start'}`;

    const qrHtml = (meta.quick_replies || []).map(q =>
      `<button class="mg-qr-btn" onclick="window.__mgSendQr('${escJ(q.value)}')">${escH(q.text)}</button>`
    ).join('');

    wrap.innerHTML = `
      <div class="mg-bubble ${m.sender_type}">
        ${escH(m.content).replace(/\n/g,'<br>')}
        ${qrHtml ? `<div class="mg-qr-wrap">${qrHtml}</div>` : ''}
        <div class="mg-bubble-meta">${m.created_at?.substr(11,5) ?? ''}</div>
      </div>
    `;
    list.appendChild(wrap);
  }

  window.__mgSendQr = (val) => {
    document.getElementById('mg-message-input').value = val;
    sendMessage();
  };

  async function sendMessage() {
    const input = document.getElementById('mg-message-input');
    const msg   = input?.value.trim();
    if (!msg || !session) return;
    input.value = '';
    input.style.height = 'auto';

    // Optimistic render
    appendMsg({ sender_type: 'visitor', content: msg, created_at: new Date().toISOString(), metadata: null });
    scrollToBottom();

    const fd = new FormData();
    fd.append('action',   'send_visitor');
    fd.append('token',    session.token);
    fd.append('content',  msg);
    const res  = await fetch(`${BASE}/api/chat.php`, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.id) lastMsgId = data.id;
    if (data.bot_reply) {
      appendMsg({ ...data.bot_reply, sender_type: 'bot' });
      scrollToBottom();
    }
  }

  function scrollToBottom() {
    const list = document.getElementById('mg-messages');
    if (list) list.scrollTop = list.scrollHeight;
  }

  function showBadge(n) {
    const badge = document.getElementById('mg-chat-badge');
    if (!badge || n <= 0) return;
    badge.textContent = n > 9 ? '9+' : n;
    badge.style.display = 'flex';
  }
  function clearBadge() {
    const badge = document.getElementById('mg-chat-badge');
    if (badge) badge.style.display = 'none';
  }

  function startPolling() {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(loadMessages, 3000);
  }

  // Resume session from sessionStorage
  const saved = (() => { try { return JSON.parse(sessionStorage.getItem('mg_session') || 'null'); } catch(e) { return null; } })();
  if (saved) {
    session = saved;
  }

  function escH(s) { return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }
  function escJ(s) { return String(s??'').replace(/'/g,"\\'"); }

  // ── Boot ──────────────────────────────────────────────────
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadConfig);
  } else {
    loadConfig();
  }

})();

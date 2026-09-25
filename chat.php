<?php
require_once __DIR__ . '/config/config.php';
auth_required();

$pageTitle  = 'Chat en Vivo';
$activePage = 'chat';
$breadcrumb = 'Chat en Vivo';

$chatMgr  = new ChatManager();
$sessions = $chatMgr->getSessions('', 1);
$stats    = $chatMgr->getStats();

$activeSessionId = isset($_GET['session']) ? (int)$_GET['session'] : null;
$activeSession   = $activeSessionId ? $chatMgr->getSession($activeSessionId) : null;

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-4">
  <div>
    <h1 class="page-title"><i class="bi bi-chat-dots me-2 text-accent"></i>Chat en Vivo</h1>
    <p class="page-subtitle">
      <span class="badge badge-success me-1"><?= $stats['active'] ?> activos</span>
      <span class="badge badge-warning me-1"><?= $stats['waiting'] ?> esperando</span>
      <span class="badge badge-gray"><?= $stats['closed_today'] ?> cerrados hoy</span>
    </p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= APP_URL ?>/chat_bot.php" class="btn btn-secondary">
      <i class="bi bi-robot me-2"></i>Bot
    </a>
    <a href="<?= APP_URL ?>/chat_widget.php" class="btn btn-secondary">
      <i class="bi bi-window-stack me-2"></i>Widget Web
    </a>
  </div>
</div>

<div class="chat-layout">
  <!-- Sessions list -->
  <div class="chat-list">
    <!-- Search -->
    <div class="p-3 border-bottom" style="border-color:var(--border)">
      <div class="search-bar">
        <i class="bi bi-search search-icon"></i>
        <input type="text" class="form-control" id="chatSearch" placeholder="Buscar conversación...">
      </div>
      <div class="d-flex gap-1 mt-2">
        <button class="btn btn-sm btn-secondary active" data-filter="all">Todos</button>
        <button class="btn btn-sm btn-secondary" data-filter="waiting" style="color:var(--warning)">
          <i class="bi bi-clock"></i> <?= $stats['waiting'] ?>
        </button>
        <button class="btn btn-sm btn-secondary" data-filter="active" style="color:var(--success)">
          <i class="bi bi-circle-fill" style="font-size:8px"></i> Activos
        </button>
        <button class="btn btn-sm btn-secondary" data-filter="closed">Cerrados</button>
      </div>
    </div>

    <!-- Session items -->
    <div id="sessionsList" style="overflow-y:auto;flex:1">
      <?php foreach ($sessions['data'] as $session): ?>
        <a href="?session=<?= $session['id'] ?>"
           class="session-item d-flex align-items-center gap-3 p-3 border-bottom text-decoration-none <?= $activeSessionId === $session['id'] ? 'active' : '' ?>"
           style="border-color:var(--border)!important;color:var(--text);transition:background .15s;<?= $activeSessionId === $session['id'] ? 'background:rgba(79,70,229,.1)' : '' ?>"
           data-status="<?= $session['status'] ?>">
          <div class="position-relative">
            <div class="avatar-sm"><?= strtoupper(substr($session['visitor_name'] ?? 'V', 0, 1)) ?></div>
            <?php if ($session['status'] === 'active'): ?>
              <div class="status-dot status-online" style="position:absolute;bottom:0;right:0;border:2px solid var(--bg-card)"></div>
            <?php elseif ($session['status'] === 'waiting'): ?>
              <div class="status-dot" style="background:var(--warning);position:absolute;bottom:0;right:0;border:2px solid var(--bg-card)"></div>
            <?php endif; ?>
          </div>
          <div class="flex-grow-1 min-w-0">
            <div class="d-flex justify-content-between align-items-start">
              <span class="fw-600 small"><?= e($session['visitor_name'] ?? 'Visitante') ?></span>
              <span class="text-xs text-muted"><?= time_ago($session['started_at']) ?></span>
            </div>
            <div class="text-xs text-muted text-truncate"><?= e(substr($session['last_message'] ?? 'Sin mensajes', 0, 45)) ?></div>
            <?php if ($session['unread_count'] > 0): ?>
              <span class="badge nav-badge mt-1"><?= $session['unread_count'] ?></span>
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
      <?php if (empty($sessions['data'])): ?>
        <div class="empty-state">
          <i class="bi bi-chat-square-dots"></i>
          <p>Sin conversaciones</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Chat main -->
  <div class="chat-main">
    <?php if ($activeSession): ?>
      <!-- Chat header -->
      <div class="p-3 border-bottom d-flex align-items-center justify-content-between" style="border-color:var(--border)">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar-sm"><?= strtoupper(substr($activeSession['visitor_name'] ?? 'V', 0, 1)) ?></div>
          <div>
            <div class="fw-600"><?= e($activeSession['visitor_name'] ?? 'Visitante') ?></div>
            <div class="text-xs text-muted">
              <?php if ($activeSession['visitor_email']): ?>
                <i class="bi bi-envelope me-1"></i><?= e($activeSession['visitor_email']) ?>
              <?php endif; ?>
              <?php if ($activeSession['page_url']): ?>
                &nbsp;·&nbsp;<i class="bi bi-link me-1"></i><?= e($activeSession['page_url']) ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="d-flex gap-2">
          <div class="dropdown">
            <button class="btn btn-secondary btn-sm" data-bs-toggle="dropdown">
              <i class="bi bi-person-badge me-1"></i>Asignar
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php
                $agents = Database::fetchAll("SELECT id, name FROM users WHERE is_active = 1");
                foreach ($agents as $agent):
              ?>
                <li>
                  <a class="dropdown-item assign-agent"
                     href="#"
                     data-agent="<?= $agent['id'] ?>"
                     data-session="<?= $activeSession['id'] ?>">
                    <?= e($agent['name']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <button class="btn btn-secondary btn-sm" id="convertEmailBtn" data-session="<?= $activeSession['id'] ?>">
            <i class="bi bi-envelope me-1"></i>→ Email
          </button>
          <button class="btn btn-danger btn-sm" id="closeSessionBtn" data-session="<?= $activeSession['id'] ?>">
            <i class="bi bi-x-circle me-1"></i>Cerrar
          </button>
        </div>
      </div>

      <!-- Messages -->
      <div class="chat-messages scrollbar-thin" id="chatMessages">
        <?php foreach ($activeSession['messages'] as $msg): ?>
          <?php if ($msg['sender_type'] === 'system'): ?>
            <div class="chat-bubble system"><?= e($msg['content']) ?></div>
          <?php else: ?>
            <div class="d-flex flex-column <?= in_array($msg['sender_type'], ['agent']) ? 'align-items-end' : 'align-items-start' ?>">
              <div class="chat-bubble <?= $msg['sender_type'] ?>">
                <?php if ($msg['message_type'] === 'image' && $msg['attachment_path']): ?>
                  <img src="<?= e($msg['attachment_path']) ?>" style="max-width:200px;border-radius:8px">
                <?php elseif ($msg['message_type'] === 'file' && $msg['attachment_path']): ?>
                  <a href="<?= e($msg['attachment_path']) ?>" class="text-white" download>
                    <i class="bi bi-paperclip me-1"></i><?= e($msg['attachment_name'] ?? 'Archivo') ?>
                  </a>
                <?php else: ?>
                  <?= nl2br(e($msg['content'])) ?>
                <?php endif; ?>
                <?php
                  $metadata = json_decode($msg['metadata'] ?? '{}', true);
                  if (!empty($metadata['quick_replies'])):
                ?>
                  <div class="quick-replies mt-2">
                    <?php foreach ($metadata['quick_replies'] as $qr): ?>
                      <button class="quick-reply-btn" data-value="<?= e($qr['value']) ?>">
                        <?= e($qr['text']) ?>
                      </button>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
                <div class="chat-bubble-meta"><?= date('H:i', strtotime($msg['created_at'])) ?></div>
              </div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <!-- Canned responses bar -->
      <div id="cannedSuggestions" class="px-3 pt-2" style="display:none">
        <div class="d-flex gap-2 flex-wrap" id="cannedList"></div>
      </div>

      <!-- Input area -->
      <div class="chat-input-area">
        <div class="chat-input-bar">
          <div class="d-flex gap-1">
            <button class="btn btn-icon" id="attachChatBtn" title="Adjuntar archivo">
              <i class="bi bi-paperclip fs-5"></i>
            </button>
            <button class="btn btn-icon" id="cannedBtn" title="Respuestas rápidas">
              <i class="bi bi-lightning-charge fs-5"></i>
            </button>
          </div>
          <textarea id="chatInput" placeholder="Escribe un mensaje... (Enter para enviar, Shift+Enter para nueva línea)"
                    rows="1"></textarea>
          <input type="file" id="chatFileInput" hidden>
          <button class="btn btn-primary" id="sendChatBtn">
            <i class="bi bi-send-fill"></i>
          </button>
        </div>
        <div class="mt-1 d-flex gap-3 text-xs text-muted">
          <span id="typingIndicator" style="display:none"><i class="bi bi-three-dots"></i> Escribiendo...</span>
        </div>
      </div>

    <?php else: ?>
      <div class="empty-state" style="margin:auto">
        <i class="bi bi-chat-square-dots" style="font-size:64px;color:var(--border)"></i>
        <h5 class="mt-3">Selecciona una conversación</h5>
        <p>Elige una conversación de la lista para comenzar a responder</p>
        <?php if ($stats['waiting'] > 0): ?>
          <div class="alert" style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-radius:8px;color:var(--warning);font-size:13px;display:inline-block">
            <i class="bi bi-clock me-2"></i><?= $stats['waiting'] ?> visitante(s) esperando respuesta
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
const CURRENT_SESSION = <?= $activeSessionId ? $activeSessionId : 'null' ?>;
const CSRF = '<?= csrf_token() ?>';
let lastMsgId = <?= !empty($activeSession['messages']) ? end($activeSession['messages'])['id'] : 0 ?>;

// Auto-scroll
function scrollToBottom() {
  const msgs = document.getElementById('chatMessages');
  if (msgs) msgs.scrollTop = msgs.scrollHeight;
}
scrollToBottom();

// Poll for new messages
if (CURRENT_SESSION) {
  setInterval(async () => {
    const res = await fetch(`${APP_URL}/api/chat.php?action=messages&session=${CURRENT_SESSION}&since=${lastMsgId}`);
    const data = await res.json();
    if (data.messages && data.messages.length > 0) {
      data.messages.forEach(m => {
        appendMessage(m);
        lastMsgId = m.id;
      });
      scrollToBottom();
    }
  }, 2500);
}

function appendMessage(m) {
  const msgs = document.getElementById('chatMessages');
  if (!msgs) return;
  const wrap = document.createElement('div');
  wrap.className = `d-flex flex-column ${m.sender_type === 'agent' ? 'align-items-end' : 'align-items-start'}`;

  let inner = '';
  if (m.sender_type === 'system') {
    inner = `<div class="chat-bubble system">${escHtml(m.content)}</div>`;
  } else {
    const meta = m.metadata ? JSON.parse(m.metadata) : {};
    const qr   = (meta.quick_replies || []).map(q =>
      `<button class="quick-reply-btn" data-value="${escHtml(q.value)}">${escHtml(q.text)}</button>`
    ).join('');
    inner = `<div class="chat-bubble ${m.sender_type}">
      ${escHtml(m.content).replace(/\n/g,'<br>')}
      ${qr ? `<div class="quick-replies mt-2">${qr}</div>` : ''}
      <div class="chat-bubble-meta">${m.created_at.substr(11,5)}</div>
    </div>`;
    wrap.innerHTML = inner;
  }
  wrap.innerHTML = inner;
  msgs.appendChild(wrap);
}

function escHtml(s) {
  return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// Send message
document.getElementById('sendChatBtn')?.addEventListener('click', sendMessage);
document.getElementById('chatInput')?.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});

async function sendMessage() {
  const input = document.getElementById('chatInput');
  const msg   = input?.value.trim();
  if (!msg || !CURRENT_SESSION) return;
  input.value = '';
  input.style.height = 'auto';

  const fd = new FormData();
  fd.append('action',      'send');
  fd.append('session_id',  CURRENT_SESSION);
  fd.append('content',     msg);
  fd.append('sender_type', 'agent');
  fd.append('csrf_token',  CSRF);

  const res  = await fetch(`${APP_URL}/api/chat.php`, { method: 'POST', body: fd });
  const data = await res.json();
  if (data.id) {
    appendMessage({ id: data.id, sender_type: 'agent', content: msg, created_at: new Date().toISOString(), metadata: null });
    lastMsgId = data.id;
    scrollToBottom();
  }
}

// Auto-resize textarea
document.getElementById('chatInput')?.addEventListener('input', function() {
  this.style.height = 'auto';
  this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// Quick replies
document.addEventListener('click', async e => {
  if (e.target.classList.contains('quick-reply-btn')) {
    const val = e.target.dataset.value;
    document.getElementById('chatInput').value = val;
    sendMessage();
  }
});

// Assign agent
document.querySelectorAll('.assign-agent').forEach(el => {
  el.addEventListener('click', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('action',     'assign');
    fd.append('session_id', el.dataset.session);
    fd.append('agent_id',   el.dataset.agent);
    fd.append('csrf_token', CSRF);
    await fetch(`${APP_URL}/api/chat.php`, { method: 'POST', body: fd });
    location.reload();
  });
});

// Close session
document.getElementById('closeSessionBtn')?.addEventListener('click', async () => {
  const fd = new FormData();
  fd.append('action',     'close');
  fd.append('session_id', CURRENT_SESSION);
  fd.append('csrf_token', CSRF);
  await fetch(`${APP_URL}/api/chat.php`, { method: 'POST', body: fd });
  location.href = '<?= APP_URL ?>/chat.php';
});

// Convert to email
document.getElementById('convertEmailBtn')?.addEventListener('click', async () => {
  const fd = new FormData();
  fd.append('action',     'to_email');
  fd.append('session_id', CURRENT_SESSION);
  fd.append('csrf_token', CSRF);
  const res  = await fetch(`${APP_URL}/api/chat.php`, { method: 'POST', body: fd });
  const data = await res.json();
  if (data.email_id) {
    Swal.fire({ icon: 'success', title: 'Convertido a email', text: 'La conversación se envió por email.', background: 'var(--bg-card)', color: 'var(--text)' });
  }
});

// Canned responses
document.getElementById('cannedBtn')?.addEventListener('click', async () => {
  const bar = document.getElementById('cannedSuggestions');
  if (bar.style.display === 'none') {
    const res  = await fetch(`${APP_URL}/api/chat.php?action=canned`);
    const data = await res.json();
    const list = document.getElementById('cannedList');
    list.innerHTML = '';
    data.forEach(c => {
      const btn = document.createElement('button');
      btn.className = 'quick-reply-btn';
      btn.textContent = '/' + c.shortcut + ' — ' + c.title;
      btn.addEventListener('click', () => {
        document.getElementById('chatInput').value = c.content;
        bar.style.display = 'none';
      });
      list.appendChild(btn);
    });
    bar.style.display = 'block';
  } else {
    bar.style.display = 'none';
  }
});

// File attach
document.getElementById('attachChatBtn')?.addEventListener('click', () => {
  document.getElementById('chatFileInput').click();
});

document.getElementById('chatFileInput')?.addEventListener('change', async function() {
  const file = this.files[0];
  if (!file) return;
  const fd = new FormData();
  fd.append('action',     'upload');
  fd.append('session_id', CURRENT_SESSION);
  fd.append('file',       file);
  fd.append('csrf_token', CSRF);
  const res  = await fetch(`${APP_URL}/api/chat.php`, { method: 'POST', body: fd });
  const data = await res.json();
  if (data.url) {
    const fdMsg = new FormData();
    fdMsg.append('action',          'send');
    fdMsg.append('session_id',      CURRENT_SESSION);
    fdMsg.append('content',         file.name);
    fdMsg.append('sender_type',     'agent');
    fdMsg.append('message_type',    file.type.startsWith('image/') ? 'image' : 'file');
    fdMsg.append('attachment_path', data.url);
    fdMsg.append('attachment_name', file.name);
    fdMsg.append('csrf_token',      CSRF);
    await fetch(`${APP_URL}/api/chat.php`, { method: 'POST', body: fdMsg });
    location.reload();
  }
});

// Filter buttons
document.querySelectorAll('[data-filter]').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const f = btn.dataset.filter;
    document.querySelectorAll('.session-item').forEach(item => {
      item.style.display = (f === 'all' || item.dataset.status === f) ? 'flex' : 'none';
    });
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

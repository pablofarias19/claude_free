/* ================================================================
   MailGenius Pro — Main Application JS
   ================================================================ */

// ── CSRF helper ──────────────────────────────────────────────
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

async function api(endpoint, data = null, method = 'POST') {
  const opts = { headers: {} };
  if (data instanceof FormData) {
    opts.method = method;
    opts.body   = data;
  } else if (data) {
    opts.method  = method;
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify({ ...data, csrf_token: CSRF });
  } else {
    opts.method = 'GET';
  }
  const res = await fetch(`${APP_URL}/api/${endpoint}`, opts);
  return res.json();
}

// ── Sidebar toggle ────────────────────────────────────────────
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('show');
});
document.getElementById('sidebarOverlay')?.addEventListener('click', () => {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('show');
});

// ── Notifications ─────────────────────────────────────────────
let notifInterval;

async function loadNotifications() {
  const res = await fetch(`${APP_URL}/api/notifications.php?action=list`);
  const data = await res.json();
  const count   = document.getElementById('notifCount');
  const list    = document.getElementById('notifList');
  if (!count || !list) return;

  const unread = data.filter(n => !n.is_read).length;
  count.textContent = unread;
  count.style.display = unread > 0 ? 'flex' : 'none';

  if (data.length === 0) {
    list.innerHTML = '<div class="text-center text-muted py-4 small">Sin notificaciones</div>';
    return;
  }

  const typeIcon = {
    new_chat:       { icon: 'chat-dots-fill',   color: 'accent'   },
    new_email:      { icon: 'envelope-fill',     color: 'primary'  },
    campaign_done:  { icon: 'megaphone-fill',    color: 'warning'  },
    chat_assigned:  { icon: 'person-check-fill', color: 'success'  },
    system:         { icon: 'gear-fill',         color: 'gray'     },
  };

  list.innerHTML = data.slice(0, 12).map(n => {
    const t = typeIcon[n.type] || typeIcon.system;
    return `
      <div class="notif-item ${n.is_read ? '' : 'unread'}" data-id="${n.id}" onclick="markNotifRead(${n.id}, '${escHtml(n.link ?? '')}')">
        <div class="notif-icon" style="background:rgba(var(--${t.color}-rgb),.15)">
          <i class="bi bi-${t.icon}" style="color:var(--${t.color})"></i>
        </div>
        <div class="flex-grow-1">
          <div class="fw-600 small">${escHtml(n.title)}</div>
          <div class="text-xs text-muted">${escHtml(n.body ?? '')}</div>
          <div class="text-xs text-muted mt-1">${timeAgo(n.created_at)}</div>
        </div>
        ${!n.is_read ? '<div class="ms-2"><div style="width:7px;height:7px;background:var(--primary);border-radius:50%"></div></div>' : ''}
      </div>
    `;
  }).join('');
}

async function markNotifRead(id, link) {
  await fetch(`${APP_URL}/api/notifications.php?action=read&id=${id}`);
  if (link) window.location.href = link;
  else loadNotifications();
}

document.getElementById('markAllRead')?.addEventListener('click', async e => {
  e.preventDefault();
  await fetch(`${APP_URL}/api/notifications.php?action=read_all`);
  loadNotifications();
});

// Load on open
document.getElementById('notifBtn')?.addEventListener('click', loadNotifications);
// Poll every 30s
notifInterval = setInterval(loadNotifications, 30000);
loadNotifications();

// ── Toast ─────────────────────────────────────────────────────
function toast(message, type = 'success') {
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: type,
    title: message,
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    background: 'var(--bg-card)',
    color: 'var(--text)',
  });
}

// ── Confirm dialog ────────────────────────────────────────────
async function confirmDialog(title, text = '') {
  const result = await Swal.fire({
    title, text,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Confirmar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: 'var(--danger)',
    background: 'var(--bg-card)',
    color: 'var(--text)',
  });
  return result.isConfirmed;
}

// ── Tags input widget ─────────────────────────────────────────
class TagsInput {
  constructor(container, input, hidden) {
    this.container = container;
    this.input     = input;
    this.hidden    = hidden;
    this.tags      = [];

    if (hidden.value) {
      hidden.value.split(',').filter(Boolean).forEach(t => this.addTag(t.trim()));
    }

    input.addEventListener('keydown', e => {
      if ((e.key === 'Enter' || e.key === ',') && input.value.trim()) {
        e.preventDefault();
        this.addTag(input.value.trim());
        input.value = '';
      }
      if (e.key === 'Backspace' && !input.value && this.tags.length) {
        this.removeTag(this.tags.length - 1);
      }
    });

    container.addEventListener('click', () => input.focus());
  }

  addTag(value) {
    if (!value || this.tags.includes(value)) return;
    this.tags.push(value);
    this.render();
    this.hidden.value = this.tags.join(',');
  }

  removeTag(idx) {
    this.tags.splice(idx, 1);
    this.render();
    this.hidden.value = this.tags.join(',');
  }

  render() {
    const chips = this.container.querySelectorAll('.tag-chip');
    chips.forEach(c => c.remove());
    this.tags.forEach((tag, i) => {
      const chip = document.createElement('div');
      chip.className = 'tag-chip';
      chip.innerHTML = `${escHtml(tag)}<span class="remove" data-idx="${i}">×</span>`;
      chip.querySelector('.remove').addEventListener('click', () => this.removeTag(i));
      this.container.insertBefore(chip, this.input);
    });
  }

  getTags() { return this.tags; }
}

// ── Auto-resize textareas ─────────────────────────────────────
document.querySelectorAll('textarea[data-autoresize]').forEach(el => {
  el.addEventListener('input', () => {
    el.style.height = 'auto';
    el.style.height = el.scrollHeight + 'px';
  });
});

// ── Utilities ─────────────────────────────────────────────────
function escHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  })[c]);
}

function timeAgo(dateStr) {
  const diff = (Date.now() - new Date(dateStr)) / 1000;
  if (diff < 60)     return 'hace ' + Math.floor(diff)         + 's';
  if (diff < 3600)   return 'hace ' + Math.floor(diff/60)      + 'm';
  if (diff < 86400)  return 'hace ' + Math.floor(diff/3600)    + 'h';
  return 'hace ' + Math.floor(diff/86400) + 'd';
}

function formatSize(bytes) {
  const u = ['B','KB','MB','GB'];
  let i = 0;
  while (bytes >= 1024 && i < 3) { bytes /= 1024; i++; }
  return bytes.toFixed(1) + ' ' + u[i];
}

// Expose globally
window.toast = toast;
window.confirmDialog = confirmDialog;
window.TagsInput = TagsInput;
window.escHtml = escHtml;
window.markNotifRead = markNotifRead;

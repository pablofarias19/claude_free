// ─── View toggle ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('viewCards')?.addEventListener('click', () => {
    document.getElementById('cardsView').classList.remove('d-none');
    document.getElementById('tableView').classList.add('d-none');
    document.getElementById('viewCards').classList.add('active');
    document.getElementById('viewTable').classList.remove('active');
  });
  document.getElementById('viewTable')?.addEventListener('click', () => {
    document.getElementById('tableView').classList.remove('d-none');
    document.getElementById('cardsView').classList.add('d-none');
    document.getElementById('viewTable').classList.add('active');
    document.getElementById('viewCards').classList.remove('active');
  });

  // char counter
  document.getElementById('cmResponse')?.addEventListener('input', function () {
    const n = this.value.length;
    const el = document.getElementById('cmCharCount');
    if (el) el.textContent = `${n} / 500`;
    el?.classList.toggle('text-danger', n > 500);
  });
});

// ─── Category filter (bot) ────────────────────────────────
document.addEventListener('click', e => {
  const btn = e.target.closest('.cat-filter');
  if (!btn) return;
  document.querySelectorAll('.cat-filter').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  filterBotItems(btn.dataset.cat);
});

document.addEventListener('click', e => {
  const btn = e.target.closest('.canned-filter');
  if (!btn) return;
  document.querySelectorAll('.canned-filter').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  filterCannedItems(btn.dataset.cat);
});

document.getElementById('botSearch')?.addEventListener('input', function () {
  const q = this.value.toLowerCase().trim();
  const activeCat = document.querySelector('.cat-filter.active')?.dataset.cat ?? '__all__';
  document.querySelectorAll('.bot-card,.bot-row').forEach(el => {
    const matchCat = activeCat === '__all__' || el.dataset.cat === activeCat;
    const matchQ   = !q || el.dataset.search.includes(q);
    el.style.display = matchCat && matchQ ? '' : 'none';
  });
});

document.getElementById('cannedSearch')?.addEventListener('input', function () {
  const q = this.value.toLowerCase().trim();
  const activeCat = document.querySelector('.canned-filter.active')?.dataset.cat ?? '__all__';
  document.querySelectorAll('.canned-card').forEach(el => {
    const matchCat = activeCat === '__all__' || el.dataset.cat === activeCat;
    const matchQ   = !q || el.dataset.search.includes(q);
    el.style.display = matchCat && matchQ ? '' : 'none';
  });
});

function filterBotItems(cat) {
  document.querySelectorAll('.bot-card,.bot-row').forEach(el => {
    el.style.display = cat === '__all__' || el.dataset.cat === cat ? '' : 'none';
  });
}
function filterCannedItems(cat) {
  document.querySelectorAll('.canned-card').forEach(el => {
    el.style.display = cat === '__all__' || el.dataset.cat === cat ? '' : 'none';
  });
}

// ─── Quick reply rows ────────────────────────────────────
function addQRRow(text = '', value = '') {
  const list = document.getElementById('cmQRList');
  const div  = document.createElement('div');
  div.className = 'input-group mb-2';
  div.innerHTML = `
    <input type="text" class="form-control qr-text" placeholder="Texto del botón" value="${escAttr(text)}">
    <input type="text" class="form-control qr-val" placeholder="Valor (opcional)" value="${escAttr(value)}" style="max-width:130px">
    <button type="button" class="btn btn-outline-danger" onclick="this.closest('.input-group').remove()">
      <i class="bi bi-x-lg"></i>
    </button>`;
  list.appendChild(div);
}

function escAttr(s) {
  return String(s).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

// ─── Open add panel (new) ────────────────────────────────
function openAddBotPanel() {
  document.getElementById('addPanelTitle').textContent = 'Nueva Respuesta del Bot';
  document.getElementById('cmBotForm').reset();
  document.getElementById('cmBotId').value = '';
  document.getElementById('cmQRList').innerHTML = '';
  document.getElementById('cmCharCount').textContent = '0 / 500';
  bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('addPanel')).show();
}

// ─── Edit bot response ───────────────────────────────────
function editBotCM(data) {
  document.getElementById('addPanelTitle').textContent = 'Editar Respuesta del Bot';
  document.getElementById('cmBotId').value       = data.id;
  document.getElementById('cmTrigger').value     = data.trigger_word   ?? '';
  document.getElementById('cmResponse').value    = data.response_text  ?? '';
  document.getElementById('cmPriority').value    = data.priority       ?? 1;
  document.getElementById('cmExact').checked     = !!data.is_exact_match;
  document.getElementById('cmNewCat').value      = '';
  // Set category dropdown
  const sel = document.getElementById('cmBotCat');
  sel.value = data.category ?? 'General';
  if (!sel.value) {
    const opt = document.createElement('option');
    opt.value = opt.textContent = data.category;
    sel.appendChild(opt);
    sel.value = data.category;
  }
  // Char counter
  const charEl = document.getElementById('cmCharCount');
  if (charEl) charEl.textContent = `${(data.response_text ?? '').length} / 500`;

  // Quick replies
  document.getElementById('cmQRList').innerHTML = '';
  const qr = JSON.parse(data.quick_replies || '[]');
  qr.forEach(q => addQRRow(q.text, q.value));

  bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('addPanel')).show();
}

// ─── Save bot response ───────────────────────────────────
async function saveBotCM(addAnother = false) {
  const newCat = document.getElementById('cmNewCat').value.trim();
  const cat    = newCat || document.getElementById('cmBotCat').value;
  const qr = [...document.querySelectorAll('#cmQRList .input-group')].map(row => ({
    text:  row.querySelector('.qr-text').value.trim(),
    value: row.querySelector('.qr-val').value.trim() || row.querySelector('.qr-text').value.trim(),
  })).filter(q => q.text);

  const fd = new FormData(document.getElementById('cmBotForm'));
  fd.set('category', cat);
  fd.append('action', 'save_bot');
  fd.append('quick_replies', JSON.stringify(qr));
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

  const res  = await fetch('api/rules.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast('Guardado', 'success');
    if (addAnother) {
      document.getElementById('cmBotForm').reset();
      document.getElementById('cmBotId').value = '';
      document.getElementById('cmQRList').innerHTML = '';
      document.getElementById('cmCharCount').textContent = '0 / 500';
      document.getElementById('addPanelTitle').textContent = 'Nueva Respuesta del Bot';
    } else {
      bootstrap.Offcanvas.getInstance(document.getElementById('addPanel'))?.hide();
      setTimeout(() => location.reload(), 600);
    }
  } else toast(data.error || 'Error al guardar', 'error');
}

// ─── Delete bot response ─────────────────────────────────
async function deleteBotCM(id) {
  if (!await confirmDialog('¿Eliminar esta respuesta?')) return;
  const fd = new FormData();
  fd.append('action', 'delete_bot');
  fd.append('id', id);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/rules.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) { toast('Eliminada', 'success'); setTimeout(() => location.reload(), 600); }
  else toast(data.error || 'Error', 'error');
}

// ─── Toggle bot response ─────────────────────────────────
async function toggleBotCM(id, state) {
  const fd = new FormData();
  fd.append('action', 'toggle_bot');
  fd.append('id', id);
  fd.append('is_active', state ? 1 : 0);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  await fetch('api/rules.php', { method: 'POST', body: fd });
}

// ─── Canned reply CRUD ────────────────────────────────────
function editCannedCM(data) {
  document.getElementById('cannedPanelTitle').textContent = 'Editar Respuesta Enlatada';
  document.getElementById('cmCannedId').value      = data.id;
  document.getElementById('cmCannedCat').value     = data.category  ?? '';
  document.getElementById('cmCannedTitle').value   = data.title     ?? '';
  document.getElementById('cmCannedShortcut').value= data.shortcut  ?? '';
  document.getElementById('cmCannedContent').value = data.content   ?? '';
  bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('cannedPanel')).show();
}

async function saveCannedCM(addAnother = false) {
  const fd = new FormData(document.getElementById('cmCannedForm'));
  fd.append('action', 'save_canned');
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/rules.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast('Guardada', 'success');
    if (addAnother) {
      document.getElementById('cmCannedForm').reset();
      document.getElementById('cmCannedId').value = '';
    } else {
      bootstrap.Offcanvas.getInstance(document.getElementById('cannedPanel'))?.hide();
      setTimeout(() => location.reload(), 600);
    }
  } else toast(data.error || 'Error', 'error');
}

async function deleteCannedCM(id) {
  if (!await confirmDialog('¿Eliminar esta respuesta enlatada?')) return;
  const fd = new FormData();
  fd.append('action', 'delete_canned');
  fd.append('id', id);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/rules.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) { toast('Eliminada', 'success'); setTimeout(() => location.reload(), 600); }
  else toast(data.error || 'Error', 'error');
}

async function toggleCannedCM(id, state) {
  const fd = new FormData();
  fd.append('action', 'toggle_canned');
  fd.append('id', id);
  fd.append('is_active', state ? 1 : 0);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  await fetch('api/rules.php', { method: 'POST', body: fd });
}

// ─── Batch import ─────────────────────────────────────────
let parsedImportRows = [];

function parseImportText(raw) {
  return raw.split('\n')
    .map(l => l.trim())
    .filter(l => l && !l.startsWith('#'))
    .map(l => {
      const parts = l.split('|').map(p => p.trim());
      const keyword  = parts[0] ?? '';
      const response = parts[1] ?? '';
      const btns     = (parts[2] ?? '').split(',').map(b => b.trim()).filter(Boolean);
      const category = parts[3] ?? 'General';
      const priority = parseInt(parts[4] ?? '1', 10) || 1;
      return keyword && response ? { keyword, response, btns, category, priority } : null;
    })
    .filter(Boolean);
}

function previewImport() {
  const raw  = document.getElementById('importText').value;
  parsedImportRows = parseImportText(raw);
  const tbody = document.getElementById('importPreviewBody');
  const count = document.getElementById('importCount');
  const btn   = document.getElementById('importBtn');

  count.textContent = `${parsedImportRows.length} respuesta${parsedImportRows.length !== 1 ? 's' : ''}`;
  btn.disabled = parsedImportRows.length === 0;

  if (!parsedImportRows.length) {
    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">Pega el texto a la izquierda para previsualizar</td></tr>';
    return;
  }

  tbody.innerHTML = parsedImportRows.map(r => `
    <tr>
      <td><code class="text-primary">${escHtml(r.keyword)}</code></td>
      <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:.82rem">${escHtml(r.response)}</td>
      <td><span class="badge bg-secondary">${escHtml(r.category)}</span></td>
      <td><span class="text-muted small">${r.btns.length ? r.btns.map(b => `<span class="badge rounded-pill border border-secondary me-1">${escHtml(b)}</span>`).join('') : '—'}</span></td>
    </tr>`).join('');
}

function loadExample() {
  document.getElementById('importText').value = `# Saludos
hola | ¡Hola! Bienvenido a nuestra empresa. ¿En qué te podemos ayudar hoy? | Ver servicios,Cotizar,Hablar con agente | Saludo | 10
buenas | ¡Buenas! Estamos para ayudarte. ¿Qué necesitas? | Ver planes,Soporte | Saludo | 9

# Precios
precio | Tenemos planes desde $XX/mes. ¿Quieres que te enviemos información detallada? | Ver precios,Solicitar demo,Hablar con ventas | Precios | 8
costo | El costo varía según el plan elegido. Te puedo conectar con nuestro equipo de ventas. | Ver planes,Cotizar ahora | Precios | 7

# Soporte
soporte | Nuestro equipo de soporte está disponible lun-vie de 9am a 6pm. | Abrir ticket,Ver FAQ,Chatear con agente | Soporte | 6
ayuda | Claro, con gusto te ayudo. ¿Puedes contarme más sobre tu consulta? | | Soporte | 5`;
  previewImport();
}

function clearImport() {
  document.getElementById('importText').value = '';
  parsedImportRows = [];
  document.getElementById('importPreviewBody').innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">Pega el texto a la izquierda para previsualizar</td></tr>';
  document.getElementById('importCount').textContent = '0 respuestas';
  document.getElementById('importBtn').disabled = true;
}

async function runImport() {
  if (!parsedImportRows.length) return;
  const btn = document.getElementById('importBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Importando…';

  let ok = 0, err = 0;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  for (const row of parsedImportRows) {
    const qr = row.btns.map(b => ({ text: b, value: b }));
    const fd = new FormData();
    fd.append('action',       'save_bot');
    fd.append('trigger_word', row.keyword);
    fd.append('response_text',row.response);
    fd.append('category',     row.category);
    fd.append('priority',     row.priority);
    fd.append('quick_replies',JSON.stringify(qr));
    fd.append('csrf_token',   csrf);
    try {
      const res  = await fetch('api/rules.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) ok++; else err++;
    } catch { err++; }
  }

  if (err === 0) {
    toast(`${ok} respuesta${ok !== 1 ? 's' : ''} importada${ok !== 1 ? 's' : ''} correctamente`, 'success');
    bootstrap.Modal.getInstance(document.getElementById('importModal'))?.hide();
    setTimeout(() => location.reload(), 800);
  } else {
    toast(`${ok} importadas, ${err} con error`, err ? 'error' : 'success');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-upload me-2"></i>Reintentar';
  }
}

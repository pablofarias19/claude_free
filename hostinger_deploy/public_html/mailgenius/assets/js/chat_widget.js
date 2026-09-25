function openWidgetModal(data = null) {
  document.getElementById('widgetModalTitle').textContent = data ? 'Editar Widget' : 'Nuevo Widget';
  document.getElementById('widgetId').value   = data?.id            ?? '';
  document.getElementById('wName').value      = data?.name          ?? '';
  document.getElementById('wColor').value     = data?.primary_color ?? '#4f46e5';
  document.getElementById('wColorPicker').value = data?.primary_color ?? '#4f46e5';
  document.getElementById('wPosition').value  = data?.position      ?? 'bottom-right';
  document.getElementById('wActive').value    = data?.is_active     ?? '1';
  document.getElementById('wDomains').value   = data?.allowed_domains ?? '';
  new bootstrap.Modal(document.getElementById('widgetModal')).show();
}

function editWidget(data) { openWidgetModal(data); }

document.addEventListener('DOMContentLoaded', () => {
  const picker = document.getElementById('wColorPicker');
  const text   = document.getElementById('wColor');
  if (picker && text) {
    picker.addEventListener('input', () => text.value = picker.value);
    text.addEventListener('input', () => {
      if (/^#[0-9a-fA-F]{6}$/.test(text.value)) picker.value = text.value;
    });
  }
});

async function saveWidget() {
  const fd = new FormData(document.getElementById('widgetForm'));
  fd.append('action', 'save');
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/widgets.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast('Widget guardado', 'success');
    bootstrap.Modal.getInstance(document.getElementById('widgetModal'))?.hide();
    setTimeout(() => location.reload(), 800);
  } else toast(data.error || 'Error al guardar', 'error');
}

async function deleteWidget(id) {
  if (!await confirmDialog('¿Eliminar este widget?', 'Los sitios que lo usen dejarán de mostrarlo.')) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id', id);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/widgets.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) { toast('Eliminado', 'success'); setTimeout(() => location.reload(), 800); }
  else toast(data.error || 'Error', 'error');
}

function showEmbedCode(apiKey) {
  const url  = (typeof APP_URL !== 'undefined' ? APP_URL : window.location.origin);
  const code = `<!-- MailGenius Pro Widget -->\n<script src="${url}/widget/chat-widget.js" data-key="${apiKey}"><\/script>`;
  document.getElementById('embedCode').textContent = code;
  new bootstrap.Modal(document.getElementById('embedModal')).show();
}

function copyEmbed() {
  const code = document.getElementById('embedCode').textContent;
  navigator.clipboard.writeText(code).then(() => toast('Código copiado', 'success'));
}

function previewWidget(data) {
  const primary = data.primary_color || '#4f46e5';
  const pos     = data.position === 'bottom-left' ? 'left:20px' : 'right:20px';
  Swal.fire({
    title: 'Vista previa del widget',
    html: `<div style="position:relative;height:120px">
      <div style="position:absolute;bottom:10px;${pos}">
        <div style="width:56px;height:56px;border-radius:50%;background:${escHtml(primary)};display:flex;align-items:center;justify-content:center;font-size:24px;box-shadow:0 4px 20px rgba(0,0,0,.3);cursor:pointer">💬</div>
      </div>
    </div>
    <p class="text-muted mt-2" style="font-size:.85rem">El botón aparece en la esquina de tu sitio</p>`,
    background: 'var(--surface)',
    color: 'var(--text-primary)',
    confirmButtonColor: primary,
  });
}

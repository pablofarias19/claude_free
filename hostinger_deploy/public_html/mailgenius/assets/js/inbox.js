async function viewEmail(id) {
  const modal = new bootstrap.Modal(document.getElementById('emailModal'));
  document.getElementById('emailModalBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
  modal.show();
  try {
    const res  = await fetch(`api/send.php?action=get&id=${id}`);
    const data = await res.json();
    if (data.error) { document.getElementById('emailModalBody').innerHTML = `<p class="text-danger">${data.error}</p>`; return; }
    document.getElementById('emailModalTitle').textContent = data.subject || '(sin asunto)';
    document.getElementById('emailModalBody').innerHTML = `
      <div class="mb-3 pb-3 border-bottom" style="border-color:var(--border)!important">
        <div class="d-flex justify-content-between text-muted mb-1" style="font-size:.85rem">
          <span><strong>Para:</strong> ${escHtml(data.to_email)}</span>
          <span>${data.created_at?.substr(0,16).replace('T',' ') ?? ''}</span>
        </div>
        ${data.cc_email ? `<div class="text-muted" style="font-size:.85rem"><strong>CC:</strong> ${escHtml(data.cc_email)}</div>` : ''}
        ${data.from_email ? `<div class="text-muted" style="font-size:.85rem"><strong>De:</strong> ${escHtml(data.from_email)}</div>` : ''}
      </div>
      <div style="line-height:1.7">${data.body || '<em class="text-muted">Sin cuerpo</em>'}</div>
    `;
  } catch(e) {
    document.getElementById('emailModalBody').innerHTML = '<p class="text-danger">Error al cargar el mensaje.</p>';
  }
}

async function deleteEmail(id) {
  if (!await confirmDialog('¿Eliminar este email?', 'Esta acción no se puede deshacer.')) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id', id);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/send.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) { location.reload(); }
  else toast(data.error || 'Error al eliminar', 'error');
}

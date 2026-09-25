async function cancelScheduled(id) {
  if (!await confirmDialog('¿Cancelar este envío programado?', 'El email no se enviará.')) return;
  const fd = new FormData();
  fd.append('action', 'cancel');
  fd.append('id', id);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/send.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) { toast('Programación cancelada', 'success'); setTimeout(() => location.reload(), 1000); }
  else toast(data.error || 'Error', 'error');
}

async function viewScheduled(id) {
  const res  = await fetch(`api/send.php?action=get&id=${id}`);
  const data = await res.json();
  if (data.error) { toast(data.error, 'error'); return; }
  Swal.fire({
    title: escHtml(data.subject),
    html: `<div style="text-align:left;font-size:.9rem">
      <strong>Para:</strong> ${escHtml(data.to_email)}<br>
      <strong>Programado:</strong> ${data.scheduled_at?.substr(0,16).replace('T',' ') ?? '—'}<br><br>
      <div style="max-height:200px;overflow-y:auto">${data.body || ''}</div>
    </div>`,
    background: 'var(--surface)',
    color: 'var(--text-primary)',
    confirmButtonColor: 'var(--primary)',
  });
}

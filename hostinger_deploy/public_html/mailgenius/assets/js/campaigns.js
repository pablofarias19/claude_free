let campQuill;

document.addEventListener('DOMContentLoaded', () => {
  campQuill = new Quill('#campEditor', {
    theme: 'snow',
    modules: { toolbar: [['bold','italic','underline'],['link'],['clean']] }
  });
  flatpickr('#campSchedule', { enableTime: true, dateFormat: 'Y-m-d H:i', minDate: 'today' });
});

function openCampaignModal(data = null) {
  document.getElementById('campaignModalTitle').textContent = data ? 'Editar Campaña' : 'Nueva Campaña';
  document.getElementById('campId').value        = data?.id          ?? '';
  document.getElementById('campName').value      = data?.name        ?? '';
  document.getElementById('campSubject').value   = data?.subject     ?? '';
  document.getElementById('campFromEmail').value = data?.from_email  ?? '';
  document.getElementById('campFromName').value  = data?.from_name   ?? '';
  document.getElementById('campBatch').value     = data?.batch_size  ?? 50;
  document.getElementById('campSchedule').value  = data?.scheduled_at ?? '';
  if (campQuill) campQuill.root.innerHTML = data?.body ?? '';
  new bootstrap.Modal(document.getElementById('campaignModal')).show();
}

async function editCampaign(id) {
  const res  = await fetch(`api/campaigns.php?action=get&id=${id}`);
  const data = await res.json();
  if (data.error) { toast(data.error, 'error'); return; }
  openCampaignModal(data);
}

async function saveCampaign(mode = 'draft') {
  document.getElementById('campBody').value = campQuill?.root.innerHTML ?? '';
  const fd = new FormData(document.getElementById('campaignForm'));
  fd.append('action', 'save');
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/campaigns.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (!data.success) { toast(data.error || 'Error al guardar', 'error'); return; }
  if (mode === 'start') {
    await startCampaign(data.id);
  } else {
    toast('Campaña guardada como borrador', 'success');
    bootstrap.Modal.getInstance(document.getElementById('campaignModal'))?.hide();
    setTimeout(() => location.reload(), 800);
  }
}

async function startCampaign(id) {
  const fd = new FormData();
  fd.append('action', 'start');
  fd.append('id', id);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/campaigns.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) { toast('Campaña iniciada', 'success'); setTimeout(() => location.reload(), 800); }
  else toast(data.error || 'Error', 'error');
}

async function pauseCampaign(id) {
  const fd = new FormData();
  fd.append('action', 'pause');
  fd.append('id', id);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/campaigns.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) { toast('Campaña pausada', 'success'); setTimeout(() => location.reload(), 800); }
  else toast(data.error || 'Error', 'error');
}

async function deleteCampaign(id) {
  if (!await confirmDialog('¿Eliminar esta campaña?', 'Se perderán todos los datos.')) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id', id);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/campaigns.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) { toast('Eliminada', 'success'); setTimeout(() => location.reload(), 800); }
  else toast(data.error || 'Error', 'error');
}

async function viewCampaignStats(id) {
  const res  = await fetch(`api/campaigns.php?action=stats&id=${id}`);
  const data = await res.json();
  Swal.fire({
    title: escHtml(data.name),
    html: `<div style="text-align:left;font-size:.9rem">
      <strong>Estado:</strong> ${data.status}<br>
      <strong>Total destinatarios:</strong> ${data.total_recipients}<br>
      <strong>Enviados:</strong> ${data.sent_count}<br>
      <strong>Aperturas:</strong> ${data.open_count}<br>
      <strong>Clics:</strong> ${data.click_count}<br>
      <strong>Rebotados:</strong> ${data.bounce_count}<br>
    </div>`,
    background: 'var(--surface)',
    color: 'var(--text-primary)',
    confirmButtonColor: 'var(--primary)',
  });
}

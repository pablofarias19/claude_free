function openBotModal() {
  document.getElementById('botModalTitle').textContent = 'Nueva Respuesta del Bot';
  document.getElementById('botForm').reset();
  document.getElementById('botId').value = '';
  new bootstrap.Modal(document.getElementById('botModal')).show();
}

function editBotResponse(data) {
  document.getElementById('botModalTitle').textContent = 'Editar Respuesta del Bot';
  document.getElementById('botId').value       = data.id;
  document.getElementById('botTrigger').value  = data.trigger_word  ?? '';
  document.getElementById('botResponse').value = data.response_text ?? '';
  document.getElementById('botPriority').value = data.priority      ?? 1;
  document.getElementById('botExact').checked  = !!data.is_exact_match;
  // Convert quick replies JSON to text
  const qr = JSON.parse(data.quick_replies || '[]');
  document.getElementById('botQR').value = qr.map(q => `${q.text}|${q.value}`).join('\n');
  new bootstrap.Modal(document.getElementById('botModal')).show();
}

async function saveBotResponse() {
  const qrRaw = document.getElementById('botQR').value.trim();
  const qr = qrRaw ? qrRaw.split('\n').filter(Boolean).map(line => {
    const [text, value] = line.split('|');
    return { text: text?.trim(), value: (value ?? text)?.trim() };
  }) : [];
  const fd = new FormData(document.getElementById('botForm'));
  fd.append('action', 'save_bot');
  fd.append('quick_replies', JSON.stringify(qr));
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/rules.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast('Guardado', 'success');
    bootstrap.Modal.getInstance(document.getElementById('botModal'))?.hide();
    setTimeout(() => location.reload(), 800);
  } else toast(data.error || 'Error', 'error');
}

async function deleteBotResponse(id) {
  if (!await confirmDialog('¿Eliminar esta respuesta?')) return;
  const fd = new FormData();
  fd.append('action', 'delete_bot');
  fd.append('id', id);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/rules.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) { toast('Eliminada', 'success'); setTimeout(() => location.reload(), 800); }
  else toast(data.error || 'Error', 'error');
}

async function toggleBotResponse(id, state) {
  const fd = new FormData();
  fd.append('action', 'toggle_bot');
  fd.append('id', id);
  fd.append('is_active', state ? 1 : 0);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  await fetch('api/rules.php', { method: 'POST', body: fd });
}

function openCannedModal() {
  document.getElementById('cannedModalTitle').textContent = 'Nueva Respuesta Enlatada';
  document.getElementById('cannedForm').reset();
  document.getElementById('cannedId').value = '';
  new bootstrap.Modal(document.getElementById('cannedModal')).show();
}

function editCanned(data) {
  document.getElementById('cannedModalTitle').textContent = 'Editar Respuesta Enlatada';
  document.getElementById('cannedId').value      = data.id;
  document.getElementById('cannedTitle').value   = data.title    ?? '';
  document.getElementById('cannedShortcut').value= data.shortcut ?? '';
  document.getElementById('cannedContent').value = data.content  ?? '';
  new bootstrap.Modal(document.getElementById('cannedModal')).show();
}

async function saveCanned() {
  const fd = new FormData(document.getElementById('cannedForm'));
  fd.append('action', 'save_canned');
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/rules.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast('Guardada', 'success');
    bootstrap.Modal.getInstance(document.getElementById('cannedModal'))?.hide();
    setTimeout(() => location.reload(), 800);
  } else toast(data.error || 'Error', 'error');
}

async function deleteCanned(id) {
  if (!await confirmDialog('¿Eliminar esta respuesta?')) return;
  const fd = new FormData();
  fd.append('action', 'delete_canned');
  fd.append('id', id);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/rules.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) { toast('Eliminada', 'success'); setTimeout(() => location.reload(), 800); }
  else toast(data.error || 'Error', 'error');
}

function openContactModal() {
  document.getElementById('contactModalTitle').textContent = 'Nuevo Contacto';
  document.getElementById('contactForm').reset();
  document.getElementById('contactId').value = '';
  new bootstrap.Modal(document.getElementById('contactModal')).show();
}

function editContact(data) {
  document.getElementById('contactModalTitle').textContent = 'Editar Contacto';
  document.getElementById('contactId').value  = data.id;
  document.getElementById('cName').value      = data.name    ?? '';
  document.getElementById('cEmail').value     = data.email   ?? '';
  document.getElementById('cPhone').value     = data.phone   ?? '';
  document.getElementById('cCompany').value   = data.company ?? '';
  document.getElementById('cNotes').value     = data.notes   ?? '';
  document.getElementById('cStatus').value    = data.status  ?? 'active';
  new bootstrap.Modal(document.getElementById('contactModal')).show();
}

async function saveContact() {
  const fd = new FormData(document.getElementById('contactForm'));
  fd.append('action', 'save');
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/contacts.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast('Contacto guardado', 'success');
    bootstrap.Modal.getInstance(document.getElementById('contactModal'))?.hide();
    setTimeout(() => location.reload(), 800);
  } else toast(data.error || 'Error al guardar', 'error');
}

async function deleteContact(id) {
  if (!await confirmDialog('¿Eliminar contacto?', 'Esta acción no se puede deshacer.')) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id', id);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/contacts.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) { toast('Eliminado', 'success'); setTimeout(() => location.reload(), 800); }
  else toast(data.error || 'Error', 'error');
}

function openGroupModal() {
  document.getElementById('groupName').value = '';
  new bootstrap.Modal(document.getElementById('groupModal')).show();
}

async function saveGroup() {
  const name = document.getElementById('groupName').value.trim();
  if (!name) return;
  const fd = new FormData();
  fd.append('action', 'save_group');
  fd.append('name', name);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/contacts.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast('Grupo creado', 'success');
    bootstrap.Modal.getInstance(document.getElementById('groupModal'))?.hide();
    setTimeout(() => location.reload(), 800);
  } else toast(data.error || 'Error', 'error');
}

async function importContacts(input) {
  const file = input.files[0];
  if (!file) return;
  const fd = new FormData();
  fd.append('action', 'import');
  fd.append('csv', file);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  toast('Importando…', 'info');
  const res  = await fetch('api/contacts.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.imported !== undefined) {
    toast(`Importados: ${data.imported}, Errores: ${data.errors}`, 'success');
    setTimeout(() => location.reload(), 1200);
  } else toast(data.error || 'Error en importación', 'error');
  input.value = '';
}

function toggleAll(master) {
  document.querySelectorAll('.contact-check').forEach(cb => cb.checked = master.checked);
  updateBulk();
}

function updateBulk() {
  const count = document.querySelectorAll('.contact-check:checked').length;
  document.getElementById('bulkActions').style.display = count > 0 ? 'block' : 'none';
}

async function deleteSelected() {
  const ids = [...document.querySelectorAll('.contact-check:checked')].map(c => c.value);
  if (!ids.length) return;
  if (!await confirmDialog(`¿Eliminar ${ids.length} contacto(s)?`, 'Esta acción no se puede deshacer.')) return;
  const fd = new FormData();
  fd.append('action', 'delete_bulk');
  fd.append('ids', JSON.stringify(ids));
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/contacts.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) { toast(`${data.deleted} eliminados`, 'success'); setTimeout(() => location.reload(), 800); }
  else toast(data.error || 'Error', 'error');
}

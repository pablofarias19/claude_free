let tplQuill;

document.addEventListener('DOMContentLoaded', () => {
  tplQuill = new Quill('#tplEditor', {
    theme: 'snow',
    modules: { toolbar: [
      [{ header: [1,2,3,false] }],
      ['bold','italic','underline'],
      [{ color: [] }],
      [{ list: 'ordered' },{ list: 'bullet' }],
      ['link','image'],
      ['clean']
    ]}
  });
});

function openTemplateModal(data = null) {
  document.getElementById('tplModalTitle').textContent = data ? 'Editar Plantilla' : 'Nueva Plantilla';
  document.getElementById('tplId').value      = data?.id      ?? '';
  document.getElementById('tplName').value    = data?.name    ?? '';
  document.getElementById('tplSubject').value = data?.subject ?? '';
  document.getElementById('tplCat').value     = data?.category_id ?? '';
  if (tplQuill) tplQuill.root.innerHTML = data?.body ?? '';
  new bootstrap.Modal(document.getElementById('tplModal')).show();
}

async function editTemplate(id) {
  const res  = await fetch(`api/templates.php?action=get&id=${id}`);
  const data = await res.json();
  if (data.error) { toast(data.error, 'error'); return; }
  openTemplateModal(data);
}

async function saveTemplate() {
  document.getElementById('tplBody').value = tplQuill?.root.innerHTML ?? '';
  const form = document.getElementById('tplForm');
  const fd   = new FormData(form);
  fd.append('action', 'save');
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/templates.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast('Plantilla guardada', 'success');
    bootstrap.Modal.getInstance(document.getElementById('tplModal'))?.hide();
    setTimeout(() => location.reload(), 800);
  } else {
    toast(data.error || 'Error al guardar', 'error');
  }
}

async function deleteTemplate(id) {
  if (!await confirmDialog('¿Eliminar esta plantilla?', 'Se perderá permanentemente.')) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id', id);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/templates.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) { toast('Eliminada', 'success'); setTimeout(() => location.reload(), 800); }
  else toast(data.error || 'Error', 'error');
}

async function previewTemplate(id) {
  const res  = await fetch(`api/templates.php?action=get&id=${id}`);
  const data = await res.json();
  if (data.error) { toast(data.error, 'error'); return; }
  const frame = document.getElementById('previewFrame');
  frame.srcdoc = data.body || '<p>Sin contenido</p>';
  document.getElementById('useTemplateBtn').onclick = () => {
    window.location = 'compose.php?tpl=' + id;
  };
  new bootstrap.Modal(document.getElementById('previewModal')).show();
}

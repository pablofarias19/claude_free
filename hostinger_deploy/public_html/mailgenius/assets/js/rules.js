let conditionCount = 0;

function openRuleModal(data = null) {
  conditionCount = 0;
  document.getElementById('ruleModalTitle').textContent = data ? 'Editar Regla' : 'Nueva Regla';
  document.getElementById('ruleId').value       = data?.id          ?? '';
  document.getElementById('ruleName').value     = data?.name        ?? '';
  document.getElementById('rulePriority').value = data?.priority    ?? '';
  document.getElementById('ruleCat').value      = data?.category_id ?? '';
  document.getElementById('ruleAction').value   = data?.action      ?? 'auto_reply';
  document.getElementById('ruleActionValue').value = data?.action_value ?? '';
  document.getElementById('conditionsContainer').innerHTML = '';
  const conds = data ? (JSON.parse(data.conditions || '[]')) : [];
  if (conds.length) conds.forEach(c => addCondition(c));
  else addCondition();
  updateActionConfig();
  new bootstrap.Modal(document.getElementById('ruleModal')).show();
}

async function editRule(id) {
  const res  = await fetch(`api/rules.php?action=list`);
  const all  = await res.json();
  const rule = all.data?.find(r => r.id == id);
  if (!rule) { toast('Regla no encontrada', 'error'); return; }
  openRuleModal(rule);
}

function addCondition(data = null) {
  conditionCount++;
  const idx = conditionCount;
  const div = document.createElement('div');
  div.className = 'condition-row d-flex gap-2 align-items-center mb-2';
  div.dataset.idx = idx;
  div.innerHTML = `
    <select class="form-select form-select-sm" name="cond_field_${idx}" style="max-width:140px">
      <option value="subject" ${data?.field==='subject'?'selected':''}>Asunto</option>
      <option value="from" ${data?.field==='from'?'selected':''}>Remitente</option>
      <option value="body" ${data?.field==='body'?'selected':''}>Cuerpo</option>
      <option value="to" ${data?.field==='to'?'selected':''}>Destinatario</option>
    </select>
    <select class="form-select form-select-sm" name="cond_op_${idx}" style="max-width:170px">
      <option value="contains" ${data?.operator==='contains'?'selected':''}>contiene</option>
      <option value="not_contains" ${data?.operator==='not_contains'?'selected':''}>no contiene</option>
      <option value="equals" ${data?.operator==='equals'?'selected':''}>igual a</option>
      <option value="starts_with" ${data?.operator==='starts_with'?'selected':''}>empieza con</option>
      <option value="ends_with" ${data?.operator==='ends_with'?'selected':''}>termina con</option>
      <option value="regex" ${data?.operator==='regex'?'selected':''}>regex</option>
    </select>
    <input type="text" class="form-control form-control-sm" name="cond_value_${idx}" value="${escHtml(data?.value??'')}" placeholder="valor…">
    <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.remove()">
      <i class="bi bi-x"></i>
    </button>
  `;
  document.getElementById('conditionsContainer').appendChild(div);
}

function updateActionConfig() {
  const action = document.getElementById('ruleAction').value;
  const cfg    = document.getElementById('actionConfig');
  let html = '';
  switch (action) {
    case 'auto_reply':
      html = `<label class="form-label">Mensaje de respuesta automática</label>
              <textarea class="form-control" id="ruleActionValue" name="action_value" rows="4" placeholder="Hola, gracias por tu mensaje…"></textarea>`;
      break;
    case 'forward':
      html = `<label class="form-label">Email del agente destino</label>
              <input type="email" class="form-control" id="ruleActionValue" name="action_value" placeholder="agente@empresa.com">`;
      break;
    case 'apply_template':
      html = `<label class="form-label">Seleccionar plantilla</label>
              <select class="form-select" id="ruleActionValue" name="action_value">
                <option value="">-- Seleccionar --</option>
                ${TEMPLATES_LIST.map(t => `<option value="${t.id}">${escHtml(t.name)}</option>`).join('')}
              </select>`;
      break;
    case 'route_to_diagram':
      html = `<label class="form-label">Seleccionar diagrama</label>
              <select class="form-select" id="ruleActionValue" name="action_value">
                <option value="">-- Seleccionar --</option>
                ${DIAGRAMS.map(d => `<option value="${d.id}">${escHtml(d.name)}</option>`).join('')}
              </select>`;
      break;
    case 'tag':
      html = `<label class="form-label">Etiqueta a aplicar</label>
              <input type="text" class="form-control" id="ruleActionValue" name="action_value" placeholder="urgente, vip, soporte…">`;
      break;
    case 'ignore':
      html = `<div class="alert alert-warning bg-warning bg-opacity-10 p-3">El email será marcado como ignorado y no se enviará respuesta.</div>
              <input type="hidden" id="ruleActionValue" name="action_value" value="ignored">`;
      break;
  }
  cfg.innerHTML = html;
  // Restore value if editing
  const stored = document.getElementById('ruleActionValue');
  if (stored && stored.tagName === 'SELECT' && window._editRuleActionValue) {
    stored.value = window._editRuleActionValue;
  }
}

async function saveRule() {
  const fd = new FormData();
  fd.append('action', 'save');
  fd.append('id',       document.getElementById('ruleId').value);
  fd.append('name',     document.getElementById('ruleName').value);
  fd.append('priority', document.getElementById('rulePriority').value);
  fd.append('category_id', document.getElementById('ruleCat').value);
  fd.append('action',   document.getElementById('ruleAction').value);
  fd.append('action_value', document.getElementById('ruleActionValue')?.value ?? '');
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

  // Collect conditions
  const rows  = document.querySelectorAll('.condition-row');
  const conds = [];
  rows.forEach(row => {
    const idx   = row.dataset.idx;
    const field = row.querySelector(`[name=cond_field_${idx}]`)?.value;
    const op    = row.querySelector(`[name=cond_op_${idx}]`)?.value;
    const val   = row.querySelector(`[name=cond_value_${idx}]`)?.value;
    if (field && op && val) conds.push({ field, operator: op, value: val });
  });
  fd.append('conditions', JSON.stringify(conds));

  const res  = await fetch('api/rules.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast('Regla guardada', 'success');
    bootstrap.Modal.getInstance(document.getElementById('ruleModal'))?.hide();
    setTimeout(() => location.reload(), 800);
  } else toast(data.error || 'Error al guardar', 'error');
}

async function deleteRule(id) {
  if (!await confirmDialog('¿Eliminar esta regla?', 'Esta acción no se puede deshacer.')) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id', id);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  const res  = await fetch('api/rules.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) { toast('Eliminada', 'success'); setTimeout(() => location.reload(), 800); }
  else toast(data.error || 'Error', 'error');
}

async function toggleRule(id, state) {
  const fd = new FormData();
  fd.append('action', 'toggle');
  fd.append('id', id);
  fd.append('is_active', state ? 1 : 0);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  await fetch('api/rules.php', { method: 'POST', body: fd });
}

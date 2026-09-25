/* ================================================================
   MailGenius Pro — Interactive Diagram Builder
   Drag-and-drop SVG flowchart editor
   ================================================================ */

document.addEventListener('DOMContentLoaded', () => {
  const canvas      = document.getElementById('diagramCanvas');
  if (!canvas) return;

  const svg         = document.getElementById('connectionsSvg');
  const propsPanel  = document.getElementById('nodePropsPanel');

  // ── State ─────────────────────────────────────────────────
  let nodes        = [];
  let connections  = [];
  let selectedNode = null;
  let dragging     = null;
  let connecting   = null;   // source node id when drawing a connection
  let tempLine     = null;
  let scale        = 1;
  let nodeIdSeq    = 1;

  // Load initial data
  const initNodes = document.getElementById('initialNodes')?.value;
  const initConns = document.getElementById('initialConnections')?.value;
  if (initNodes)  { try { nodes       = JSON.parse(initNodes); } catch(e) {} }
  if (initConns)  { try { connections = JSON.parse(initConns);  } catch(e) {} }
  if (nodes.length) {
    nodeIdSeq = Math.max(...nodes.map(n => parseInt(n.id.replace('node','')) || 0)) + 1;
  }

  // ── Render ────────────────────────────────────────────────
  function render() {
    // Clear existing nodes (keep SVG)
    canvas.querySelectorAll('.diagram-node').forEach(el => el.remove());
    svg.querySelectorAll('.conn-line, .conn-label').forEach(el => el.remove());

    // Render nodes
    nodes.forEach(n => renderNode(n));

    // Render connections
    connections.forEach(c => renderConnection(c));
  }

  function renderNode(node) {
    const el = document.createElement('div');
    el.id           = 'el_' + node.id;
    el.className    = `diagram-node node-${node.type} ${selectedNode === node.id ? 'selected' : ''}`;
    el.style.left   = node.x + 'px';
    el.style.top    = node.y + 'px';
    el.dataset.id   = node.id;
    el.dataset.type = node.type;

    el.innerHTML = `
      <div>${escHtml(node.label)}</div>
      ${node.data?.response ? `<div style="font-size:10px;opacity:.7;margin-top:4px">${escHtml(node.data.response.substring(0,40))}...</div>` : ''}
      <div class="node-ports">
        <div class="node-port" style="bottom:-5px;left:50%;transform:translateX(-50%)" data-node="${node.id}" data-port="out" title="Conectar desde aquí"></div>
        <div class="node-port" style="top:-5px;left:50%;transform:translateX(-50%)"   data-node="${node.id}" data-port="in"  title="Conectar aquí"></div>
      </div>
    `;

    // Drag node
    el.addEventListener('mousedown', e => {
      if (e.target.classList.contains('node-port')) return;
      e.preventDefault();
      selectNode(node.id);
      dragging = {
        el, nodeId: node.id,
        startX: e.clientX - node.x,
        startY: e.clientY - node.y,
      };
    });

    // Port: start connection
    el.querySelectorAll('.node-port[data-port="out"]').forEach(port => {
      port.addEventListener('mousedown', e => {
        e.stopPropagation();
        e.preventDefault();
        connecting = node.id;
        tempLine   = makeTempLine(node.x + 70, node.y + 40);
        svg.appendChild(tempLine);
      });
    });

    // Port: end connection
    el.querySelectorAll('.node-port[data-port="in"]').forEach(port => {
      port.addEventListener('mouseup', e => {
        if (connecting && connecting !== node.id) {
          // Avoid duplicates
          const exists = connections.find(c => c.source === connecting && c.target === node.id);
          if (!exists) {
            connections.push({ id: 'conn_' + Date.now(), source: connecting, target: node.id, label: '' });
            render();
          }
          connecting = null;
          if (tempLine) { tempLine.remove(); tempLine = null; }
        }
      });
    });

    canvas.appendChild(el);
  }

  function renderConnection(conn) {
    const src = nodes.find(n => n.id === conn.source);
    const tgt = nodes.find(n => n.id === conn.target);
    if (!src || !tgt) return;

    const x1 = src.x + 70, y1 = src.y + 40;
    const x2 = tgt.x + 70, y2 = tgt.y;

    // Curved path
    const cp1x = x1, cp1y = (y1 + y2) / 2;
    const cp2x = x2, cp2y = (y1 + y2) / 2;
    const d    = `M ${x1} ${y1} C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${x2} ${y2}`;

    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('d', d);
    path.setAttribute('class', 'conn-line');
    path.setAttribute('fill', 'none');
    path.setAttribute('stroke', '#4f46e5');
    path.setAttribute('stroke-width', '1.8');
    path.setAttribute('stroke-opacity', '.8');
    path.setAttribute('marker-end', 'url(#arrowhead)');
    path.dataset.connId = conn.id;
    path.style.cursor   = 'pointer';
    path.addEventListener('click', e => {
      e.stopPropagation();
      editConnectionLabel(conn);
    });
    svg.appendChild(path);

    // Label
    if (conn.label) {
      const mx   = (x1 + x2) / 2;
      const my   = (y1 + y2) / 2;
      const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      text.setAttribute('x', mx);
      text.setAttribute('y', my - 6);
      text.setAttribute('text-anchor', 'middle');
      text.setAttribute('class', 'conn-label');
      text.setAttribute('fill', '#94a3b8');
      text.setAttribute('font-size', '11');
      text.textContent = conn.label;
      svg.appendChild(text);
    }
  }

  function makeTempLine(x, y) {
    const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    line.setAttribute('x1', x);
    line.setAttribute('y1', y);
    line.setAttribute('x2', x);
    line.setAttribute('y2', y);
    line.setAttribute('stroke', '#4f46e5');
    line.setAttribute('stroke-width', '1.5');
    line.setAttribute('stroke-dasharray', '5,3');
    line.setAttribute('stroke-opacity', '.8');
    line.id = 'tempLine';
    return line;
  }

  // ── Select node ───────────────────────────────────────────
  function selectNode(id) {
    selectedNode = id;
    render();
    showNodeProps(id);
  }

  function showNodeProps(id) {
    const node = nodes.find(n => n.id === id);
    if (!node || !propsPanel) return;

    propsPanel.innerHTML = `
      <div class="mb-3">
        <label class="form-label">Etiqueta</label>
        <input type="text" class="form-control" id="propLabel" value="${escHtml(node.label)}" placeholder="Etiqueta del nodo">
      </div>
      ${node.type === 'response' ? `
        <div class="mb-3">
          <label class="form-label">Texto de respuesta</label>
          <textarea class="form-control" id="propResponse" rows="4" placeholder="Escribe la respuesta automática...">${escHtml(node.data?.response ?? '')}</textarea>
        </div>
      ` : ''}
      ${node.type === 'decision' ? `
        <div class="mb-3">
          <label class="form-label">Condición</label>
          <input type="text" class="form-control" id="propCondition" value="${escHtml(node.data?.condition ?? '')}" placeholder="Ej: precio, factura, error">
          <div class="form-text text-muted">Palabras clave que activan este camino</div>
        </div>
      ` : ''}
      ${node.type === 'action' ? `
        <div class="mb-3">
          <label class="form-label">Tipo de acción</label>
          <select class="form-select" id="propActionType">
            <option value="forward"  ${node.data?.action_type === 'forward'  ? 'selected' : ''}>Reenviar email</option>
            <option value="template" ${node.data?.action_type === 'template' ? 'selected' : ''}>Aplicar plantilla</option>
            <option value="tag"      ${node.data?.action_type === 'tag'      ? 'selected' : ''}>Etiquetar contacto</option>
            <option value="notify"   ${node.data?.action_type === 'notify'   ? 'selected' : ''}>Notificar agente</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Valor</label>
          <input type="text" class="form-control" id="propActionValue" value="${escHtml(node.data?.action_value ?? '')}" placeholder="Valor o destino de la acción">
        </div>
      ` : ''}
      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary btn-sm flex-grow-1" id="saveNodeProps">
          <i class="bi bi-check2 me-1"></i>Aplicar
        </button>
        <button class="btn btn-danger btn-sm" id="deleteNodeBtn" title="Eliminar nodo">
          <i class="bi bi-trash"></i>
        </button>
      </div>
    `;

    document.getElementById('saveNodeProps')?.addEventListener('click', () => {
      node.label = document.getElementById('propLabel').value;
      if (node.type === 'response')  node.data = { ...node.data, response:     document.getElementById('propResponse')?.value };
      if (node.type === 'decision')  node.data = { ...node.data, condition:    document.getElementById('propCondition')?.value };
      if (node.type === 'action')    node.data = { ...node.data,
        action_type:  document.getElementById('propActionType')?.value,
        action_value: document.getElementById('propActionValue')?.value,
      };
      render();
    });

    document.getElementById('deleteNodeBtn')?.addEventListener('click', () => deleteSelected());
  }

  // ── Mouse events ──────────────────────────────────────────
  document.addEventListener('mousemove', e => {
    // Drag node
    if (dragging) {
      const rect = canvas.getBoundingClientRect();
      const node = nodes.find(n => n.id === dragging.nodeId);
      if (node) {
        node.x = Math.max(0, Math.min((e.clientX - rect.left - dragging.startX), canvas.offsetWidth  - 140));
        node.y = Math.max(0, Math.min((e.clientY - rect.top  - dragging.startY), canvas.offsetHeight - 80));
        dragging.el.style.left = node.x + 'px';
        dragging.el.style.top  = node.y + 'px';
        // Redraw connections
        svg.querySelectorAll('.conn-line, .conn-label').forEach(el => el.remove());
        connections.forEach(c => renderConnection(c));
      }
    }

    // Temp connection line
    if (connecting && tempLine) {
      const rect = canvas.getBoundingClientRect();
      tempLine.setAttribute('x2', e.clientX - rect.left);
      tempLine.setAttribute('y2', e.clientY - rect.top);
    }
  });

  document.addEventListener('mouseup', () => {
    dragging = null;
    if (connecting) {
      connecting = null;
      if (tempLine) { tempLine.remove(); tempLine = null; }
    }
  });

  // Deselect on canvas click
  canvas.addEventListener('click', e => {
    if (e.target === canvas || e.target === svg) {
      selectedNode = null;
      if (propsPanel) propsPanel.innerHTML = '<div class="empty-state py-3"><i class="bi bi-cursor-fill fs-3"></i><p class="mb-0 small">Selecciona un nodo</p></div>';
      canvas.querySelectorAll('.diagram-node.selected').forEach(el => el.classList.remove('selected'));
    }
  });

  // ── Drag from palette ─────────────────────────────────────
  document.querySelectorAll('.diagram-palette-node').forEach(el => {
    el.addEventListener('dragstart', e => {
      e.dataTransfer.setData('node_type', el.dataset.type);
    });
  });

  canvas.addEventListener('dragover', e => e.preventDefault());

  canvas.addEventListener('drop', e => {
    e.preventDefault();
    const type  = e.dataTransfer.getData('node_type');
    if (!type) return;
    const rect  = canvas.getBoundingClientRect();
    const x     = e.clientX - rect.left - 70;
    const y     = e.clientY - rect.top  - 25;
    const labels = { start: 'Inicio', end: 'Fin', decision: 'Condición', response: 'Respuesta', action: 'Acción' };
    const node  = { id: 'node' + (nodeIdSeq++), type, label: labels[type] || type, x, y, data: {} };
    nodes.push(node);
    render();
    selectNode(node.id);
  });

  // ── Keyboard shortcuts ─────────────────────────────────────
  document.addEventListener('keydown', e => {
    if ((e.key === 'Delete' || e.key === 'Backspace') && selectedNode && document.activeElement === document.body) {
      deleteSelected();
    }
  });

  function deleteSelected() {
    if (!selectedNode) return;
    nodes       = nodes.filter(n => n.id !== selectedNode);
    connections = connections.filter(c => c.source !== selectedNode && c.target !== selectedNode);
    selectedNode = null;
    render();
    if (propsPanel) propsPanel.innerHTML = '<div class="empty-state py-3"><i class="bi bi-cursor-fill fs-3"></i><p class="mb-0 small">Selecciona un nodo</p></div>';
  }

  document.getElementById('deleteNode')?.addEventListener('click', deleteSelected);

  // ── Connection label editing ───────────────────────────────
  function editConnectionLabel(conn) {
    Swal.fire({
      title: 'Etiqueta de conexión',
      input: 'text',
      inputValue: conn.label,
      inputPlaceholder: 'Ej: Sí, No, Precio, Error...',
      showCancelButton: true,
      confirmButtonText: 'Guardar',
      cancelButtonText: 'Cancelar',
      background: 'var(--bg-card)',
      color: 'var(--text)',
    }).then(result => {
      if (result.isConfirmed) {
        conn.label = result.value;
        render();
      }
    });
  }

  // ── Zoom ───────────────────────────────────────────────────
  document.getElementById('zoomIn')?.addEventListener('click', () => {
    scale = Math.min(scale + 0.1, 2);
    applyScale();
  });
  document.getElementById('zoomOut')?.addEventListener('click', () => {
    scale = Math.max(scale - 0.1, 0.4);
    applyScale();
  });
  document.getElementById('fitCanvas')?.addEventListener('click', () => {
    scale = 1;
    applyScale();
  });

  canvas.addEventListener('wheel', e => {
    e.preventDefault();
    const delta = e.deltaY > 0 ? -0.05 : 0.05;
    scale = Math.max(0.4, Math.min(2, scale + delta));
    applyScale();
  });

  function applyScale() {
    canvas.querySelectorAll('.diagram-node').forEach(el => {
      el.style.transform = `scale(${scale})`;
      el.style.transformOrigin = 'top left';
    });
  }

  // ── Save ───────────────────────────────────────────────────
  document.getElementById('saveDiagramBtn')?.addEventListener('click', async () => {
    const id   = document.getElementById('diagramId')?.value;
    const name = document.getElementById('diagramName')?.value.trim();
    const cat  = document.getElementById('diagramCategory')?.value;

    if (!name) { toast('El nombre es obligatorio', 'warning'); return; }
    if (nodes.length === 0) { toast('Agrega al menos un nodo', 'warning'); return; }

    const fd = new FormData();
    fd.append('action',      'save');
    fd.append('id',          id);
    fd.append('name',        name);
    fd.append('category_id', cat);
    fd.append('nodes',       JSON.stringify(nodes));
    fd.append('connections', JSON.stringify(connections));
    fd.append('csrf_token',  CSRF);

    const res  = await fetch(`${APP_URL}/api/diagrams.php`, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      toast('Diagrama guardado', 'success');
    } else {
      toast(data.error || 'Error al guardar', 'error');
    }
  });

  // ── Initial render ────────────────────────────────────────
  render();
});

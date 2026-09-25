<?php
require_once __DIR__ . '/config/config.php';
auth_required();

$pageTitle    = 'Diagramas de Respuesta';
$activePage   = 'diagrams';
$breadcrumb   = 'Diagramas';
$extraScripts = ['diagram-builder.js'];

$engine     = new ResponseEngine();
$categories = $engine->getCategories();

$diagrams = Database::fetchAll(
    "SELECT d.*, rc.name AS cat_name, rc.color AS cat_color FROM diagrams d
     LEFT JOIN response_categories rc ON rc.id = d.category_id
     ORDER BY d.updated_at DESC"
);

$editId      = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$editDiagram = $editId ? Database::fetch("SELECT * FROM diagrams WHERE id = ?", [$editId]) : null;

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-4">
  <div>
    <h1 class="page-title"><i class="bi bi-diagram-3 me-2 text-primary"></i>Diagramas de Respuesta</h1>
    <p class="page-subtitle">Diseña flujos de respuesta visual para diferentes tipos de consulta</p>
  </div>
  <button class="btn btn-primary" id="newDiagramBtn">
    <i class="bi bi-plus-lg me-2"></i>Nuevo diagrama
  </button>
</div>

<?php if ($editDiagram): ?>
<!-- ── EDITOR MODE ────────────────────────────────────────────── -->
<div id="diagramEditor">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-3">
      <input type="text" class="form-control fw-600" id="diagramName" style="width:280px;font-size:16px"
             value="<?= e($editDiagram['name']) ?>" placeholder="Nombre del diagrama">
      <select class="form-select" id="diagramCategory" style="width:180px">
        <option value="">Sin categoría</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= $editDiagram['category_id'] == $cat['id'] ? 'selected' : '' ?>>
            <?= e($cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-secondary" onclick="window.location='<?= APP_URL ?>/diagrams.php'">
        <i class="bi bi-arrow-left me-1"></i>Volver
      </button>
      <button class="btn btn-primary" id="saveDiagramBtn">
        <i class="bi bi-floppy me-2"></i>Guardar
      </button>
    </div>
  </div>

  <!-- Node palette -->
  <div class="d-flex gap-2 mb-3 align-items-center flex-wrap">
    <span class="text-muted small me-1">Arrastrar al canvas:</span>
    <div class="diagram-palette-node node-start"   draggable="true" data-type="start">   <i class="bi bi-play-circle-fill me-1"></i>Inicio</div>
    <div class="diagram-palette-node node-decision" draggable="true" data-type="decision"><i class="bi bi-diamond-fill me-1"></i>Decisión</div>
    <div class="diagram-palette-node node-response" draggable="true" data-type="response"><i class="bi bi-chat-square-text-fill me-1"></i>Respuesta</div>
    <div class="diagram-palette-node node-action"   draggable="true" data-type="action">  <i class="bi bi-gear-fill me-1"></i>Acción</div>
    <div class="diagram-palette-node node-end"      draggable="true" data-type="end">     <i class="bi bi-stop-circle-fill me-1"></i>Fin</div>
    <div class="ms-auto d-flex gap-2">
      <button class="btn btn-sm btn-secondary" id="zoomIn"><i class="bi bi-zoom-in"></i></button>
      <button class="btn btn-sm btn-secondary" id="zoomOut"><i class="bi bi-zoom-out"></i></button>
      <button class="btn btn-sm btn-secondary" id="fitCanvas"><i class="bi bi-fullscreen"></i></button>
      <button class="btn btn-sm btn-secondary" id="deleteNode" title="Eliminar seleccionado"><i class="bi bi-trash text-danger"></i></button>
    </div>
  </div>

  <div class="row g-3">
    <!-- Canvas -->
    <div class="col-lg-9">
      <div id="diagramCanvas" style="height:580px;position:relative">
        <svg id="connectionsSvg" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:1">
          <defs>
            <marker id="arrowhead" markerWidth="8" markerHeight="6" refX="6" refY="3" orient="auto">
              <polygon points="0 0, 8 3, 0 6" fill="#4f46e5" opacity=".8"/>
            </marker>
          </defs>
        </svg>
        <!-- Nodes rendered here by JS -->
      </div>
    </div>

    <!-- Properties panel -->
    <div class="col-lg-3">
      <div class="card sticky-top" style="top:80px">
        <div class="card-header"><i class="bi bi-sliders me-2"></i>Propiedades</div>
        <div class="card-body" id="nodePropsPanel">
          <div class="empty-state py-3">
            <i class="bi bi-cursor-fill fs-3"></i>
            <p class="mb-0 small">Selecciona un nodo</p>
          </div>
        </div>
      </div>

      <!-- How-to -->
      <div class="card mt-3">
        <div class="card-header text-xs">Cómo usar</div>
        <div class="card-body" style="font-size:12px;color:var(--text-muted)">
          <p><kbd>Drag</kbd> Arrastrar nodos del panel</p>
          <p><kbd>Click</kbd> Seleccionar nodo</p>
          <p><kbd>Hover puerto</kbd> + drag para conectar</p>
          <p><kbd>Del</kbd> Eliminar seleccionado</p>
          <p class="mb-0"><kbd>Scroll</kbd> Zoom</p>
        </div>
      </div>
    </div>
  </div>
</div>

<input type="hidden" id="diagramId" value="<?= $editId ?>">
<input type="hidden" id="initialNodes" value="<?= e($editDiagram['nodes'] ?? '[]') ?>">
<input type="hidden" id="initialConnections" value="<?= e($editDiagram['connections'] ?? '[]') ?>">

<?php else: ?>
<!-- ── LIST MODE ─────────────────────────────────────────────── -->

<!-- Filter -->
<div class="d-flex gap-3 mb-4">
  <div class="search-bar flex-grow-1" style="max-width:320px">
    <i class="bi bi-search search-icon"></i>
    <input type="text" class="form-control" id="diagramSearch" placeholder="Buscar diagrama...">
  </div>
  <select class="form-select" id="catFilter" style="width:200px">
    <option value="">Todas las categorías</option>
    <?php foreach ($categories as $cat): ?>
      <option value="<?= e($cat['name']) ?>"><?= e($cat['name']) ?></option>
    <?php endforeach; ?>
  </select>
</div>

<div class="row g-3" id="diagramsGrid">
  <?php if (empty($diagrams)): ?>
    <div class="col-12">
      <div class="empty-state">
        <i class="bi bi-diagram-3"></i>
        <h5>Sin diagramas</h5>
        <p>Crea tu primer diagrama de flujo de respuesta</p>
        <button class="btn btn-primary" id="newDiagramBtnEmpty">
          <i class="bi bi-plus-lg me-2"></i>Crear diagrama
        </button>
      </div>
    </div>
  <?php else: ?>
    <?php foreach ($diagrams as $d): ?>
      <div class="col-md-6 col-xl-4 diagram-card-wrap" data-cat="<?= e($d['cat_name'] ?? '') ?>">
        <div class="card h-100" style="cursor:pointer" onclick="window.location='?edit=<?= $d['id'] ?>'">
          <!-- Mini diagram preview -->
          <div style="height:120px;background:#0d1020;border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:center;overflow:hidden;border-bottom:1px solid var(--border)">
            <svg viewBox="0 0 200 100" style="width:100%;opacity:.8">
              <circle cx="30" cy="50" r="15" fill="rgba(16,185,129,.3)" stroke="#10b981" stroke-width="1.5"/>
              <text x="30" y="54" text-anchor="middle" fill="#6ee7b7" font-size="8">START</text>
              <rect x="70" y="35" width="60" height="30" rx="6" fill="rgba(79,70,229,.3)" stroke="#4f46e5" stroke-width="1.5"/>
              <text x="100" y="54" text-anchor="middle" fill="#818cf8" font-size="8">Decisión</text>
              <circle cx="170" cy="50" r="15" fill="rgba(239,68,68,.3)" stroke="#ef4444" stroke-width="1.5"/>
              <text x="170" y="54" text-anchor="middle" fill="#fca5a5" font-size="8">END</text>
              <line x1="45" y1="50" x2="70" y2="50" stroke="#4f46e5" stroke-width="1" marker-end="url(#arrowhead)"/>
              <line x1="130" y1="50" x2="155" y2="50" stroke="#4f46e5" stroke-width="1" marker-end="url(#arrowhead)"/>
              <defs><marker id="arrowhead" markerWidth="6" markerHeight="4" refX="5" refY="2" orient="auto"><polygon points="0 0, 6 2, 0 4" fill="#4f46e5"/></marker></defs>
            </svg>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <?php if ($d['cat_name']): ?>
                  <span class="badge mb-2" style="background:<?= e($d['cat_color'] ?? '#4f46e5') ?>22;color:<?= e($d['cat_color'] ?? '#818cf8') ?>;font-size:10px"><?= e($d['cat_name']) ?></span>
                <?php endif; ?>
                <h6 class="fw-600 mb-1"><?= e($d['name']) ?></h6>
                <p class="text-muted small mb-0"><?= e(substr($d['description'] ?? '', 0, 60)) ?></p>
              </div>
              <div class="dropdown ms-2" onclick="event.stopPropagation()">
                <button class="btn btn-icon" data-bs-toggle="dropdown">
                  <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item" href="?edit=<?= $d['id'] ?>"><i class="bi bi-pencil me-2"></i>Editar</a></li>
                  <li><a class="dropdown-item" href="?duplicate=<?= $d['id'] ?>"><i class="bi bi-copy me-2"></i>Duplicar</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item text-danger delete-diagram" href="#" data-id="<?= $d['id'] ?>"><i class="bi bi-trash me-2"></i>Eliminar</a></li>
                </ul>
              </div>
            </div>
            <div class="d-flex justify-content-between mt-3 text-xs text-muted">
              <span><i class="bi bi-calendar me-1"></i><?= date('d/m/Y', strtotime($d['updated_at'])) ?></span>
              <span class="badge <?= $d['is_active'] ? 'badge-success' : 'badge-gray' ?>"><?= $d['is_active'] ? 'Activo' : 'Inactivo' ?></span>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- New diagram modal -->
<div class="modal fade" id="newDiagramModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-diagram-3 me-2"></i>Nuevo diagrama</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nombre del diagrama</label>
          <input type="text" class="form-control" id="newDiagName" placeholder="Ej: Flujo de soporte técnico">
        </div>
        <div class="mb-3">
          <label class="form-label">Descripción</label>
          <textarea class="form-control" id="newDiagDesc" rows="2" placeholder="Describe el propósito de este diagrama..."></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Categoría</label>
          <select class="form-select" id="newDiagCat">
            <option value="">Sin categoría</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" id="createDiagramBtn">
          <i class="bi bi-arrow-right me-2"></i>Crear y editar
        </button>
      </div>
    </div>
  </div>
</div>

<style>
.diagram-palette-node {
  padding: 6px 14px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: grab;
  user-select: none;
  white-space: nowrap;
}
.diagram-palette-node.node-start    { background:rgba(16,185,129,.2);border:1.5px solid var(--success);color:#6ee7b7; }
.diagram-palette-node.node-end      { background:rgba(239,68,68,.2); border:1.5px solid var(--danger); color:#fca5a5; }
.diagram-palette-node.node-decision { background:rgba(245,158,11,.2);border:1.5px solid var(--warning);color:#fcd34d; }
.diagram-palette-node.node-response { background:rgba(79,70,229,.2); border:1.5px solid var(--primary);color:#818cf8; }
.diagram-palette-node.node-action   { background:rgba(6,182,212,.2); border:1.5px solid var(--accent); color:#67e8f9; }
</style>

<script>
// New diagram
document.getElementById('newDiagramBtn')?.addEventListener('click', () => {
  new bootstrap.Modal(document.getElementById('newDiagramModal')).show();
});
document.getElementById('newDiagramBtnEmpty')?.addEventListener('click', () => {
  new bootstrap.Modal(document.getElementById('newDiagramModal')).show();
});

document.getElementById('createDiagramBtn')?.addEventListener('click', async () => {
  const name = document.getElementById('newDiagName').value.trim();
  if (!name) { alert('El nombre es obligatorio'); return; }
  const fd = new FormData();
  fd.append('action',      'create');
  fd.append('name',        name);
  fd.append('description', document.getElementById('newDiagDesc').value);
  fd.append('category_id', document.getElementById('newDiagCat').value);
  fd.append('csrf_token',  '<?= csrf_token() ?>');
  const res  = await fetch(`${APP_URL}/api/diagrams.php`, { method: 'POST', body: fd });
  const data = await res.json();
  if (data.id) window.location = `?edit=${data.id}`;
});

// Delete diagram
document.querySelectorAll('.delete-diagram').forEach(el => {
  el.addEventListener('click', async e => {
    e.preventDefault();
    const ok = await Swal.fire({
      title: '¿Eliminar diagrama?', icon: 'warning',
      showCancelButton: true, confirmButtonText: 'Sí, eliminar',
      confirmButtonColor: 'var(--danger)', background: 'var(--bg-card)', color: 'var(--text)',
    });
    if (!ok.isConfirmed) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id',     el.dataset.id);
    fd.append('csrf_token', '<?= csrf_token() ?>');
    await fetch(`${APP_URL}/api/diagrams.php`, { method: 'POST', body: fd });
    el.closest('.diagram-card-wrap').remove();
  });
});

// Search & filter
document.getElementById('diagramSearch')?.addEventListener('input', filterDiagrams);
document.getElementById('catFilter')?.addEventListener('change', filterDiagrams);

function filterDiagrams() {
  const q   = document.getElementById('diagramSearch')?.value.toLowerCase() ?? '';
  const cat = document.getElementById('catFilter')?.value ?? '';
  document.querySelectorAll('.diagram-card-wrap').forEach(el => {
    const text   = el.textContent.toLowerCase();
    const elCat  = el.dataset.cat;
    const showQ  = !q   || text.includes(q);
    const showC  = !cat || elCat === cat;
    el.style.display = showQ && showC ? '' : 'none';
  });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

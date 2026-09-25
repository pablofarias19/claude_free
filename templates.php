<?php
require_once 'config/config.php';
auth_required();

$activePage  = 'templates';
$mgr         = new TemplateManager();
$categories  = $mgr->getCategories();
$catId       = (int)($_GET['cat'] ?? 0);
$search      = trim($_GET['q'] ?? '');
$templates   = $mgr->getAll($catId, $search);

$pageTitle    = 'Plantillas de Email';
$extraScripts = ['assets/js/templates.js'];
include 'includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h2 class="page-title mb-1">Plantillas de Email</h2>
    <p class="text-muted mb-0"><?= count($templates) ?> plantilla<?= count($templates) != 1 ? 's' : '' ?></p>
  </div>
  <button class="btn btn-primary" onclick="openTemplateModal()">
    <i class="bi bi-plus-lg me-2"></i>Nueva Plantilla
  </button>
</div>

<div class="row g-4">
  <!-- Sidebar filters -->
  <div class="col-md-3">
    <div class="card">
      <div class="card-body">
        <form method="GET">
          <div class="mb-3">
            <input type="search" name="q" class="form-control" placeholder="Buscar…" value="<?= e($search) ?>">
            <input type="hidden" name="cat" value="<?= $catId ?>">
          </div>
          <button class="btn btn-primary btn-sm w-100">Buscar</button>
        </form>
        <hr style="border-color:var(--border)">
        <p class="text-muted mb-2" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.05em">Categorías</p>
        <ul class="list-unstyled mb-0">
          <li>
            <a href="templates.php?q=<?= urlencode($search) ?>" class="d-flex justify-content-between py-1 px-2 rounded text-decoration-none <?= !$catId ? 'bg-primary' : 'text-muted' ?>">
              <span>Todas</span>
              <span class="badge bg-secondary"><?= count($templates) ?></span>
            </a>
          </li>
          <?php foreach ($categories as $cat): ?>
          <li>
            <a href="templates.php?cat=<?= $cat['id'] ?>&q=<?= urlencode($search) ?>"
               class="d-flex justify-content-between py-1 px-2 rounded text-decoration-none <?= $catId===$cat['id'] ? 'bg-primary' : 'text-muted' ?>">
              <span><?= e($cat['name']) ?></span>
              <span class="badge bg-secondary"><?= $cat['template_count'] ?? 0 ?></span>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>

  <!-- Templates grid -->
  <div class="col-md-9">
    <?php if (empty($templates)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-file-earmark-text" style="font-size:3rem;opacity:.4"></i>
        <p class="mt-2">No hay plantillas. <button class="btn btn-sm btn-primary" onclick="openTemplateModal()">Crear primera</button></p>
      </div>
    <?php else: ?>
      <div class="row g-3" id="tplGrid">
        <?php foreach ($templates as $tpl): ?>
        <div class="col-md-6 col-xl-4">
          <div class="card h-100 tpl-card" style="cursor:pointer" onclick="previewTemplate(<?= $tpl['id'] ?>)">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="badge bg-info text-dark"><?= e($tpl['category_name'] ?? 'Sin categoría') ?></span>
                <div class="dropdown" onclick="event.stopPropagation()">
                  <button class="btn btn-link btn-sm text-muted p-0" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="compose.php?tpl=<?= $tpl['id'] ?>"><i class="bi bi-send me-2"></i>Usar</a></li>
                    <li><a class="dropdown-item" href="#" onclick="editTemplate(<?= $tpl['id'] ?>)"><i class="bi bi-pencil me-2"></i>Editar</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteTemplate(<?= $tpl['id'] ?>)"><i class="bi bi-trash me-2"></i>Eliminar</a></li>
                  </ul>
                </div>
              </div>
              <h6 class="fw-700 mb-1"><?= e($tpl['name']) ?></h6>
              <p class="text-muted mb-3" style="font-size:.8rem;line-height:1.4"><?= e(mb_strimwidth($tpl['subject'] ?? '', 0, 60, '…')) ?></p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted" style="font-size:.75rem">
                  <i class="bi bi-arrow-repeat me-1"></i><?= $tpl['usage_count'] ?? 0 ?> usos
                </span>
                <?php if (!empty($tpl['variables'])): ?>
                  <?php $vars = json_decode($tpl['variables'], true) ?? []; ?>
                  <span class="badge bg-secondary"><?= count($vars) ?> var<?= count($vars) != 1 ? 's' : '' ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Template Modal -->
<div class="modal fade" id="tplModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content bg-dark border" style="border-color:var(--border)!important">
      <div class="modal-header border-bottom" style="border-color:var(--border)!important">
        <h5 class="modal-title" id="tplModalTitle">Nueva Plantilla</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="tplForm">
          <input type="hidden" id="tplId" name="id">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Nombre de la plantilla *</label>
              <input type="text" class="form-control" id="tplName" name="name" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Categoría</label>
              <select class="form-select" id="tplCat" name="category_id">
                <option value="">Sin categoría</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Asunto *</label>
              <input type="text" class="form-control" id="tplSubject" name="subject" placeholder="Hola {{nombre}}, …" required>
            </div>
            <div class="col-12">
              <label class="form-label">Cuerpo del email (HTML)</label>
              <div id="tplEditor" style="height:320px;border-radius:8px;overflow:hidden"></div>
              <textarea id="tplBody" name="body" style="display:none"></textarea>
              <small class="text-muted">Usa <code>&#123;&#123;variable&#125;&#125;</code> para insertar variables dinámicas</small>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-top" style="border-color:var(--border)!important">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="saveTemplate()">
          <i class="bi bi-floppy me-2"></i>Guardar Plantilla
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content bg-dark border" style="border-color:var(--border)!important">
      <div class="modal-header border-bottom" style="border-color:var(--border)!important">
        <h5 class="modal-title">Vista previa</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <iframe id="previewFrame" style="width:100%;min-height:500px;border:none;background:#fff"></iframe>
      </div>
      <div class="modal-footer border-top" style="border-color:var(--border)!important">
        <button id="useTemplateBtn" class="btn btn-primary">
          <i class="bi bi-send me-2"></i>Usar esta plantilla
        </button>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

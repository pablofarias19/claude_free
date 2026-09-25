<?php
require_once 'config/config.php';
auth_required();

$activePage = 'chat_widget';
$widgets = Database::fetchAll("SELECT * FROM chat_widgets ORDER BY created_at DESC");

$pageTitle    = 'Widget de Chat';
$extraScripts = ['assets/js/chat_widget.js'];
include 'includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h2 class="page-title mb-1">Widget de Chat</h2>
    <p class="text-muted mb-0">Integración en sitios web externos</p>
  </div>
  <button class="btn btn-primary" onclick="openWidgetModal()">
    <i class="bi bi-plus-lg me-2"></i>Nuevo Widget
  </button>
</div>

<!-- Widgets list -->
<?php if (empty($widgets)): ?>
  <div class="text-center py-5 text-muted">
    <i class="bi bi-chat-dots" style="font-size:3rem;opacity:.4"></i>
    <p class="mt-2">No hay widgets creados</p>
    <button class="btn btn-primary" onclick="openWidgetModal()">Crear primer widget</button>
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($widgets as $w): ?>
    <div class="col-md-6 col-xl-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h6 class="fw-700 mb-1"><?= e($w['name']) ?></h6>
              <span class="badge <?= $w['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                <?= $w['is_active'] ? 'Activo' : 'Inactivo' ?>
              </span>
            </div>
            <div class="dropdown">
              <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown">
                <i class="bi bi-three-dots-vertical"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" onclick="editWidget(<?= htmlspecialchars(json_encode($w)) ?>)"><i class="bi bi-pencil me-2"></i>Editar</a></li>
                <li><a class="dropdown-item" href="#" onclick="showEmbedCode('<?= e($w['api_key']) ?>')"><i class="bi bi-code-slash me-2"></i>Código de integración</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="#" onclick="deleteWidget(<?= $w['id'] ?>)"><i class="bi bi-trash me-2"></i>Eliminar</a></li>
              </ul>
            </div>
          </div>

          <!-- Preview -->
          <div class="rounded-3 p-3 mb-3 d-flex align-items-center justify-content-center" style="background:<?= e($w['primary_color'] ?? '#4f46e5') ?>22;min-height:80px">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white"
                 style="width:48px;height:48px;background:<?= e($w['primary_color'] ?? '#4f46e5') ?>;font-size:22px;box-shadow:0 4px 16px rgba(0,0,0,.3)">
              💬
            </div>
          </div>

          <div class="mb-2">
            <div class="text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em">Color primario</div>
            <div class="d-flex align-items-center gap-2 mt-1">
              <div class="rounded" style="width:20px;height:20px;background:<?= e($w['primary_color'] ?? '#4f46e5') ?>"></div>
              <code style="font-size:.85rem"><?= e($w['primary_color'] ?? '#4f46e5') ?></code>
            </div>
          </div>
          <div class="mb-3">
            <div class="text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em">Posición</div>
            <div class="mt-1"><?= e($w['position'] ?? 'bottom-right') ?></div>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm flex-grow-1" onclick="showEmbedCode('<?= e($w['api_key']) ?>')">
              <i class="bi bi-code-slash me-1"></i>Código
            </button>
            <button class="btn btn-outline-secondary btn-sm" onclick="previewWidget(<?= htmlspecialchars(json_encode($w)) ?>)">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Widget Modal -->
<div class="modal fade" id="widgetModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content bg-dark border" style="border-color:var(--border)!important">
      <div class="modal-header border-bottom" style="border-color:var(--border)!important">
        <h5 class="modal-title" id="widgetModalTitle">Nuevo Widget</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="widgetForm">
          <input type="hidden" id="widgetId" name="id">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nombre del widget *</label>
              <input type="text" class="form-control" id="wName" name="name" required placeholder="Mi Sitio Web">
            </div>
            <div class="col-md-6">
              <label class="form-label">Color primario</label>
              <div class="input-group">
                <input type="color" class="form-control form-control-color" id="wColorPicker" value="#4f46e5" style="width:50px;padding:4px">
                <input type="text" class="form-control" id="wColor" name="primary_color" value="#4f46e5" maxlength="7">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Posición</label>
              <select class="form-select" id="wPosition" name="position">
                <option value="bottom-right">Abajo derecha</option>
                <option value="bottom-left">Abajo izquierda</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Estado</label>
              <select class="form-select" id="wActive" name="is_active">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Dominios permitidos <small class="text-muted">(opcional, uno por línea)</small></label>
              <textarea class="form-control" id="wDomains" name="allowed_domains" rows="3" placeholder="miempresa.com&#10;app.miempresa.com"></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-top" style="border-color:var(--border)!important">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="saveWidget()">
          <i class="bi bi-floppy me-2"></i>Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Embed Code Modal -->
<div class="modal fade" id="embedModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content bg-dark border" style="border-color:var(--border)!important">
      <div class="modal-header border-bottom" style="border-color:var(--border)!important">
        <h5 class="modal-title">Código de integración</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted">Pega este código antes del cierre de <code>&lt;/body&gt;</code> en tu sitio web:</p>
        <div class="position-relative">
          <pre class="p-3 rounded" id="embedCode" style="background:#0f172a;font-size:.85rem;overflow-x:auto"></pre>
          <button class="btn btn-sm btn-outline-primary position-absolute top-0 end-0 m-2" onclick="copyEmbed()">
            <i class="bi bi-clipboard me-1"></i>Copiar
          </button>
        </div>
        <div class="alert alert-info bg-info bg-opacity-10 border-info mt-3" style="border-color:rgba(6,182,212,.3)!important;font-size:.85rem">
          <i class="bi bi-info-circle me-2"></i>
          El widget se cargará automáticamente en la esquina de tu página. Los visitantes podrán chatear sin iniciar sesión.
        </div>
      </div>
    </div>
  </div>
</div>

<script>const APP_URL = '<?= defined('APP_URL') ? APP_URL : '' ?>';</script>
<?php include 'includes/footer.php'; ?>

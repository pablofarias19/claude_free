<?php
require_once __DIR__ . '/config/config.php';
auth_required();

$pageTitle    = 'Redactar Email';
$activePage   = 'compose';
$breadcrumb   = 'Redactar';
$extraScripts = ['composer.js'];

$tplMgr   = new TemplateManager();
$templates = $tplMgr->getAll();

// Load draft if editing
$draft = null;
if (!empty($_GET['id'])) {
    $emailMgr = new EmailManager();
    $draft    = $emailMgr->getEmail((int)$_GET['id']);
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-4">
  <div>
    <h1 class="page-title"><i class="bi bi-pencil-square me-2 text-primary"></i>Redactar Email</h1>
    <p class="page-subtitle">Compose, adjunta multimedia y programa el envío</p>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-secondary" id="saveDraftBtn">
      <i class="bi bi-cloud me-2"></i>Guardar borrador
    </button>
    <button class="btn btn-primary" id="sendNowBtn">
      <i class="bi bi-send me-2"></i>Enviar ahora
    </button>
    <button class="btn btn-outline-primary" id="scheduleBtn" data-bs-toggle="modal" data-bs-target="#scheduleModal">
      <i class="bi bi-calendar-plus me-2"></i>Programar
    </button>
  </div>
</div>

<div class="row g-4">
  <!-- Compose area -->
  <div class="col-lg-8">
    <form id="composeForm" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="email_id" id="emailId" value="<?= $draft['id'] ?? '' ?>">

      <div class="composer-header">
        <!-- To -->
        <div class="recipient-field">
          <span class="recipient-label">Para</span>
          <div class="flex-grow-1">
            <div class="tags-input" id="toField">
              <input type="text" id="toInput" placeholder="email@ejemplo.com" autocomplete="off">
            </div>
            <input type="hidden" name="to" id="toHidden" value="<?= e(implode(',', json_decode($draft['to_emails'] ?? '[]', true))) ?>">
          </div>
          <div class="d-flex gap-2 ms-2">
            <button type="button" class="btn btn-sm btn-secondary" id="toggleCc">CC</button>
            <button type="button" class="btn btn-sm btn-secondary" id="toggleBcc">BCC</button>
          </div>
        </div>

        <!-- CC -->
        <div class="recipient-field" id="ccRow" style="display:none">
          <span class="recipient-label">CC</span>
          <div class="flex-grow-1">
            <div class="tags-input" id="ccField">
              <input type="text" id="ccInput" placeholder="cc@ejemplo.com">
            </div>
            <input type="hidden" name="cc" id="ccHidden">
          </div>
        </div>

        <!-- BCC -->
        <div class="recipient-field" id="bccRow" style="display:none">
          <span class="recipient-label">BCC</span>
          <div class="flex-grow-1">
            <div class="tags-input" id="bccField">
              <input type="text" id="bccInput" placeholder="bcc@ejemplo.com">
            </div>
            <input type="hidden" name="bcc" id="bccHidden">
          </div>
        </div>

        <!-- Subject -->
        <div class="recipient-field">
          <span class="recipient-label">Asunto</span>
          <input type="text" name="subject" class="form-control border-0 bg-transparent px-0 flex-grow-1"
                 placeholder="Escribe el asunto del email..."
                 value="<?= e($draft['subject'] ?? '') ?>"
                 id="subjectInput" style="box-shadow:none">
        </div>

        <!-- From -->
        <div class="recipient-field">
          <span class="recipient-label">De</span>
          <input type="email" name="from_email" class="form-control border-0 bg-transparent px-0 flex-grow-1"
                 value="<?= e($draft['from_email'] ?? MAIL_FROM_EMAIL) ?>"
                 style="box-shadow:none">
        </div>

        <!-- Priority -->
        <div class="recipient-field border-0">
          <span class="recipient-label">Prioridad</span>
          <select name="priority" class="form-select form-select-sm w-auto border-0 bg-transparent" style="box-shadow:none">
            <option value="normal" <?= ($draft['priority'] ?? 'normal') === 'normal'  ? 'selected' : '' ?>>Normal</option>
            <option value="high"   <?= ($draft['priority'] ?? '') === 'high'   ? 'selected' : '' ?>>Alta</option>
            <option value="urgent" <?= ($draft['priority'] ?? '') === 'urgent' ? 'selected' : '' ?>>Urgente</option>
            <option value="low"    <?= ($draft['priority'] ?? '') === 'low'    ? 'selected' : '' ?>>Baja</option>
          </select>
        </div>
      </div>

      <div class="composer-body">
        <!-- Rich Text Editor -->
        <div id="quillEditor"><?= $draft['body_html'] ?? '' ?></div>
        <input type="hidden" name="body_html" id="bodyHtml">

        <!-- Attachments -->
        <div class="mt-3 d-flex align-items-center gap-3">
          <label class="btn btn-secondary btn-sm" for="attachInput">
            <i class="bi bi-paperclip me-1"></i>Adjuntar archivo
          </label>
          <label class="btn btn-secondary btn-sm" for="imageInput">
            <i class="bi bi-image me-1"></i>Imagen
          </label>
          <label class="btn btn-secondary btn-sm" for="videoInput">
            <i class="bi bi-camera-video me-1"></i>Video
          </label>
          <input type="file" id="attachInput" name="attachments[]" multiple hidden accept="*/*">
          <input type="file" id="imageInput" name="attachments[]" multiple hidden accept="image/*">
          <input type="file" id="videoInput" name="attachments[]" hidden accept="video/*">
        </div>
        <div class="attach-list" id="attachList"></div>
      </div>
    </form>
  </div>

  <!-- Sidebar tools -->
  <div class="col-lg-4">
    <!-- Templates -->
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-file-earmark-richtext me-2"></i>Plantillas</div>
      <div class="card-body p-0">
        <div class="p-3">
          <div class="search-bar mb-3">
            <i class="bi bi-search search-icon"></i>
            <input type="text" class="form-control" id="tplSearch" placeholder="Buscar plantilla...">
          </div>
        </div>
        <div id="tplList" style="max-height:320px;overflow-y:auto">
          <?php foreach ($templates as $tpl): ?>
            <div class="tpl-item p-3 border-bottom" style="border-color:var(--border)!important;cursor:pointer"
                 data-id="<?= $tpl['id'] ?>" data-name="<?= e($tpl['name']) ?>">
              <div class="d-flex align-items-center gap-2">
                <span class="badge" style="background:<?= e($tpl['category_color'] ?? '#4f46e5') ?>22;color:<?= e($tpl['category_color'] ?? '#818cf8') ?>;font-size:10px"><?= e($tpl['category_name'] ?? '') ?></span>
              </div>
              <div class="fw-600 small mt-1"><?= e($tpl['name']) ?></div>
              <div class="text-xs text-muted"><?= e(substr($tpl['subject'], 0, 50)) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($templates)): ?>
            <div class="p-3 text-center text-muted small">No hay plantillas</div>
          <?php endif; ?>
        </div>
        <div class="p-3 border-top" style="border-color:var(--border)!important">
          <a href="<?= APP_URL ?>/templates.php" class="btn btn-sm btn-secondary w-100">
            <i class="bi bi-plus me-1"></i>Gestionar plantillas
          </a>
        </div>
      </div>
    </div>

    <!-- Quick preview -->
    <div class="card">
      <div class="card-header"><i class="bi bi-eye me-2"></i>Vista previa</div>
      <div class="card-body">
        <div id="previewContainer" style="background:#fff;border-radius:8px;padding:16px;min-height:100px;color:#333;font-size:13px">
          <em class="text-muted">Escribe el email para previsualizar...</em>
        </div>
        <div class="mt-2 d-flex gap-2">
          <button class="btn btn-sm btn-secondary w-100" id="previewDesktop">
            <i class="bi bi-laptop me-1"></i>Desktop
          </button>
          <button class="btn btn-sm btn-secondary w-100" id="previewMobile">
            <i class="bi bi-phone me-1"></i>Móvil
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-calendar-plus me-2"></i>Programar envío</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Fecha y hora de envío</label>
          <input type="text" id="scheduleDateInput" class="form-control" placeholder="Selecciona fecha y hora" readonly>
        </div>
        <div class="mb-3">
          <label class="form-label">Zona horaria</label>
          <select class="form-select" id="scheduleTimezone">
            <option value="America/Buenos_Aires">Argentina (GMT-3)</option>
            <option value="America/Mexico_City">México (GMT-6)</option>
            <option value="America/Bogota">Colombia (GMT-5)</option>
            <option value="Europe/Madrid">España (GMT+1)</option>
            <option value="UTC">UTC</option>
          </select>
        </div>
        <div class="alert" style="background:rgba(79,70,229,.1);border:1px solid rgba(79,70,229,.3);border-radius:8px;font-size:13px">
          <i class="bi bi-info-circle me-2"></i>El email se añadirá a la cola y se enviará automáticamente en la fecha y hora especificada.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" id="confirmScheduleBtn">
          <i class="bi bi-calendar-check me-2"></i>Confirmar programación
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Template Variables Modal -->
<div class="modal fade" id="tplVarsModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Variables de plantilla</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="tplVarsBody"></div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" id="applyTplBtn">Aplicar plantilla</button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

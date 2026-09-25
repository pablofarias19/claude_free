<?php
require_once 'config/config.php';
auth_required();

$activePage = 'chat_bot';
$botResponses = Database::fetchAll("SELECT * FROM chat_bot_responses ORDER BY priority DESC, trigger_word ASC");
$cannedReplies = Database::fetchAll("SELECT * FROM chat_canned ORDER BY title ASC");

$pageTitle   = 'Bot de Chat';
$extraScripts = ['assets/js/chat_bot.js'];
include 'includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h2 class="page-title mb-1">Configuración del Bot de Chat</h2>
    <p class="text-muted mb-0">Respuestas automáticas e IA conversacional</p>
  </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="botTabs">
  <li class="nav-item">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabBot">
      <i class="bi bi-robot me-2"></i>Respuestas del Bot
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabCanned">
      <i class="bi bi-chat-quote me-2"></i>Respuestas Enlatadas
    </button>
  </li>
</ul>

<div class="tab-content">
  <!-- Bot Responses -->
  <div class="tab-pane fade show active" id="tabBot">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <p class="text-muted mb-0">El bot responde automáticamente cuando el mensaje contiene las palabras clave configuradas.</p>
      <button class="btn btn-primary btn-sm" onclick="openBotModal()">
        <i class="bi bi-plus-lg me-2"></i>Nueva respuesta
      </button>
    </div>
    <div class="card">
      <div class="card-body p-0">
        <?php if (empty($botResponses)): ?>
          <div class="text-center py-4 text-muted">No hay respuestas configuradas.</div>
        <?php else: ?>
          <table class="table table-dark table-hover mb-0 align-middle">
            <thead>
              <tr>
                <th class="ps-4">Palabra clave</th>
                <th>Respuesta</th>
                <th>Respuestas rápidas</th>
                <th>Prioridad</th>
                <th>Estado</th>
                <th class="text-end pe-4">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($botResponses as $bot): ?>
              <?php $qr = json_decode($bot['quick_replies'] ?? '[]', true); ?>
              <tr>
                <td class="ps-4">
                  <code class="text-warning"><?= e($bot['trigger_word']) ?></code>
                  <div class="text-muted" style="font-size:.78rem">exacto: <?= $bot['is_exact_match'] ? 'sí' : 'no' ?></div>
                </td>
                <td>
                  <div class="text-truncate" style="max-width:300px"><?= e($bot['response_text']) ?></div>
                </td>
                <td>
                  <?php if (!empty($qr)): ?>
                    <?php foreach ($qr as $q): ?>
                      <span class="badge bg-secondary me-1"><?= e($q['text']) ?></span>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge bg-secondary"><?= $bot['priority'] ?></span>
                </td>
                <td>
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" <?= $bot['is_active'] ? 'checked' : '' ?>
                           onchange="toggleBotResponse(<?= $bot['id'] ?>, this.checked)">
                  </div>
                </td>
                <td class="text-end pe-4">
                  <button class="btn btn-sm btn-outline-primary" onclick="editBotResponse(<?= htmlspecialchars(json_encode($bot)) ?>)">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger ms-1" onclick="deleteBotResponse(<?= $bot['id'] ?>)">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Canned Replies -->
  <div class="tab-pane fade" id="tabCanned">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <p class="text-muted mb-0">Respuestas rápidas para los agentes humanos durante el chat en vivo.</p>
      <button class="btn btn-primary btn-sm" onclick="openCannedModal()">
        <i class="bi bi-plus-lg me-2"></i>Nueva respuesta
      </button>
    </div>
    <div class="card">
      <div class="card-body p-0">
        <?php if (empty($cannedReplies)): ?>
          <div class="text-center py-4 text-muted">No hay respuestas enlatadas.</div>
        <?php else: ?>
          <table class="table table-dark table-hover mb-0 align-middle">
            <thead>
              <tr>
                <th class="ps-4">Título / Atajo</th>
                <th>Contenido</th>
                <th class="text-end pe-4">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cannedReplies as $cr): ?>
              <tr>
                <td class="ps-4">
                  <div class="fw-600"><?= e($cr['title']) ?></div>
                  <?php if ($cr['shortcut']): ?>
                    <code class="text-info" style="font-size:.78rem">/<?= e($cr['shortcut']) ?></code>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="text-truncate" style="max-width:400px"><?= e($cr['content']) ?></div>
                </td>
                <td class="text-end pe-4">
                  <button class="btn btn-sm btn-outline-primary" onclick="editCanned(<?= htmlspecialchars(json_encode($cr)) ?>)">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger ms-1" onclick="deleteCanned(<?= $cr['id'] ?>)">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Bot Response Modal -->
<div class="modal fade" id="botModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content bg-dark border" style="border-color:var(--border)!important">
      <div class="modal-header border-bottom" style="border-color:var(--border)!important">
        <h5 class="modal-title" id="botModalTitle">Nueva Respuesta del Bot</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="botForm">
          <input type="hidden" id="botId" name="id">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Palabra/frase clave *</label>
              <input type="text" class="form-control" id="botTrigger" name="trigger_word" required placeholder="hola, precio, soporte…">
            </div>
            <div class="col-md-3">
              <label class="form-label">Prioridad</label>
              <input type="number" class="form-control" id="botPriority" name="priority" value="1" min="1" max="100">
            </div>
            <div class="col-md-3 d-flex align-items-end">
              <div class="form-check">
                <input type="checkbox" class="form-check-input" id="botExact" name="is_exact_match">
                <label class="form-check-label" for="botExact">Coincidencia exacta</label>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Respuesta del bot *</label>
              <textarea class="form-control" id="botResponse" name="response_text" rows="4" required placeholder="Hola, bienvenido a nuestro servicio de soporte…"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Respuestas rápidas (una por línea, formato: texto|valor)</label>
              <textarea class="form-control" id="botQR" rows="3" placeholder="Ver precios|precios&#10;Hablar con agente|agente&#10;Conocer más|info"></textarea>
              <small class="text-muted">Ejemplo: <code>Ver precios|precios</code> — mostrará el botón "Ver precios" y enviará "precios" al hacer clic</small>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-top" style="border-color:var(--border)!important">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="saveBotResponse()">
          <i class="bi bi-floppy me-2"></i>Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Canned Modal -->
<div class="modal fade" id="cannedModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content bg-dark border" style="border-color:var(--border)!important">
      <div class="modal-header border-bottom" style="border-color:var(--border)!important">
        <h5 class="modal-title" id="cannedModalTitle">Nueva Respuesta Enlatada</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="cannedForm">
          <input type="hidden" id="cannedId" name="id">
          <div class="mb-3">
            <label class="form-label">Título *</label>
            <input type="text" class="form-control" id="cannedTitle" name="title" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Atajo (sin /)</label>
            <div class="input-group">
              <span class="input-group-text text-muted">/</span>
              <input type="text" class="form-control" id="cannedShortcut" name="shortcut" placeholder="saludo">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Contenido *</label>
            <textarea class="form-control" id="cannedContent" name="content" rows="4" required></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer border-top" style="border-color:var(--border)!important">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="saveCanned()">
          <i class="bi bi-floppy me-2"></i>Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<?php
require_once 'config/config.php';
auth_required();

// Auto-migrate: add category column if missing
try {
    Database::query("ALTER TABLE chat_bot_responses ADD COLUMN category VARCHAR(100) DEFAULT 'General' AFTER is_active");
} catch (Exception $e) { /* already exists */ }

// Fetch all bot responses
$botResponses = Database::fetchAll(
    "SELECT * FROM chat_bot_responses ORDER BY category ASC, priority DESC, trigger_word ASC"
);

// Build category map with counts
$categories = [];
foreach ($botResponses as $r) {
    $cat = $r['category'] ?: 'General';
    if (!isset($categories[$cat])) $categories[$cat] = 0;
    $categories[$cat]++;
}
arsort($categories); // most populated first

// Canned replies
$cannedReplies = Database::fetchAll("SELECT * FROM chat_canned ORDER BY category ASC, title ASC");
$cannedCats = [];
foreach ($cannedReplies as $c) {
    $cat = $c['category'] ?: 'General';
    if (!isset($cannedCats[$cat])) $cannedCats[$cat] = 0;
    $cannedCats[$cat]++;
}

// Stats
$totalActive = count(array_filter($botResponses, fn($r) => $r['is_active']));

$activePage   = 'content_manager';
$pageTitle    = 'Gestor de Contenido';
$extraScripts = ['assets/js/content_manager.js'];
include 'includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
  <div>
    <h2 class="page-title mb-1">Gestor de Contenido</h2>
    <p class="text-muted mb-0">Organiza y administra todas las respuestas del bot y agentes</p>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
      <i class="bi bi-upload me-2"></i>Importar masivo
    </button>
    <button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#addPanel">
      <i class="bi bi-plus-lg me-2"></i>Nueva respuesta
    </button>
  </div>
</div>

<!-- Stats row -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card p-3 text-center">
      <div class="h2 mb-0 text-primary fw-bold"><?= count($botResponses) ?></div>
      <div class="small text-muted mt-1">Respuestas del bot</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card p-3 text-center">
      <div class="h2 mb-0 text-success fw-bold"><?= $totalActive ?></div>
      <div class="small text-muted mt-1">Activas</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card p-3 text-center">
      <div class="h2 mb-0 text-warning fw-bold"><?= count($categories) ?></div>
      <div class="small text-muted mt-1">Categorías</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card p-3 text-center">
      <div class="h2 mb-0 text-info fw-bold"><?= count($cannedReplies) ?></div>
      <div class="small text-muted mt-1">Respuestas enlatadas</div>
    </div>
  </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-0" id="cmTabs">
  <li class="nav-item">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabBot">
      <i class="bi bi-robot me-2"></i>Bot Respuestas
      <span class="badge bg-primary ms-1"><?= count($botResponses) ?></span>
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabCanned">
      <i class="bi bi-chat-quote me-2"></i>Enlatadas
      <span class="badge bg-secondary ms-1"><?= count($cannedReplies) ?></span>
    </button>
  </li>
</ul>

<div class="tab-content">

  <!-- ─────────── BOT RESPONSES ─────────── -->
  <div class="tab-pane fade show active" id="tabBot">
    <div class="card border-top-0 rounded-top-0 p-3 mb-3" style="border-top-left-radius:0!important;border-top-right-radius:0!important">
      <div class="d-flex gap-2 flex-wrap align-items-center">
        <!-- Category filter pills -->
        <button class="btn btn-sm btn-primary cat-filter active" data-cat="__all__">
          Todas <span class="badge bg-white text-dark ms-1"><?= count($botResponses) ?></span>
        </button>
        <?php
        $catColors = ['Saludo'=>'success','Precios'=>'warning','Servicios'=>'info','Soporte'=>'danger','Lead'=>'purple','FAQ'=>'secondary','General'=>'dark'];
        foreach ($categories as $cat => $cnt):
            $color = $catColors[$cat] ?? 'secondary';
        ?>
        <button class="btn btn-sm btn-outline-<?= $color ?> cat-filter" data-cat="<?= e($cat) ?>">
          <?= e($cat) ?> <span class="badge bg-<?= $color ?> ms-1"><?= $cnt ?></span>
        </button>
        <?php endforeach; ?>
        <!-- Search -->
        <div class="ms-auto d-flex gap-2 align-items-center">
          <input type="search" class="form-control form-control-sm" id="botSearch" placeholder="Buscar respuesta…" style="width:200px">
          <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-secondary active" id="viewCards" title="Tarjetas">
              <i class="bi bi-grid-3x3-gap"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary" id="viewTable" title="Tabla">
              <i class="bi bi-table"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Cards view -->
    <div id="cardsView">
      <?php if (empty($botResponses)): ?>
        <div class="text-center py-5 text-muted">
          <i class="bi bi-robot fs-1 d-block mb-3 opacity-25"></i>
          No hay respuestas. Haz clic en <strong>Nueva respuesta</strong> para comenzar.
        </div>
      <?php else: ?>
        <div class="row g-3" id="botCardsGrid">
          <?php foreach ($botResponses as $r):
            $qr  = json_decode($r['quick_replies'] ?? '[]', true) ?: [];
            $cat = $r['category'] ?: 'General';
            $color = $catColors[$cat] ?? 'secondary';
          ?>
          <div class="col-12 col-md-6 col-xl-4 bot-card" data-cat="<?= e($cat) ?>" data-search="<?= e(strtolower($r['trigger_word'] . ' ' . $r['response_text'])) ?>">
            <div class="card h-100 <?= $r['is_active'] ? '' : 'opacity-50' ?>">
              <div class="card-body pb-2">
                <!-- Header row -->
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div class="d-flex gap-2 align-items-center flex-wrap">
                    <span class="badge bg-<?= $color ?> text-white"><?= e($cat) ?></span>
                    <?php if ($r['priority'] > 1): ?>
                      <span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1"></i><?= $r['priority'] ?></span>
                    <?php endif; ?>
                    <?php if ($r['is_exact_match'] ?? false): ?>
                      <span class="badge bg-dark border border-secondary" title="Coincidencia exacta">Exacto</span>
                    <?php endif; ?>
                  </div>
                  <div class="form-check form-switch mb-0 ms-2" title="Activar/desactivar">
                    <input class="form-check-input" type="checkbox" role="switch"
                           <?= $r['is_active'] ? 'checked' : '' ?>
                           onchange="toggleBotCM(<?= $r['id'] ?>, this.checked)">
                  </div>
                </div>
                <!-- Keyword -->
                <div class="fw-bold text-primary mb-1" style="font-size:.95rem">
                  <i class="bi bi-key me-1 opacity-50"></i><?= e($r['trigger_word']) ?>
                </div>
                <!-- Response preview -->
                <p class="text-muted mb-2" style="font-size:.82rem;line-height:1.5;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden">
                  <?= e($r['response_text']) ?>
                </p>
                <!-- Quick replies chips -->
                <?php if (!empty($qr)): ?>
                  <div class="d-flex flex-wrap gap-1 mb-2">
                    <?php foreach (array_slice($qr, 0, 4) as $btn): ?>
                      <span class="badge rounded-pill border border-secondary text-muted" style="font-size:.75rem"><?= e($btn['text'] ?? '') ?></span>
                    <?php endforeach; ?>
                    <?php if (count($qr) > 4): ?>
                      <span class="badge rounded-pill bg-secondary" style="font-size:.75rem">+<?= count($qr) - 4 ?></span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
              <div class="card-footer d-flex justify-content-end gap-1 py-2">
                <button class="btn btn-xs btn-outline-primary" onclick="editBotCM(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)" title="Editar">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-xs btn-outline-danger" onclick="deleteBotCM(<?= $r['id'] ?>)" title="Eliminar">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Table view (hidden by default) -->
    <div id="tableView" class="d-none">
      <div class="card">
        <div class="card-body p-0">
          <table class="table table-dark table-hover mb-0 align-middle" id="botTable">
            <thead>
              <tr>
                <th class="ps-4">Categoría</th>
                <th>Palabra clave</th>
                <th>Respuesta</th>
                <th>Botones rápidos</th>
                <th>Prioridad</th>
                <th>Estado</th>
                <th class="text-end pe-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($botResponses as $r):
                $qr  = json_decode($r['quick_replies'] ?? '[]', true) ?: [];
                $cat = $r['category'] ?: 'General';
                $color = $catColors[$cat] ?? 'secondary';
              ?>
              <tr class="bot-row" data-cat="<?= e($cat) ?>" data-search="<?= e(strtolower($r['trigger_word'] . ' ' . $r['response_text'])) ?>">
                <td class="ps-4"><span class="badge bg-<?= $color ?>"><?= e($cat) ?></span></td>
                <td><code class="text-primary"><?= e($r['trigger_word']) ?></code></td>
                <td class="text-muted" style="max-width:260px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;font-size:.85rem"><?= e($r['response_text']) ?></td>
                <td><span class="text-muted small"><?= count($qr) ?> botón<?= count($qr) !== 1 ? 'es' : '' ?></span></td>
                <td><?= $r['priority'] ?></td>
                <td>
                  <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch"
                           <?= $r['is_active'] ? 'checked' : '' ?>
                           onchange="toggleBotCM(<?= $r['id'] ?>, this.checked)">
                  </div>
                </td>
                <td class="text-end pe-3">
                  <button class="btn btn-xs btn-outline-primary me-1" onclick="editBotCM(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-xs btn-outline-danger" onclick="deleteBotCM(<?= $r['id'] ?>)">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ─────────── CANNED REPLIES ─────────── -->
  <div class="tab-pane fade" id="tabCanned">
    <div class="card border-top-0 rounded-top-0 p-3 mb-3" style="border-top-left-radius:0!important;border-top-right-radius:0!important">
      <div class="d-flex gap-2 flex-wrap align-items-center">
        <button class="btn btn-sm btn-primary canned-filter active" data-cat="__all__">
          Todas <span class="badge bg-white text-dark ms-1"><?= count($cannedReplies) ?></span>
        </button>
        <?php foreach ($cannedCats as $cat => $cnt): ?>
        <button class="btn btn-sm btn-outline-secondary canned-filter" data-cat="<?= e($cat) ?>">
          <?= e($cat) ?> <span class="badge bg-secondary ms-1"><?= $cnt ?></span>
        </button>
        <?php endforeach; ?>
        <div class="ms-auto">
          <input type="search" class="form-control form-control-sm" id="cannedSearch" placeholder="Buscar enlatada…" style="width:200px">
        </div>
      </div>
    </div>

    <?php if (empty($cannedReplies)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-chat-quote fs-1 d-block mb-3 opacity-25"></i>
        No hay respuestas enlatadas.
      </div>
    <?php else: ?>
      <div class="row g-3" id="cannedGrid">
        <?php foreach ($cannedReplies as $c):
          $cat = $c['category'] ?: 'General';
        ?>
        <div class="col-12 col-md-6 col-xl-4 canned-card" data-cat="<?= e($cat) ?>" data-search="<?= e(strtolower($c['title'] . ' ' . $c['shortcut'] . ' ' . $c['content'])) ?>">
          <div class="card h-100 <?= $c['is_active'] ? '' : 'opacity-50' ?>">
            <div class="card-body pb-2">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <span class="badge bg-secondary"><?= e($cat) ?></span>
                  <code class="ms-2 text-warning small">/<?= e($c['shortcut']) ?></code>
                </div>
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" role="switch"
                         <?= $c['is_active'] ? 'checked' : '' ?>
                         onchange="toggleCannedCM(<?= $c['id'] ?>, this.checked)">
                </div>
              </div>
              <div class="fw-semibold mb-1" style="font-size:.9rem"><?= e($c['title']) ?></div>
              <p class="text-muted mb-0" style="font-size:.82rem;line-height:1.5;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden">
                <?= e($c['content']) ?>
              </p>
            </div>
            <div class="card-footer d-flex justify-content-end gap-1 py-2">
              <button class="btn btn-xs btn-outline-primary" onclick="editCannedCM(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)" title="Editar">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="btn btn-xs btn-outline-danger" onclick="deleteCannedCM(<?= $c['id'] ?>)" title="Eliminar">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- OFFCANVAS: Quick Add / Edit Bot Response              -->
<!-- ══════════════════════════════════════════════════════ -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="addPanel" style="width:420px;background:var(--card);border-left:1px solid var(--border)">
  <div class="offcanvas-header border-bottom" style="border-color:var(--border)!important">
    <h5 class="offcanvas-title" id="addPanelTitle">Nueva Respuesta del Bot</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <form id="cmBotForm">
      <input type="hidden" id="cmBotId" name="id">

      <!-- Category -->
      <div class="mb-3">
        <label class="form-label fw-semibold">Categoría</label>
        <div class="d-flex gap-2">
          <select class="form-select" id="cmBotCat" name="category">
            <option value="General">General</option>
            <option value="Saludo">Saludo</option>
            <option value="Precios">Precios</option>
            <option value="Servicios">Servicios</option>
            <option value="Soporte">Soporte</option>
            <option value="Lead">Lead</option>
            <option value="FAQ">FAQ</option>
            <option value="Despedida">Despedida</option>
            <?php foreach (array_keys($categories) as $cat): ?>
              <?php if (!in_array($cat, ['General','Saludo','Precios','Servicios','Soporte','Lead','FAQ','Despedida'])): ?>
                <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
          <input type="text" class="form-control" id="cmNewCat" placeholder="Nueva…" title="Escribe aquí para crear nueva categoría" style="width:110px" oninput="if(this.value)document.getElementById('cmBotCat').value=''">
        </div>
        <small class="text-muted">O escribe una categoría nueva en el campo de la derecha</small>
      </div>

      <!-- Trigger word -->
      <div class="mb-3">
        <label class="form-label fw-semibold">Palabra / frase clave <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="cmTrigger" name="trigger_word" required placeholder="hola, precio, soporte, cotizar…">
        <small class="text-muted">El bot responde cuando el mensaje contiene esta palabra</small>
      </div>

      <!-- Priority + Exact -->
      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label fw-semibold">Prioridad</label>
          <input type="number" class="form-control" id="cmPriority" name="priority" value="1" min="1" max="100">
        </div>
        <div class="col-6 d-flex align-items-end pb-1">
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="cmExact" name="is_exact_match">
            <label class="form-check-label" for="cmExact">Coincidencia exacta</label>
          </div>
        </div>
      </div>

      <!-- Response -->
      <div class="mb-3">
        <label class="form-label fw-semibold">Respuesta del bot <span class="text-danger">*</span></label>
        <textarea class="form-control" id="cmResponse" name="response_text" rows="5" required
                  placeholder="Escribe aquí la respuesta que enviará el bot al usuario…"></textarea>
        <div class="d-flex justify-content-between mt-1">
          <small class="text-muted">Máx. recomendado: 500 caracteres</small>
          <small class="text-muted" id="cmCharCount">0 / 500</small>
        </div>
      </div>

      <!-- Quick replies -->
      <div class="mb-4">
        <label class="form-label fw-semibold">Botones de respuesta rápida</label>
        <div id="cmQRList">
          <!-- Dynamic rows added by JS -->
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="addQRRow()">
          <i class="bi bi-plus-lg me-1"></i>Agregar botón
        </button>
        <small class="d-block text-muted mt-1">Estos botones aparecen debajo del mensaje del bot en el chat</small>
      </div>

      <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary flex-fill" onclick="saveBotCM(false)">
          <i class="bi bi-floppy me-2"></i>Guardar
        </button>
        <button type="button" class="btn btn-outline-primary" onclick="saveBotCM(true)" title="Guardar y agregar otro">
          <i class="bi bi-plus-circle"></i>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- OFFCANVAS: Quick Edit Canned Reply                    -->
<!-- ══════════════════════════════════════════════════════ -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="cannedPanel" style="width:420px;background:var(--card);border-left:1px solid var(--border)">
  <div class="offcanvas-header border-bottom" style="border-color:var(--border)!important">
    <h5 class="offcanvas-title" id="cannedPanelTitle">Nueva Respuesta Enlatada</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <form id="cmCannedForm">
      <input type="hidden" id="cmCannedId" name="id">
      <div class="mb-3">
        <label class="form-label fw-semibold">Categoría</label>
        <input type="text" class="form-control" id="cmCannedCat" name="category" placeholder="General">
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="cmCannedTitle" name="title" required placeholder="Saludo de bienvenida">
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Atajo</label>
        <div class="input-group">
          <span class="input-group-text text-muted">/</span>
          <input type="text" class="form-control" id="cmCannedShortcut" name="shortcut" placeholder="saludo">
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">Contenido <span class="text-danger">*</span></label>
        <textarea class="form-control" id="cmCannedContent" name="content" rows="5" required></textarea>
      </div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary flex-fill" onclick="saveCannedCM(false)">
          <i class="bi bi-floppy me-2"></i>Guardar
        </button>
        <button type="button" class="btn btn-outline-primary" onclick="saveCannedCM(true)">
          <i class="bi bi-plus-circle"></i>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- MODAL: Batch Import                                   -->
<!-- ══════════════════════════════════════════════════════ -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content bg-dark border" style="border-color:var(--border)!important">
      <div class="modal-header border-bottom" style="border-color:var(--border)!important">
        <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Importar respuestas en bloque</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-4">
          <!-- Left: instructions + input -->
          <div class="col-lg-6">
            <div class="alert alert-info py-2 mb-3" style="font-size:.85rem">
              <strong>Formato por línea:</strong><br>
              <code>keyword | respuesta | botón1,botón2,botón3 | Categoría | prioridad</code><br>
              <span class="text-muted">Los campos desde "botones" son opcionales. Una línea = una respuesta. Las líneas que comienzan con <code>#</code> son comentarios.</span>
            </div>
            <div class="mb-2 d-flex justify-content-between align-items-center">
              <label class="form-label mb-0 fw-semibold">Pegar respuestas</label>
              <div class="d-flex gap-2">
                <button class="btn btn-xs btn-outline-secondary" onclick="loadExample()">
                  <i class="bi bi-lightning me-1"></i>Ver ejemplo
                </button>
                <button class="btn btn-xs btn-outline-secondary" onclick="clearImport()">
                  <i class="bi bi-x-lg me-1"></i>Limpiar
                </button>
              </div>
            </div>
            <textarea class="form-control font-monospace" id="importText" rows="18"
              placeholder="# Ejemplo de formato:&#10;hola | ¡Hola! Bienvenido a MailGenius. ¿En qué puedo ayudarte? | Ver servicios,Cotizar,Hablar con agente | Saludo | 10&#10;precio | Ofrecemos planes a partir de $XX/mes. | Ver planes,Contactar ventas | Precios | 8&#10;soporte | Nuestro equipo está disponible lun-vie 9am-6pm. | Abrir ticket,Ver FAQ | Soporte | 5"
              oninput="previewImport()" style="font-size:.82rem;resize:vertical"></textarea>
          </div>
          <!-- Right: preview -->
          <div class="col-lg-6">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="form-label mb-0 fw-semibold">Vista previa</label>
              <span class="badge bg-primary" id="importCount">0 respuestas</span>
            </div>
            <div style="height:420px;overflow-y:auto">
              <table class="table table-dark table-sm mb-0" id="importPreviewTable">
                <thead>
                  <tr>
                    <th>Keyword</th>
                    <th>Respuesta</th>
                    <th>Categoría</th>
                    <th>Botones</th>
                  </tr>
                </thead>
                <tbody id="importPreviewBody">
                  <tr><td colspan="4" class="text-center text-muted py-4">Pega el texto a la izquierda para previsualizar</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer border-top" style="border-color:var(--border)!important">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="runImport()" id="importBtn" disabled>
          <i class="bi bi-upload me-2"></i>Importar todas
        </button>
      </div>
    </div>
  </div>
</div>

<style>
.btn-xs { padding: .2rem .5rem; font-size: .78rem; }
.cat-filter.active, .canned-filter.active { font-weight: 600; }
.card { transition: opacity .2s, box-shadow .15s; }
.card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.3); }
</style>

<?php include 'includes/footer.php'; ?>

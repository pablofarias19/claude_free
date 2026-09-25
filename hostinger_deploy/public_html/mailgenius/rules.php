<?php
require_once 'config/config.php';
auth_required();

$activePage = 'rules';
$eng  = new ResponseEngine();
$rules      = $eng->getRules();
$categories = $eng->getCategories();
$diagrams   = Database::fetchAll("SELECT id, name FROM diagrams WHERE is_active = 1 ORDER BY name");

$pageTitle    = 'Reglas de Respuesta';
$extraScripts = ['assets/js/rules.js'];
include 'includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h2 class="page-title mb-1">Reglas de Respuesta Inteligente</h2>
    <p class="text-muted mb-0">Motor de clasificación y respuesta automática</p>
  </div>
  <button class="btn btn-primary" onclick="openRuleModal()">
    <i class="bi bi-plus-lg me-2"></i>Nueva Regla
  </button>
</div>

<!-- How it works -->
<div class="alert alert-info bg-info bg-opacity-10 border-info mb-4" style="border-color:rgba(6,182,212,.3)!important">
  <i class="bi bi-info-circle me-2"></i>
  <strong>¿Cómo funciona?</strong> Cada email entrante es analizado por las reglas en orden de prioridad.
  La primera regla que coincida ejecuta la acción configurada (respuesta automática, enrutamiento, etc.).
</div>

<!-- Rules list -->
<div class="card">
  <div class="card-body p-0">
    <?php if (empty($rules)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-diagram-3" style="font-size:3rem;opacity:.4"></i>
        <p class="mt-2">No hay reglas configuradas</p>
        <button class="btn btn-primary btn-sm" onclick="openRuleModal()">Crear primera regla</button>
      </div>
    <?php else: ?>
      <table class="table table-dark table-hover mb-0 align-middle" id="rulesTable">
        <thead>
          <tr>
            <th class="ps-4" style="width:50px">Orden</th>
            <th>Nombre</th>
            <th>Condiciones</th>
            <th>Acción</th>
            <th>Categoría</th>
            <th>Estado</th>
            <th class="text-end pe-4">Acciones</th>
          </tr>
        </thead>
        <tbody id="rulesList">
          <?php foreach ($rules as $r): ?>
          <?php $conds = json_decode($r['conditions'] ?? '[]', true) ?? []; ?>
          <?php $actionMap = [
            'auto_reply'       => ['primary','Respuesta automática','reply-all'],
            'forward'          => ['info','Reenviar','forward'],
            'apply_template'   => ['success','Aplicar plantilla','file-earmark-text'],
            'route_to_diagram' => ['warning','Enrutar a diagrama','diagram-3'],
            'tag'              => ['secondary','Etiquetar','tag'],
            'ignore'           => ['danger','Ignorar','slash-circle'],
          ]; ?>
          <?php [$actCls,$actLbl,$actIco] = $actionMap[$r['action']] ?? ['secondary',$r['action'],'gear']; ?>
          <tr data-id="<?= $r['id'] ?>">
            <td class="ps-4 text-center">
              <span class="badge bg-secondary"><?= $r['priority'] ?></span>
            </td>
            <td>
              <div class="fw-600"><?= e($r['name']) ?></div>
              <div class="text-muted" style="font-size:.8rem"><?= count($conds) ?> condición<?= count($conds) != 1 ? 'es' : '' ?></div>
            </td>
            <td>
              <?php foreach (array_slice($conds, 0, 2) as $cond): ?>
                <div class="mb-1" style="font-size:.82rem">
                  <span class="badge bg-secondary"><?= e($cond['field'] ?? '') ?></span>
                  <span class="text-muted mx-1"><?= e($cond['operator'] ?? '') ?></span>
                  <code class="text-warning"><?= e(mb_strimwidth($cond['value'] ?? '', 0, 30, '…')) ?></code>
                </div>
              <?php endforeach; ?>
              <?php if (count($conds) > 2): ?>
                <div class="text-muted" style="font-size:.78rem">+<?= count($conds) - 2 ?> más</div>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge bg-<?= $actCls ?>">
                <i class="bi bi-<?= $actIco ?> me-1"></i><?= $actLbl ?>
              </span>
              <?php if ($r['action_value']): ?>
                <div class="text-muted" style="font-size:.78rem;margin-top:2px">
                  <?= e(mb_strimwidth($r['action_value'], 0, 40, '…')) ?>
                </div>
              <?php endif; ?>
            </td>
            <td>
              <?php $cat = array_filter($categories, fn($c) => $c['id'] == $r['category_id']); ?>
              <?php $cat = reset($cat); ?>
              <?= $cat ? '<span class="badge bg-info text-dark">' . e($cat['name']) . '</span>' : '<span class="text-muted">—</span>' ?>
            </td>
            <td>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" <?= $r['is_active'] ? 'checked' : '' ?>
                       onchange="toggleRule(<?= $r['id'] ?>, this.checked)">
              </div>
            </td>
            <td class="text-end pe-4">
              <button class="btn btn-sm btn-outline-primary" onclick="editRule(<?= $r['id'] ?>)">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger ms-1" onclick="deleteRule(<?= $r['id'] ?>)">
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

<!-- Rule Modal -->
<div class="modal fade" id="ruleModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content bg-dark border" style="border-color:var(--border)!important">
      <div class="modal-header border-bottom" style="border-color:var(--border)!important">
        <h5 class="modal-title" id="ruleModalTitle">Nueva Regla</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="ruleForm">
          <input type="hidden" id="ruleId" name="id">
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">Nombre de la regla *</label>
              <input type="text" class="form-control" id="ruleName" name="name" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Categoría</label>
              <select class="form-select" id="ruleCat" name="category_id">
                <option value="">Sin categoría</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Prioridad</label>
              <input type="number" class="form-control" id="rulePriority" name="priority" value="<?= count($rules) + 1 ?>" min="1">
            </div>
          </div>

          <h6 class="fw-700 mb-3">Condiciones <small class="text-muted fw-normal">(todas deben cumplirse)</small></h6>
          <div id="conditionsContainer"></div>
          <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="addCondition()">
            <i class="bi bi-plus-circle me-1"></i>Agregar condición
          </button>

          <hr style="border-color:var(--border)">
          <h6 class="fw-700 mb-3">Acción a ejecutar</h6>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Tipo de acción</label>
              <select class="form-select" id="ruleAction" name="action" onchange="updateActionConfig()">
                <option value="auto_reply">Respuesta automática</option>
                <option value="apply_template">Aplicar plantilla</option>
                <option value="forward">Reenviar a agente</option>
                <option value="route_to_diagram">Enrutar a diagrama</option>
                <option value="tag">Etiquetar</option>
                <option value="ignore">Ignorar</option>
              </select>
            </div>
            <div class="col-md-8" id="actionConfig">
              <label class="form-label">Configuración de acción</label>
              <textarea class="form-control" id="ruleActionValue" name="action_value" rows="3" placeholder="Mensaje de respuesta automática…"></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-top" style="border-color:var(--border)!important">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="saveRule()">
          <i class="bi bi-floppy me-2"></i>Guardar Regla
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const DIAGRAMS = <?= json_encode($diagrams) ?>;
const TEMPLATES_LIST = <?= json_encode(Database::fetchAll("SELECT id, name FROM templates ORDER BY name")) ?>;
</script>

<?php include 'includes/footer.php'; ?>

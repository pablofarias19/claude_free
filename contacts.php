<?php
require_once 'config/config.php';
auth_required();

$activePage = 'contacts';
$mgr    = new ContactManager();
$groups = $mgr->getGroups();
$stats  = $mgr->getStats();

$groupId = (int)($_GET['group'] ?? 0);
$search  = trim($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['p'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$whereExtra = '';
$params = [];
if ($groupId) {
    $whereExtra .= " AND cg.group_id = ?";
    $params[] = $groupId;
}
if ($search) {
    $whereExtra .= " AND (c.name LIKE ? OR c.email LIKE ? OR c.company LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$total = Database::query(
    "SELECT COUNT(*) FROM contacts c
     LEFT JOIN contact_groups g ON g.id = c.group_id
     WHERE 1=1 $whereExtra",
    $params
)->fetchColumn();

$contacts = Database::fetchAll(
    "SELECT c.*, g.name AS group_names
     FROM contacts c
     LEFT JOIN contact_groups g ON g.id = c.group_id
     WHERE 1=1 $whereExtra
     ORDER BY c.name ASC LIMIT $perPage OFFSET $offset",
    $params
);

$totalPages = max(1, ceil($total / $perPage));

$pageTitle   = 'Contactos';
$extraScripts = ['assets/js/contacts.js'];
include 'includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h2 class="page-title mb-1">Contactos</h2>
    <p class="text-muted mb-0"><?= number_format($total) ?> contacto<?= $total != 1 ? 's' : '' ?></p>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-outline-secondary" onclick="document.getElementById('importFile').click()">
      <i class="bi bi-upload me-2"></i>Importar CSV
    </button>
    <input type="file" id="importFile" accept=".csv" style="display:none" onchange="importContacts(this)">
    <button class="btn btn-primary" onclick="openContactModal()">
      <i class="bi bi-person-plus me-2"></i>Nuevo Contacto
    </button>
  </div>
</div>

<!-- Stats row -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card text-center">
      <div class="stat-value"><?= number_format($stats['total'] ?? 0) ?></div>
      <div class="stat-label">Total</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card text-center">
      <div class="stat-value text-success"><?= number_format($stats['active'] ?? 0) ?></div>
      <div class="stat-label">Activos</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card text-center">
      <div class="stat-value text-warning"><?= number_format($stats['unsubscribed'] ?? 0) ?></div>
      <div class="stat-label">Desubscritos</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card text-center">
      <div class="stat-value text-primary"><?= count($groups) ?></div>
      <div class="stat-label">Grupos</div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Groups sidebar -->
  <div class="col-md-3">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-600">Grupos</span>
        <button class="btn btn-link btn-sm text-primary p-0" onclick="openGroupModal()">
          <i class="bi bi-plus-circle"></i>
        </button>
      </div>
      <div class="card-body p-0">
        <ul class="list-unstyled mb-0">
          <li>
            <a href="contacts.php?q=<?= urlencode($search) ?>" class="d-flex justify-content-between align-items-center px-3 py-2 text-decoration-none <?= !$groupId ? 'bg-primary' : 'text-muted' ?> rounded-bottom">
              <span><i class="bi bi-people me-2"></i>Todos</span>
              <span class="badge bg-secondary"><?= $stats['total'] ?? 0 ?></span>
            </a>
          </li>
          <?php foreach ($groups as $g): ?>
          <li>
            <a href="contacts.php?group=<?= $g['id'] ?>&q=<?= urlencode($search) ?>"
               class="d-flex justify-content-between align-items-center px-3 py-2 text-decoration-none border-top <?= $groupId===$g['id'] ? 'bg-primary' : 'text-muted' ?>"
               style="border-color:var(--border)!important">
              <span class="text-truncate"><?= e($g['name']) ?></span>
              <span class="badge bg-secondary"><?= $g['contact_count'] ?? 0 ?></span>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>

  <!-- Contacts table -->
  <div class="col-md-9">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-3">
        <form class="d-flex gap-2 flex-grow-1" method="GET">
          <input type="hidden" name="group" value="<?= $groupId ?>">
          <input type="search" name="q" class="form-control" placeholder="Buscar por nombre, email, empresa…" value="<?= e($search) ?>">
          <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
        <div id="bulkActions" style="display:none">
          <button class="btn btn-sm btn-danger" onclick="deleteSelected()">
            <i class="bi bi-trash me-1"></i>Eliminar seleccionados
          </button>
        </div>
      </div>
      <div class="card-body p-0">
        <?php if (empty($contacts)): ?>
          <div class="text-center py-5 text-muted">
            <i class="bi bi-person-x" style="font-size:3rem;opacity:.4"></i>
            <p class="mt-2">No se encontraron contactos</p>
          </div>
        <?php else: ?>
          <table class="table table-dark table-hover mb-0 align-middle">
            <thead>
              <tr>
                <th class="ps-4" style="width:40px">
                  <input type="checkbox" id="selectAll" onchange="toggleAll(this)">
                </th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Empresa</th>
                <th>Grupos</th>
                <th>Estado</th>
                <th class="text-end pe-4">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($contacts as $c): ?>
              <tr>
                <td class="ps-4"><input type="checkbox" class="contact-check" value="<?= $c['id'] ?>" onchange="updateBulk()"></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:.8rem;flex-shrink:0">
                      <?= strtoupper(substr($c['name'] ?? '?', 0, 1)) ?>
                    </div>
                    <div>
                      <div class="fw-600"><?= e($c['name']) ?></div>
                      <?php if ($c['phone']): ?>
                        <div class="text-muted" style="font-size:.75rem"><?= e($c['phone']) ?></div>
                      <?php endif; ?>
                    </div>
                  </div>
                </td>
                <td><?= e($c['email']) ?></td>
                <td><?= e($c['company'] ?? '—') ?></td>
                <td>
                  <?php if ($c['group_names']): ?>
                    <?php foreach (explode(', ', $c['group_names']) as $gn): ?>
                      <span class="badge bg-secondary me-1"><?= e($gn) ?></span>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php $st = $c['status'] ?? 'active'; ?>
                  <?php $stMap = ['active'=>['success','Activo'],'unsubscribed'=>['warning','Desubscrito'],'bounced'=>['danger','Rebotado']]; ?>
                  <?php [$cls,$lbl] = $stMap[$st] ?? ['secondary',$st]; ?>
                  <span class="badge bg-<?= $cls ?>"><?= $lbl ?></span>
                </td>
                <td class="text-end pe-4">
                  <button class="btn btn-sm btn-outline-primary" onclick="editContact(<?= htmlspecialchars(json_encode($c)) ?>)">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger ms-1" onclick="deleteContact(<?= $c['id'] ?>)">
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

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav class="mt-3">
      <ul class="pagination justify-content-center mb-0">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="contacts.php?group=<?= $groupId ?>&p=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>
  </div>
</div>

<!-- Contact Modal -->
<div class="modal fade" id="contactModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content bg-dark border" style="border-color:var(--border)!important">
      <div class="modal-header border-bottom" style="border-color:var(--border)!important">
        <h5 class="modal-title" id="contactModalTitle">Nuevo Contacto</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="contactForm">
          <input type="hidden" id="contactId" name="id">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nombre *</label>
              <input type="text" class="form-control" id="cName" name="name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email *</label>
              <input type="email" class="form-control" id="cEmail" name="email" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Teléfono</label>
              <input type="tel" class="form-control" id="cPhone" name="phone">
            </div>
            <div class="col-md-6">
              <label class="form-label">Empresa</label>
              <input type="text" class="form-control" id="cCompany" name="company">
            </div>
            <div class="col-12">
              <label class="form-label">Grupos</label>
              <select class="form-select" id="cGroups" name="groups[]" multiple>
                <?php foreach ($groups as $g): ?>
                  <option value="<?= $g['id'] ?>"><?= e($g['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted">Ctrl+clic para seleccionar varios</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Estado</label>
              <select class="form-select" id="cStatus" name="status">
                <option value="active">Activo</option>
                <option value="unsubscribed">Desubscrito</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Notas</label>
              <textarea class="form-control" id="cNotes" name="notes" rows="2"></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-top" style="border-color:var(--border)!important">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="saveContact()">
          <i class="bi bi-floppy me-2"></i>Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Group Modal -->
<div class="modal fade" id="groupModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content bg-dark border" style="border-color:var(--border)!important">
      <div class="modal-header border-bottom" style="border-color:var(--border)!important">
        <h5 class="modal-title">Nuevo Grupo</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Nombre del grupo</label>
        <input type="text" class="form-control" id="groupName" placeholder="Clientes VIP…">
      </div>
      <div class="modal-footer border-top" style="border-color:var(--border)!important">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="saveGroup()">Crear</button>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

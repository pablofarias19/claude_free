<?php
require_once 'config/config.php';
auth_required();

$activePage = 'inbox';
$mgr  = new EmailManager();
$box  = $_GET['box']    ?? 'sent';
$page = max(1, (int)($_GET['p'] ?? 1));
$search = trim($_GET['q'] ?? '');

$validBoxes = ['sent','drafts','scheduled','inbox'];
if (!in_array($box, $validBoxes)) $box = 'sent';

$perPage = 20;
$offset  = ($page - 1) * $perPage;

$whereExtra = '';
$params = [];

if ($search) {
    $whereExtra .= " AND (subject LIKE ? OR to_email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

switch ($box) {
    case 'drafts':
        $status = 'draft';
        $title  = 'Borradores';
        break;
    case 'scheduled':
        $status = 'scheduled';
        $title  = 'Programados';
        break;
    default:
        $status = 'sent';
        $title  = 'Enviados';
}

$total = Database::query(
    "SELECT COUNT(*) FROM emails WHERE type = ? $whereExtra",
    array_merge([$status], $params)
)->fetchColumn();

$emails = Database::fetchAll(
    "SELECT e.*, (SELECT COUNT(*) FROM email_attachments WHERE email_id=e.id) AS att_count
     FROM emails e WHERE e.type = ? $whereExtra
     ORDER BY e.created_at DESC LIMIT $perPage OFFSET $offset",
    array_merge([$status], $params)
);

$totalPages = max(1, ceil($total / $perPage));

$pageTitle   = "Bandeja — $title";
$extraScripts = ['assets/js/inbox.js'];
include 'includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h2 class="page-title mb-1"><?= $title ?></h2>
    <p class="text-muted mb-0"><?= number_format($total) ?> mensaje<?= $total != 1 ? 's' : '' ?></p>
  </div>
  <a href="compose.php" class="btn btn-primary"><i class="bi bi-pencil-square me-2"></i>Nuevo Email</a>
</div>

<!-- Tabs -->
<div class="card mb-4">
  <div class="card-body p-0">
    <div class="d-flex border-bottom" style="border-color:var(--border)!important">
      <?php foreach ([['sent','bi-send','Enviados'],['drafts','bi-file-earmark','Borradores'],['scheduled','bi-clock','Programados']] as [$b,$ic,$lb]): ?>
      <a href="inbox.php?box=<?= $b ?>&q=<?= urlencode($search) ?>"
         class="px-4 py-3 text-decoration-none fw-500 border-end <?= $box===$b ? 'text-primary border-bottom border-primary' : 'text-muted' ?>"
         style="border-color:var(--border)!important;<?= $box===$b ? 'margin-bottom:-1px;' : '' ?>">
        <i class="bi <?= $ic ?> me-2"></i><?= $lb ?>
      </a>
      <?php endforeach; ?>
      <div class="ms-auto d-flex align-items-center px-3">
        <form class="d-flex gap-2" method="GET">
          <input type="hidden" name="box" value="<?= e($box) ?>">
          <input type="search" name="q" class="form-control form-control-sm" placeholder="Buscar…" value="<?= e($search) ?>" style="width:220px">
          <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
      </div>
    </div>

    <!-- Email list -->
    <?php if (empty($emails)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-inbox" style="font-size:3rem;opacity:.4"></i>
        <p class="mt-2">No hay mensajes en <?= $title ?></p>
      </div>
    <?php else: ?>
      <div id="emailList">
        <?php foreach ($emails as $em): ?>
        <div class="email-row d-flex align-items-center px-4 py-3 border-bottom <?= $em['is_read'] ? '' : 'unread' ?>"
             style="border-color:var(--border)!important;cursor:pointer"
             onclick="viewEmail(<?= $em['id'] ?>)">
          <div class="me-3">
            <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-size:.8rem">
              <?= strtoupper(substr($em['to_email'] ?? '?', 0, 1)) ?>
            </div>
          </div>
          <div class="flex-grow-1 overflow-hidden">
            <div class="d-flex align-items-baseline gap-2">
              <span class="fw-600 text-truncate"><?= e($em['to_email']) ?></span>
              <?php if ($em['priority'] === 'high'): ?>
                <span class="badge bg-danger" style="font-size:.65rem">Alta</span>
              <?php endif; ?>
              <?php if ($em['att_count'] > 0): ?>
                <i class="bi bi-paperclip text-muted" style="font-size:.8rem"></i>
              <?php endif; ?>
            </div>
            <div class="text-truncate mt-1" style="font-size:.875rem"><?= e($em['subject']) ?></div>
          </div>
          <div class="ms-3 text-end text-muted" style="font-size:.8rem;white-space:nowrap">
            <div><?= date('d/m/y', strtotime($em['created_at'])) ?></div>
            <div><?= date('H:i', strtotime($em['created_at'])) ?></div>
          </div>
          <div class="ms-3 d-flex gap-2">
            <?php if ($box === 'drafts'): ?>
              <a href="compose.php?draft=<?= $em['id'] ?>" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation()">
                <i class="bi bi-pencil"></i>
              </a>
            <?php endif; ?>
            <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation();deleteEmail(<?= $em['id'] ?>)">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav>
  <ul class="pagination justify-content-center">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <li class="page-item <?= $i === $page ? 'active' : '' ?>">
        <a class="page-link" href="inbox.php?box=<?= $box ?>&p=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<!-- View Modal -->
<div class="modal fade" id="emailModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content bg-dark border" style="border-color:var(--border)!important">
      <div class="modal-header border-bottom" style="border-color:var(--border)!important">
        <h5 class="modal-title" id="emailModalTitle">Mensaje</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="emailModalBody">
        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

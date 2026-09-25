<?php
require_once 'config/config.php';
auth_required();

$activePage = 'scheduler';
$mgr = new EmailManager();

$scheduled = $mgr->getScheduled();

$pageTitle = 'Emails Programados';
$extraScripts = ['assets/js/scheduler.js'];
include 'includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h2 class="page-title mb-1">Emails Programados</h2>
    <p class="text-muted mb-0"><?= count($scheduled) ?> programado<?= count($scheduled) != 1 ? 's' : '' ?></p>
  </div>
  <a href="compose.php" class="btn btn-primary"><i class="bi bi-clock me-2"></i>Programar Nuevo</a>
</div>

<div class="card">
  <div class="card-body p-0">
    <?php if (empty($scheduled)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-clock-history" style="font-size:3rem;opacity:.4"></i>
        <p class="mt-2">No hay emails programados</p>
        <a href="compose.php" class="btn btn-outline-primary btn-sm mt-2">Crear programación</a>
      </div>
    <?php else: ?>
      <table class="table table-dark table-hover mb-0 align-middle">
        <thead>
          <tr>
            <th class="ps-4">Destinatario</th>
            <th>Asunto</th>
            <th>Programado para</th>
            <th>Recurrencia</th>
            <th>Estado</th>
            <th class="text-end pe-4">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($scheduled as $item): ?>
          <tr>
            <td class="ps-4">
              <div class="fw-600"><?= e($item['to_email']) ?></div>
              <?php if ($item['cc_email']): ?>
                <div class="text-muted" style="font-size:.8rem">CC: <?= e($item['cc_email']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <span class="text-truncate d-block" style="max-width:280px"><?= e($item['subject']) ?></span>
            </td>
            <td>
              <div class="text-warning">
                <i class="bi bi-calendar-event me-1"></i>
                <?= date('d/m/Y H:i', strtotime($item['scheduled_at'])) ?>
              </div>
              <?php
              $diff = strtotime($item['scheduled_at']) - time();
              if ($diff > 0):
                $h = floor($diff/3600); $m = floor(($diff%3600)/60);
              ?>
              <div class="text-muted" style="font-size:.78rem">en <?= $h ?>h <?= $m ?>m</div>
              <?php endif; ?>
            </td>
            <td>
              <?php $rec = $item['recurrence'] ?? 'none'; ?>
              <?php $labels = ['none'=>'—','daily'=>'Diario','weekly'=>'Semanal','monthly'=>'Mensual']; ?>
              <span class="badge <?= $rec === 'none' ? 'bg-secondary' : 'bg-info text-dark' ?>">
                <?= $labels[$rec] ?? $rec ?>
              </span>
            </td>
            <td>
              <?php $st = $item['status'] ?? 'pending'; ?>
              <?php $stMap = ['pending'=>['warning','Pendiente'],'processing'=>['primary','Procesando'],'done'=>['success','Enviado'],'failed'=>['danger','Fallido'],'cancelled'=>['secondary','Cancelado']]; ?>
              <?php [$cls,$lbl] = $stMap[$st] ?? ['secondary',$st]; ?>
              <span class="badge bg-<?= $cls ?>"><?= $lbl ?></span>
            </td>
            <td class="text-end pe-4">
              <?php if ($st === 'pending'): ?>
              <button class="btn btn-sm btn-outline-danger" onclick="cancelScheduled(<?= $item['id'] ?>)">
                <i class="bi bi-x-circle me-1"></i>Cancelar
              </button>
              <?php endif; ?>
              <button class="btn btn-sm btn-outline-secondary ms-1" onclick="viewScheduled(<?= $item['id'] ?>)">
                <i class="bi bi-eye"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

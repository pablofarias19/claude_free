<?php
require_once __DIR__ . '/config/config.php';
auth_required();

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
$breadcrumb = 'Dashboard';

$emailMgr    = new EmailManager();
$chatMgr     = new ChatManager();
$campaignMgr = new CampaignManager();

$emailStats    = $emailMgr->getStats();
$chatStats     = $chatMgr->getStats();
$campaignStats = $campaignMgr->getStats();
$dailySent     = $emailMgr->getDailySent(14);

// Build chart data
$chartLabels = [];
$chartData   = [];
foreach ($dailySent as $row) {
    $chartLabels[] = date('d/m', strtotime($row['day']));
    $chartData[]   = (int)$row['cnt'];
}

// Recent activity
$recentEmails = Database::fetchAll(
    "SELECT id, subject, from_email, type, sent_at, created_at
     FROM emails ORDER BY created_at DESC LIMIT 8"
);
$recentChats = Database::fetchAll(
    "SELECT cs.*, u.name AS agent_name FROM chat_sessions cs
     LEFT JOIN users u ON u.id = cs.agent_id
     ORDER BY cs.started_at DESC LIMIT 8"
);

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-4">
  <div>
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Resumen de comunicaciones — <?= date('d \d\e F, Y') ?></p>
  </div>
  <a href="<?= APP_URL ?>/compose.php" class="btn btn-primary">
    <i class="bi bi-pencil-square me-2"></i>Redactar Email
  </a>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-2">
    <div class="stat-card">
      <div class="stat-icon primary"><i class="bi bi-send-fill"></i></div>
      <div>
        <div class="stat-value"><?= number_format($emailStats['sent_today']) ?></div>
        <div class="stat-label">Enviados hoy</div>
        <div class="stat-change up"><i class="bi bi-arrow-up-short"></i><?= $emailStats['sent_week'] ?> esta semana</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-2">
    <div class="stat-card">
      <div class="stat-icon accent"><i class="bi bi-calendar-event-fill"></i></div>
      <div>
        <div class="stat-value"><?= number_format($emailStats['scheduled']) ?></div>
        <div class="stat-label">Programados</div>
        <div class="stat-change"><i class="bi bi-clock"></i>Pendientes</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-2">
    <div class="stat-card">
      <div class="stat-icon success"><i class="bi bi-eye-fill"></i></div>
      <div>
        <div class="stat-value"><?= $emailStats['open_rate_today'] ?>%</div>
        <div class="stat-label">Tasa apertura</div>
        <div class="stat-change up"><i class="bi bi-graph-up"></i>Hoy</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-2">
    <div class="stat-card">
      <div class="stat-icon <?= $chatStats['waiting'] > 0 ? 'warning' : 'success' ?>">
        <i class="bi bi-chat-dots-fill"></i>
      </div>
      <div>
        <div class="stat-value"><?= $chatStats['active'] + $chatStats['waiting'] ?></div>
        <div class="stat-label">Chats activos</div>
        <?php if ($chatStats['waiting'] > 0): ?>
          <div class="stat-change down"><i class="bi bi-clock-fill"></i><?= $chatStats['waiting'] ?> esperando</div>
        <?php else: ?>
          <div class="stat-change up"><i class="bi bi-check-circle"></i>Todo atendido</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-2">
    <div class="stat-card">
      <div class="stat-icon warning"><i class="bi bi-megaphone-fill"></i></div>
      <div>
        <div class="stat-value"><?= $campaignStats['running'] ?></div>
        <div class="stat-label">Campañas activas</div>
        <div class="stat-change"><i class="bi bi-people"></i><?= number_format($campaignStats['total_sent']) ?> enviados</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-2">
    <div class="stat-card">
      <div class="stat-icon danger"><i class="bi bi-exclamation-triangle-fill"></i></div>
      <div>
        <div class="stat-value"><?= $emailStats['bounced_today'] ?></div>
        <div class="stat-label">Rebotados hoy</div>
        <div class="stat-change <?= $emailStats['bounced_today'] > 0 ? 'down' : 'up' ?>">
          <i class="bi bi-envelope-x"></i>Monitorear
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Charts + Activity -->
<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-bar-chart-line me-2 text-primary"></i>Emails enviados (14 días)</span>
        <a href="<?= APP_URL ?>/inbox.php" class="btn btn-sm btn-secondary">Ver todos</a>
      </div>
      <div class="card-body">
        <canvas id="sentChart" height="100"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header">
        <i class="bi bi-pie-chart me-2 text-accent"></i>Estado de campañas
      </div>
      <div class="card-body d-flex flex-column align-items-center justify-content-center">
        <canvas id="campaignChart" style="max-width:180px"></canvas>
        <div class="mt-3 w-100">
          <div class="d-flex justify-content-between small mb-1">
            <span class="text-muted">Completadas</span>
            <strong class="text-success"><?= $campaignStats['completed'] ?></strong>
          </div>
          <div class="d-flex justify-content-between small mb-1">
            <span class="text-muted">En proceso</span>
            <strong class="text-warning"><?= $campaignStats['running'] ?></strong>
          </div>
          <div class="d-flex justify-content-between small">
            <span class="text-muted">Programadas</span>
            <strong class="text-primary"><?= $campaignStats['scheduled'] ?></strong>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Recent Tables -->
<div class="row g-3">
  <!-- Recent Emails -->
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-envelope me-2"></i>Emails recientes</span>
        <a href="<?= APP_URL ?>/inbox.php" class="btn btn-sm btn-secondary">Ver bandeja</a>
      </div>
      <div class="card-body p-0">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Asunto</th>
              <th>De / Para</th>
              <th>Tipo</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentEmails)): ?>
              <tr><td colspan="4"><div class="empty-state py-4"><i class="bi bi-inbox fs-3"></i><p class="mb-0">No hay emails aún</p></div></td></tr>
            <?php else: ?>
              <?php foreach ($recentEmails as $email): ?>
              <tr>
                <td>
                  <a href="<?= APP_URL ?>/compose.php?id=<?= $email['id'] ?>" class="text-decoration-none text-white fw-500">
                    <?= e(substr($email['subject'], 0, 45)) ?>
                  </a>
                </td>
                <td class="text-muted small"><?= e($email['from_email']) ?></td>
                <td>
                  <?php
                    $typeMap = ['sent'=>'success','draft'=>'gray','scheduled'=>'accent','received'=>'primary'];
                    $typeLabel = ['sent'=>'Enviado','draft'=>'Borrador','scheduled'=>'Prog.','received'=>'Recibido'];
                  ?>
                  <span class="badge badge-<?= $typeMap[$email['type']] ?? 'gray' ?>"><?= $typeLabel[$email['type']] ?? $email['type'] ?></span>
                </td>
                <td class="text-muted small"><?= time_ago($email['created_at']) ?></td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Recent Chats -->
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-chat-dots me-2"></i>Chats recientes</span>
        <a href="<?= APP_URL ?>/chat.php" class="btn btn-sm btn-secondary">Ver todos</a>
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush">
          <?php if (empty($recentChats)): ?>
            <div class="empty-state py-4"><i class="bi bi-chat-square fs-3"></i><p class="mb-0">Sin conversaciones</p></div>
          <?php else: ?>
            <?php foreach ($recentChats as $chat): ?>
              <a href="<?= APP_URL ?>/chat.php?session=<?= $chat['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" style="background:transparent;border-color:var(--border);color:var(--text)">
                <div class="d-flex align-items-center gap-3">
                  <div class="avatar-sm"><?= strtoupper(substr($chat['visitor_name'] ?? 'V', 0, 1)) ?></div>
                  <div>
                    <div class="fw-600 small"><?= e($chat['visitor_name'] ?? 'Visitante') ?></div>
                    <div class="text-muted text-xs"><?= e($chat['visitor_email'] ?? '') ?></div>
                  </div>
                </div>
                <div class="text-end">
                  <?php
                    $statusMap = ['waiting'=>'warning','active'=>'success','closed'=>'gray','missed'=>'danger'];
                    $statusLabel = ['waiting'=>'Esperando','active'=>'Activo','closed'=>'Cerrado','missed'=>'Perdido'];
                  ?>
                  <span class="badge badge-<?= $statusMap[$chat['status']] ?? 'gray' ?>"><?= $statusLabel[$chat['status']] ?? $chat['status'] ?></span>
                  <div class="text-xs text-muted mt-1"><?= time_ago($chat['started_at']) ?></div>
                </div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Chart: daily sent
const ctx1 = document.getElementById('sentChart').getContext('2d');
new Chart(ctx1, {
  type: 'bar',
  data: {
    labels: <?= json_encode($chartLabels) ?>,
    datasets: [{
      label: 'Emails enviados',
      data: <?= json_encode($chartData) ?>,
      backgroundColor: 'rgba(79,70,229,.5)',
      borderColor: 'rgba(79,70,229,1)',
      borderWidth: 1.5,
      borderRadius: 6,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#64748b' } },
      y: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#64748b', stepSize: 1 } }
    }
  }
});

// Chart: campaigns donut
const ctx2 = document.getElementById('campaignChart').getContext('2d');
new Chart(ctx2, {
  type: 'doughnut',
  data: {
    labels: ['Completadas', 'Activas', 'Programadas'],
    datasets: [{
      data: [<?= $campaignStats['completed'] ?>, <?= $campaignStats['running'] ?>, <?= $campaignStats['scheduled'] ?>],
      backgroundColor: ['rgba(16,185,129,.7)', 'rgba(245,158,11,.7)', 'rgba(79,70,229,.7)'],
      borderWidth: 0,
    }]
  },
  options: {
    cutout: '70%',
    plugins: {
      legend: { display: false },
    }
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

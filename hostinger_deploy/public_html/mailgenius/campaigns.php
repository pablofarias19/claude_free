<?php
require_once 'config/config.php';
auth_required();

$activePage = 'campaigns';
$mgr    = new CampaignManager();
$stats  = $mgr->getStats();
$campaigns = $mgr->getAll();

$pageTitle    = 'Campañas de Email';
$extraScripts = ['assets/js/campaigns.js'];
include 'includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h2 class="page-title mb-1">Campañas de Email</h2>
    <p class="text-muted mb-0">Envíos masivos y campañas automatizadas</p>
  </div>
  <button class="btn btn-primary" onclick="openCampaignModal()">
    <i class="bi bi-megaphone me-2"></i>Nueva Campaña
  </button>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <?php
  $statCards = [
    ['label'=>'Total','value'=>$stats['total']??0,'icon'=>'megaphone','color'=>'primary'],
    ['label'=>'Activas','value'=>$stats['active']??0,'icon'=>'play-circle','color'=>'success'],
    ['label'=>'Emails Enviados','value'=>$stats['total_sent']??0,'icon'=>'envelope-check','color'=>'info'],
    ['label'=>'Tasa Apertura','value'=>($stats['open_rate']??0).'%','icon'=>'eye','color'=>'warning'],
  ];
  foreach ($statCards as $sc): ?>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-icon bg-<?= $sc['color'] ?> bg-opacity-15 text-<?= $sc['color'] ?>">
          <i class="bi bi-<?= $sc['icon'] ?>" style="font-size:1.4rem"></i>
        </div>
        <div>
          <div class="stat-value"><?= $sc['value'] ?></div>
          <div class="stat-label"><?= $sc['label'] ?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Campaigns list -->
<div class="card">
  <div class="card-body p-0">
    <?php if (empty($campaigns)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-megaphone" style="font-size:3rem;opacity:.4"></i>
        <p class="mt-2">No hay campañas. <button class="btn btn-primary btn-sm" onclick="openCampaignModal()">Crear primera</button></p>
      </div>
    <?php else: ?>
      <table class="table table-dark table-hover mb-0 align-middle">
        <thead>
          <tr>
            <th class="ps-4">Campaña</th>
            <th>Estado</th>
            <th>Progreso</th>
            <th>Enviados</th>
            <th>Aperturas</th>
            <th>Fecha</th>
            <th class="text-end pe-4">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($campaigns as $camp): ?>
          <?php
            $total_r = $camp['total_recipients'] ?: 1;
            $pct     = round(($camp['sent_count'] / $total_r) * 100);
            $stMap   = [
              'draft'     => ['secondary','Borrador'],
              'scheduled' => ['warning','Programada'],
              'sending'   => ['primary','Enviando'],
              'paused'    => ['warning','Pausada'],
              'completed' => ['success','Completada'],
              'cancelled' => ['danger','Cancelada'],
            ];
            [$stCls,$stLbl] = $stMap[$camp['status']] ?? ['secondary',$camp['status']];
          ?>
          <tr>
            <td class="ps-4">
              <div class="fw-600"><?= e($camp['name']) ?></div>
              <div class="text-muted" style="font-size:.8rem"><?= e($camp['subject']) ?></div>
            </td>
            <td><span class="badge bg-<?= $stCls ?>"><?= $stLbl ?></span></td>
            <td style="min-width:140px">
              <div class="d-flex align-items-center gap-2">
                <div class="flex-grow-1 bg-secondary rounded" style="height:6px;overflow:hidden">
                  <div class="bg-primary rounded" style="height:6px;width:<?= $pct ?>%"></div>
                </div>
                <span class="text-muted" style="font-size:.75rem;white-space:nowrap"><?= $pct ?>%</span>
              </div>
              <div class="text-muted" style="font-size:.75rem"><?= $camp['sent_count'] ?>/<?= $camp['total_recipients'] ?></div>
            </td>
            <td>
              <span class="fw-600"><?= number_format($camp['sent_count']) ?></span>
            </td>
            <td>
              <?php if ($camp['open_count'] > 0): ?>
                <span class="text-success"><?= round(($camp['open_count'] / max(1,$camp['sent_count'])) * 100) ?>%</span>
                <div class="text-muted" style="font-size:.75rem"><?= $camp['open_count'] ?> aperturas</div>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="text-muted" style="font-size:.85rem">
                <?= $camp['scheduled_at'] ? date('d/m/Y H:i', strtotime($camp['scheduled_at'])) : '—' ?>
              </div>
            </td>
            <td class="text-end pe-4">
              <div class="d-flex gap-1 justify-content-end">
                <?php if ($camp['status'] === 'draft'): ?>
                  <button class="btn btn-sm btn-success" onclick="startCampaign(<?= $camp['id'] ?>)" title="Iniciar">
                    <i class="bi bi-play-fill"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-primary" onclick="editCampaign(<?= $camp['id'] ?>)" title="Editar">
                    <i class="bi bi-pencil"></i>
                  </button>
                <?php elseif ($camp['status'] === 'sending'): ?>
                  <button class="btn btn-sm btn-warning" onclick="pauseCampaign(<?= $camp['id'] ?>)" title="Pausar">
                    <i class="bi bi-pause-fill"></i>
                  </button>
                <?php elseif ($camp['status'] === 'paused'): ?>
                  <button class="btn btn-sm btn-success" onclick="startCampaign(<?= $camp['id'] ?>)" title="Reanudar">
                    <i class="bi bi-play-fill"></i>
                  </button>
                <?php endif; ?>
                <button class="btn btn-sm btn-outline-info" onclick="viewCampaignStats(<?= $camp['id'] ?>)" title="Estadísticas">
                  <i class="bi bi-bar-chart"></i>
                </button>
                <?php if (in_array($camp['status'], ['draft','cancelled','completed'])): ?>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteCampaign(<?= $camp['id'] ?>)" title="Eliminar">
                  <i class="bi bi-trash"></i>
                </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<!-- Campaign Modal -->
<div class="modal fade" id="campaignModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content bg-dark border" style="border-color:var(--border)!important">
      <div class="modal-header border-bottom" style="border-color:var(--border)!important">
        <h5 class="modal-title" id="campaignModalTitle">Nueva Campaña</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="campaignForm">
          <input type="hidden" id="campId" name="id">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nombre de la campaña *</label>
              <input type="text" class="form-control" id="campName" name="name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Asunto del email *</label>
              <input type="text" class="form-control" id="campSubject" name="subject" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email remitente</label>
              <input type="email" class="form-control" id="campFromEmail" name="from_email" placeholder="<?= e(defined('MAIL_FROM') ? MAIL_FROM : '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Nombre remitente</label>
              <input type="text" class="form-control" id="campFromName" name="from_name">
            </div>
            <div class="col-12">
              <label class="form-label">Grupos de destinatarios</label>
              <div id="groupSelector" class="d-flex flex-wrap gap-2 p-3 border rounded" style="border-color:var(--border)!important;min-height:60px">
                <?php
                $allGroups = Database::fetchAll("SELECT * FROM contact_groups ORDER BY name");
                foreach ($allGroups as $g): ?>
                  <label class="d-flex align-items-center gap-2 badge bg-secondary fw-normal py-2 px-3" style="cursor:pointer;font-size:.85rem">
                    <input type="checkbox" name="groups[]" value="<?= $g['id'] ?>" class="form-check-input m-0">
                    <?= e($g['name']) ?> (<?= $g['contact_count'] ?? 0 ?>)
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Cuerpo del email</label>
              <div id="campEditor" style="height:280px"></div>
              <textarea id="campBody" name="body" style="display:none"></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Programar envío (opcional)</label>
              <input type="text" class="form-control flatpickr-input" id="campSchedule" name="scheduled_at" placeholder="Seleccionar fecha y hora…">
            </div>
            <div class="col-md-6">
              <label class="form-label">Tamaño de lote</label>
              <input type="number" class="form-control" id="campBatch" name="batch_size" value="50" min="1" max="500">
              <small class="text-muted">Emails por procesamiento del cron</small>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-top" style="border-color:var(--border)!important">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-outline-primary" onclick="saveCampaign('draft')">
          <i class="bi bi-floppy me-2"></i>Guardar borrador
        </button>
        <button class="btn btn-success" onclick="saveCampaign('start')">
          <i class="bi bi-play-fill me-2"></i>Iniciar campaña
        </button>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

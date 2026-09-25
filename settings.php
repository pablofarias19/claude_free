<?php
require_once 'config/config.php';
auth_required();
if (!is_admin()) { header('Location: index.php'); exit; }

$activePage = 'settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) json_response(['error' => 'Token inválido'], 403);
    foreach ($_POST as $key => $value) {
        if ($key === 'csrf_token') continue;
        Database::get()->prepare(
            "INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        )->execute([$key, $value]);
    }
    $successMsg = 'Configuración guardada correctamente.';
}

$settings = [];
foreach (Database::fetchAll("SELECT setting_key, setting_value FROM settings") as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

function sval($key, $default = '') {
    global $settings;
    return $settings[$key] ?? $default;
}

$pageTitle = 'Configuración';
include 'includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <h2 class="page-title mb-0">Configuración del Sistema</h2>
</div>

<?php if (!empty($successMsg)): ?>
  <div class="alert alert-success bg-success bg-opacity-10 border-success mb-4">
    <i class="bi bi-check-circle me-2"></i><?= e($successMsg) ?>
  </div>
<?php endif; ?>

<form method="POST" id="settingsForm">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

  <div class="row g-4">
    <div class="col-lg-6">
      <!-- General -->
      <div class="card mb-4">
        <div class="card-header fw-700">
          <i class="bi bi-gear me-2 text-primary"></i>General
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Nombre de la aplicación</label>
            <input type="text" class="form-control" name="app_name" value="<?= e(sval('app_name', 'MailGenius Pro')) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">URL base</label>
            <input type="url" class="form-control" name="app_url" value="<?= e(sval('app_url', APP_URL)) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Zona horaria</label>
            <select class="form-select" name="timezone">
              <?php foreach (['America/Bogota','America/Mexico_City','America/Lima','America/Argentina/Buenos_Aires','America/Santiago','Europe/Madrid','UTC'] as $tz): ?>
                <option value="<?= $tz ?>" <?= sval('timezone', 'America/Bogota') === $tz ? 'selected' : '' ?>><?= $tz ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Idioma</label>
            <select class="form-select" name="language">
              <option value="es" <?= sval('language', 'es') === 'es' ? 'selected' : '' ?>>Español</option>
              <option value="en" <?= sval('language', 'es') === 'en' ? 'selected' : '' ?>>English</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Chat -->
      <div class="card">
        <div class="card-header fw-700">
          <i class="bi bi-chat-dots me-2 text-success"></i>Chat en Vivo
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Mensaje de bienvenida del bot</label>
            <textarea class="form-control" name="chat_welcome_msg" rows="3"><?= e(sval('chat_welcome_msg', '¡Hola! ¿En qué puedo ayudarte hoy?')) ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Intervalo de polling del chat (ms)</label>
            <input type="number" class="form-control" name="chat_poll_interval" value="<?= e(sval('chat_poll_interval', '2500')) ?>" min="1000" max="10000">
          </div>
          <div class="mb-3">
            <label class="form-label">Máximo de archivos adjuntos (MB)</label>
            <input type="number" class="form-control" name="chat_max_file_mb" value="<?= e(sval('chat_max_file_mb', '10')) ?>" min="1" max="50">
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="chatOffline" name="chat_show_offline"
                   <?= sval('chat_show_offline', '1') ? 'checked' : '' ?> value="1">
            <label class="form-check-label" for="chatOffline">Mostrar widget fuera de horario</label>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <!-- SMTP -->
      <div class="card mb-4">
        <div class="card-header fw-700">
          <i class="bi bi-envelope me-2 text-warning"></i>Configuración SMTP
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Servidor SMTP</label>
              <input type="text" class="form-control" name="smtp_host" value="<?= e(sval('smtp_host', defined('SMTP_HOST') ? SMTP_HOST : '')) ?>" placeholder="smtp.gmail.com">
            </div>
            <div class="col-6">
              <label class="form-label">Puerto</label>
              <input type="number" class="form-control" name="smtp_port" value="<?= e(sval('smtp_port', '587')) ?>">
            </div>
            <div class="col-6">
              <label class="form-label">Encriptación</label>
              <select class="form-select" name="smtp_encryption">
                <option value="tls" <?= sval('smtp_encryption','tls') === 'tls' ? 'selected':'' ?>>TLS</option>
                <option value="ssl" <?= sval('smtp_encryption','tls') === 'ssl' ? 'selected':'' ?>>SSL</option>
                <option value="" <?= sval('smtp_encryption','tls') === '' ? 'selected':'' ?>>Ninguna</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Usuario SMTP</label>
              <input type="text" class="form-control" name="smtp_user" value="<?= e(sval('smtp_user')) ?>" placeholder="usuario@dominio.com">
            </div>
            <div class="col-12">
              <label class="form-label">Contraseña SMTP</label>
              <div class="input-group">
                <input type="password" class="form-control" name="smtp_pass" id="smtpPass" value="<?= e(sval('smtp_pass')) ?>">
                <button type="button" class="btn btn-outline-secondary" onclick="toggleSmtpPass()">
                  <i class="bi bi-eye" id="smtpEye"></i>
                </button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email remitente por defecto</label>
              <input type="email" class="form-control" name="mail_from" value="<?= e(sval('mail_from', defined('MAIL_FROM') ? MAIL_FROM : '')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Nombre remitente</label>
              <input type="text" class="form-control" name="mail_from_name" value="<?= e(sval('mail_from_name', defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : '')) ?>">
            </div>
          </div>
          <button type="button" class="btn btn-outline-info btn-sm mt-3" onclick="testSmtp()">
            <i class="bi bi-send me-1"></i>Probar conexión SMTP
          </button>
        </div>
      </div>

      <!-- Queue -->
      <div class="card">
        <div class="card-header fw-700">
          <i class="bi bi-clock me-2 text-info"></i>Cola de Envíos
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Lote de procesamiento por cron</label>
            <input type="number" class="form-control" name="queue_batch_size" value="<?= e(sval('queue_batch_size','50')) ?>" min="1" max="500">
          </div>
          <div class="mb-3">
            <label class="form-label">Reintentos en caso de error</label>
            <input type="number" class="form-control" name="queue_max_retries" value="<?= e(sval('queue_max_retries','3')) ?>" min="0" max="10">
          </div>
          <div class="mb-0">
            <label class="form-label">Comando de cron recomendado</label>
            <pre class="p-2 rounded mb-0" style="background:#0f172a;font-size:.8rem;word-break:break-all">* * * * * php <?= APP_ROOT ?>/cron/process_queue.php</pre>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end mt-4 gap-3">
    <button type="reset" class="btn btn-secondary">Descartar cambios</button>
    <button type="submit" class="btn btn-primary px-5">
      <i class="bi bi-floppy me-2"></i>Guardar configuración
    </button>
  </div>
</form>

<script>
function toggleSmtpPass() {
  const f = document.getElementById('smtpPass');
  const i = document.getElementById('smtpEye');
  f.type = f.type === 'password' ? 'text' : 'password';
  i.className = f.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
async function testSmtp() {
  const btn = event.target.closest('button');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Probando…';
  try {
    const fd = new FormData();
    fd.append('action', 'test_smtp');
    fd.append('csrf_token', document.querySelector('[name=csrf_token]').value);
    const res = await fetch('api/settings.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) toast('Conexión SMTP exitosa', 'success');
    else toast('Error SMTP: ' + (data.error || 'desconocido'), 'error');
  } catch(e) {
    toast('Error al probar SMTP', 'error');
  }
  btn.disabled = false;
  btn.innerHTML = '<i class="bi bi-send me-1"></i>Probar conexión SMTP';
}
</script>
<?php include 'includes/footer.php'; ?>

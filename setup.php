<?php
/**
 * MailGenius Pro — Installation Wizard
 * Delete this file after successful installation.
 */
define('SETUP_MODE', true);

$step    = (int)($_GET['step'] ?? 1);
$errors  = [];
$success = '';

// Step 2: test DB connection
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['db_host'] ?? '');
    $name = trim($_POST['db_name'] ?? '');
    $user = trim($_POST['db_user'] ?? '');
    $pass = trim($_POST['db_pass'] ?? '');
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        // Write config
        $configContent = "<?php\ndefine('DB_HOST', " . var_export($host, true) . ");\ndefine('DB_NAME', " . var_export($name, true) . ");\ndefine('DB_USER', " . var_export($user, true) . ");\ndefine('DB_PASS', " . var_export($pass, true) . ");\n";
        if (is_writable('config/')) {
            file_put_contents('config/db.php', $configContent);
        }
        $step = 3;
        // Store in session for step 3
        session_start();
        $_SESSION['setup_db'] = compact('host','name','user','pass');
    } catch (PDOException $e) {
        $errors[] = 'Error de conexión: ' . $e->getMessage();
    }
}

// Step 3: run schema
if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $db = $_SESSION['setup_db'] ?? null;
    if (!$db) { header('Location: setup.php?step=1'); exit; }
    try {
        $pdo  = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4", $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $sql  = file_get_contents('database/schema.sql');
        $stmts = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($stmts as $stmt) {
            if ($stmt) $pdo->exec($stmt);
        }
        $success = 'Base de datos instalada correctamente.';
        $step = 4;
    } catch (Exception $e) {
        $errors[] = 'Error al ejecutar schema: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MailGenius Pro — Instalación</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root { --primary: #6366f1; --bg: #0f172a; --card: #1e293b; --border: #334155; --text: #e2e8f0; --muted: #94a3b8; }
    body { background: var(--bg); color: var(--text); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: system-ui, sans-serif; }
    .setup-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 2.5rem; width: 100%; max-width: 560px; }
    .step-indicator { display: flex; gap: 8px; margin-bottom: 2rem; }
    .step-dot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .8rem; font-weight: 700; }
    .step-dot.done { background: var(--primary); color: #fff; }
    .step-dot.active { background: var(--primary); color: #fff; }
    .step-dot.todo { background: #1e293b; border: 2px solid var(--border); color: var(--muted); }
    .form-control, .form-select { background: #0f172a; border: 1.5px solid var(--border); color: var(--text); border-radius: 8px; }
    .form-control:focus, .form-select:focus { background: #0f172a; border-color: var(--primary); color: var(--text); box-shadow: none; }
    .btn-setup { background: var(--primary); border: none; border-radius: 8px; padding: .75rem 2rem; font-weight: 600; }
  </style>
</head>
<body>
<div class="setup-card">
  <div class="text-center mb-4">
    <div style="font-size:2.5rem">✉</div>
    <h1 style="font-size:1.4rem;font-weight:700">MailGenius Pro</h1>
    <p style="color:var(--muted);font-size:.85rem">Asistente de instalación</p>
  </div>

  <div class="step-indicator justify-content-center">
    <?php for ($i = 1; $i <= 4; $i++): ?>
      <div class="step-dot <?= $i < $step ? 'done' : ($i === $step ? 'active' : 'todo') ?>"><?= $i < $step ? '✓' : $i ?></div>
      <?php if ($i < 4): ?><div class="flex-grow-1" style="border-top:2px solid var(--border);margin-top:15px"></div><?php endif; ?>
    <?php endfor; ?>
  </div>

  <?php foreach ($errors as $err): ?>
    <div class="alert" style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.4);color:#fca5a5;border-radius:8px;padding:.75rem 1rem;margin-bottom:1rem">
      <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($err) ?>
    </div>
  <?php endforeach; ?>

  <?php if ($step === 1): ?>
    <h4 class="fw-700 mb-3">Bienvenido</h4>
    <p style="color:var(--muted)">Este asistente te guiará para instalar MailGenius Pro en tu servidor. Antes de continuar, asegúrate de tener:</p>
    <ul style="color:var(--muted);line-height:2">
      <li>PHP 8.0 o superior</li>
      <li>MySQL 8.0 o MariaDB 10.5+</li>
      <li>Extensiones: PDO, PDO_MySQL, mbstring, json</li>
      <li>Datos de conexión a la base de datos</li>
    </ul>
    <a href="setup.php?step=2" class="btn btn-setup btn-primary w-100 mt-3">Comenzar instalación →</a>

  <?php elseif ($step === 2): ?>
    <h4 class="fw-700 mb-3">Configuración de Base de Datos</h4>
    <form method="POST" action="setup.php?step=2">
      <div class="mb-3">
        <label class="form-label" style="color:var(--muted)">Host</label>
        <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label" style="color:var(--muted)">Nombre de la base de datos</label>
        <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($_POST['db_name'] ?? 'mailgenius') ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label" style="color:var(--muted)">Usuario</label>
        <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
      </div>
      <div class="mb-4">
        <label class="form-label" style="color:var(--muted)">Contraseña</label>
        <input type="password" name="db_pass" class="form-control">
      </div>
      <button type="submit" class="btn btn-setup btn-primary w-100">Probar conexión →</button>
    </form>

  <?php elseif ($step === 3): ?>
    <h4 class="fw-700 mb-3">Instalar Base de Datos</h4>
    <p style="color:var(--muted)">Se crearán todas las tablas necesarias. Si ya existen serán omitidas.</p>
    <form method="POST" action="setup.php?step=3">
      <button type="submit" class="btn btn-setup btn-primary w-100">Instalar esquema →</button>
    </form>

  <?php elseif ($step === 4): ?>
    <div class="text-center">
      <div style="font-size:3rem;color:#22c55e">✓</div>
      <h4 class="fw-700 mt-2">¡Instalación completada!</h4>
      <p style="color:var(--muted)">MailGenius Pro está listo para usarse.</p>
      <div class="alert mt-3" style="background:rgba(234,179,8,.1);border:1px solid rgba(234,179,8,.3);color:#fde68a;border-radius:8px;font-size:.85rem">
        <i class="bi bi-shield-exclamation me-2"></i>
        <strong>Por seguridad:</strong> elimina el archivo <code>setup.php</code> de tu servidor antes de continuar.
      </div>
      <a href="login.php" class="btn btn-setup btn-primary w-100 mt-3">Ir al sistema →</a>
      <p class="mt-3" style="color:var(--muted);font-size:.8rem">Usuario: <strong>admin@mailgenius.com</strong> / Contraseña: <strong>Admin2024!</strong></p>
    </div>
  <?php endif; ?>
</div>
</body>
</html>

<?php
require_once 'config/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        $user = Database::fetch("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email']= $user['email'];
            header('Location: index.php');
            exit;
        }
        $error = 'Credenciales incorrectas. Intente de nuevo.';
    } else {
        $error = 'Complete todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MailGenius Pro — Iniciar Sesión</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root { --primary: #6366f1; --bg: #0f172a; --card: #1e293b; --border: #334155; --text: #e2e8f0; --muted: #94a3b8; }
    body { background: var(--bg); color: var(--text); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: system-ui, sans-serif; }
    .login-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 2.5rem; width: 100%; max-width: 420px; box-shadow: 0 20px 60px rgba(0,0,0,.4); }
    .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 2rem; }
    .brand-icon { width: 48px; height: 48px; background: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .brand h1 { font-size: 1.4rem; font-weight: 700; margin: 0; }
    .brand p { font-size: .8rem; color: var(--muted); margin: 0; }
    .form-label { color: var(--muted); font-size: .875rem; font-weight: 500; }
    .form-control { background: #0f172a; border: 1.5px solid var(--border); color: var(--text); border-radius: 8px; padding: .65rem 1rem; }
    .form-control:focus { background: #0f172a; border-color: var(--primary); color: var(--text); box-shadow: 0 0 0 3px rgba(99,102,241,.2); }
    .btn-login { background: var(--primary); border: none; border-radius: 8px; padding: .75rem; font-weight: 600; font-size: .95rem; width: 100%; transition: filter .2s; }
    .btn-login:hover { filter: brightness(1.1); }
    .alert-err { background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.4); border-radius: 8px; color: #fca5a5; padding: .75rem 1rem; font-size: .875rem; }
    .input-group-text { background: #0f172a; border: 1.5px solid var(--border); color: var(--muted); cursor: pointer; }
    .input-group .form-control { border-left: none; }
    .input-group .input-group-text:first-child { border-right: none; }
  </style>
</head>
<body>
<div class="login-card">
  <div class="brand">
    <div class="brand-icon">✉</div>
    <div>
      <h1>MailGenius Pro</h1>
      <p>Sistema de Gestión de Comunicaciones</p>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert-err mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?></div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div class="mb-3">
      <label class="form-label">Correo Electrónico</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
        <input type="email" name="email" class="form-control" placeholder="admin@ejemplo.com"
               value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
      </div>
    </div>
    <div class="mb-4">
      <label class="form-label">Contraseña</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-lock"></i></span>
        <input type="password" name="password" id="passField" class="form-control" placeholder="••••••••" required>
        <span class="input-group-text" onclick="togglePass()"><i class="bi bi-eye" id="eyeIcon"></i></span>
      </div>
    </div>
    <button type="submit" class="btn btn-login btn-primary">
      <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
    </button>
  </form>

  <p class="text-center mt-4 mb-0" style="font-size:.8rem;color:var(--muted);">
    Usuario inicial: <strong>admin@mailgenius.com</strong> / <strong>Admin2024!</strong>
  </p>
</div>

<script>
function togglePass() {
  const f = document.getElementById('passField');
  const i = document.getElementById('eyeIcon');
  if (f.type === 'password') { f.type = 'text'; i.className = 'bi bi-eye-slash'; }
  else { f.type = 'password'; i.className = 'bi bi-eye'; }
}
</script>
</body>
</html>

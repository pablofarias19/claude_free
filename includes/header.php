<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? APP_NAME) ?> — <?= e(APP_NAME) ?></title>

<!-- Bootstrap 5.3 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<!-- Quill Editor -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css">
<!-- Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<!-- App CSS -->
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">

<meta name="csrf-token" content="<?= csrf_token() ?>">
<script>const APP_URL = "<?= APP_URL ?>";</script>
</head>
<body>
<?php require_once APP_ROOT . '/includes/sidebar.php'; ?>
<div class="main-content">
  <!-- Topbar -->
  <header class="topbar d-flex align-items-center justify-content-between px-4">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-icon sidebar-toggle d-lg-none" id="sidebarToggle">
        <i class="bi bi-list fs-5"></i>
      </button>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Inicio</a></li>
          <?php if (!empty($breadcrumb)): ?>
            <li class="breadcrumb-item active"><?= e($breadcrumb) ?></li>
          <?php endif; ?>
        </ol>
      </nav>
    </div>
    <div class="d-flex align-items-center gap-2">
      <!-- Notifications -->
      <div class="dropdown">
        <button class="btn btn-icon position-relative" id="notifBtn" data-bs-toggle="dropdown">
          <i class="bi bi-bell fs-5"></i>
          <span class="notif-badge" id="notifCount" style="display:none">0</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end notif-dropdown" id="notifDropdown">
          <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
            <strong>Notificaciones</strong>
            <a href="#" class="small text-primary" id="markAllRead">Leer todo</a>
          </div>
          <div id="notifList" class="notif-list">
            <div class="text-center text-muted py-4 small">Sin notificaciones</div>
          </div>
        </div>
      </div>
      <!-- User -->
      <div class="dropdown">
        <button class="btn btn-icon d-flex align-items-center gap-2" data-bs-toggle="dropdown">
          <div class="avatar-sm"><?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?></div>
          <span class="d-none d-md-inline text-sm"><?= e($_SESSION['user_name'] ?? 'Admin') ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?= APP_URL ?>/settings.php"><i class="bi bi-gear me-2"></i>Configuración</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
        </ul>
      </div>
    </div>
  </header>
  <div class="page-content">

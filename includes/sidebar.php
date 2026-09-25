<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="brand d-flex align-items-center gap-2">
      <div class="brand-icon"><i class="bi bi-envelope-heart-fill"></i></div>
      <span class="brand-name"><?= APP_NAME ?></span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <!-- Main -->
    <div class="nav-section-label">Principal</div>
    <a href="<?= APP_URL ?>/index.php" class="nav-item <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
      <i class="bi bi-speedometer2"></i><span>Dashboard</span>
    </a>

    <!-- Email -->
    <div class="nav-section-label mt-2">Email</div>
    <a href="<?= APP_URL ?>/compose.php" class="nav-item <?= ($activePage ?? '') === 'compose' ? 'active' : '' ?>">
      <i class="bi bi-pencil-square"></i><span>Redactar</span>
    </a>
    <a href="<?= APP_URL ?>/inbox.php" class="nav-item <?= ($activePage ?? '') === 'inbox' ? 'active' : '' ?>">
      <i class="bi bi-inbox"></i><span>Bandeja</span>
      <?php
        $draftCount = Database::query("SELECT COUNT(*) FROM emails WHERE type='draft'")->fetchColumn();
        if ($draftCount > 0): ?>
        <span class="nav-badge"><?= $draftCount ?></span>
      <?php endif; ?>
    </a>
    <a href="<?= APP_URL ?>/scheduler.php" class="nav-item <?= ($activePage ?? '') === 'scheduler' ? 'active' : '' ?>">
      <i class="bi bi-calendar-check"></i><span>Programados</span>
      <?php
        $schCount = Database::query("SELECT COUNT(*) FROM scheduled_queue WHERE status='pending'")->fetchColumn();
        if ($schCount > 0): ?>
        <span class="nav-badge nav-badge-warn"><?= $schCount ?></span>
      <?php endif; ?>
    </a>

    <!-- Chat -->
    <div class="nav-section-label mt-2">Chat en vivo</div>
    <a href="<?= APP_URL ?>/chat.php" class="nav-item <?= ($activePage ?? '') === 'chat' ? 'active' : '' ?>">
      <i class="bi bi-chat-dots"></i><span>Conversaciones</span>
      <?php
        $chatWait = Database::query("SELECT COUNT(*) FROM chat_sessions WHERE status='waiting'")->fetchColumn();
        if ($chatWait > 0): ?>
        <span class="nav-badge nav-badge-live">●</span>
      <?php endif; ?>
    </a>
    <a href="<?= APP_URL ?>/chat_bot.php" class="nav-item <?= ($activePage ?? '') === 'chat_bot' ? 'active' : '' ?>">
      <i class="bi bi-robot"></i><span>Bot de Chat</span>
    </a>
    <a href="<?= APP_URL ?>/chat_widget.php" class="nav-item <?= ($activePage ?? '') === 'chat_widget' ? 'active' : '' ?>">
      <i class="bi bi-window-stack"></i><span>Widget Web</span>
    </a>

    <!-- Tools -->
    <div class="nav-section-label mt-2">Herramientas</div>
    <a href="<?= APP_URL ?>/campaigns.php" class="nav-item <?= ($activePage ?? '') === 'campaigns' ? 'active' : '' ?>">
      <i class="bi bi-megaphone"></i><span>Campañas</span>
    </a>
    <a href="<?= APP_URL ?>/templates.php" class="nav-item <?= ($activePage ?? '') === 'templates' ? 'active' : '' ?>">
      <i class="bi bi-file-earmark-richtext"></i><span>Plantillas</span>
    </a>
    <a href="<?= APP_URL ?>/diagrams.php" class="nav-item <?= ($activePage ?? '') === 'diagrams' ? 'active' : '' ?>">
      <i class="bi bi-diagram-3"></i><span>Diagramas</span>
    </a>
    <a href="<?= APP_URL ?>/rules.php" class="nav-item <?= ($activePage ?? '') === 'rules' ? 'active' : '' ?>">
      <i class="bi bi-lightning-charge"></i><span>Reglas Auto.</span>
    </a>

    <!-- Data -->
    <div class="nav-section-label mt-2">Gestión</div>
    <a href="<?= APP_URL ?>/contacts.php" class="nav-item <?= ($activePage ?? '') === 'contacts' ? 'active' : '' ?>">
      <i class="bi bi-people"></i><span>Contactos</span>
    </a>
    <a href="<?= APP_URL ?>/settings.php" class="nav-item <?= ($activePage ?? '') === 'settings' ? 'active' : '' ?>">
      <i class="bi bi-gear"></i><span>Configuración</span>
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="d-flex align-items-center gap-2">
      <div class="status-dot status-online"></div>
      <span class="small text-muted"><?= e($_SESSION['user_email'] ?? '') ?></span>
    </div>
  </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

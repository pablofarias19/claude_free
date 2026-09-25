<?php
// ============================================================
// MailGenius Pro - Configuración Principal
// ============================================================

define('APP_NAME',    'MailGenius Pro');
define('APP_VERSION', '1.0.0');
define('APP_URL',     'https://www.sucesionlegal.com.ar/mailgenius');
define('APP_ROOT',    dirname(__DIR__));
define('UPLOADS_DIR', APP_ROOT . '/uploads');
define('UPLOADS_URL', APP_URL . '/uploads');

// ── Sesión ───────────────────────────────────────────────────
session_name('MAILGENIUS_SESS');
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

// ── Zona horaria ─────────────────────────────────────────────
date_default_timezone_set('America/Buenos_Aires');

// ── Base de datos ────────────────────────────────────────────
// Load credentials written by setup.php (overrides the defaults below)
if (file_exists(__DIR__ . '/db.php')) {
    require_once __DIR__ . '/db.php';
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'mailgenius');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
}

// ── SMTP (sobreescribe con valores de la BD) ─────────────────
define('SMTP_HOST',       'smtp.gmail.com');
define('SMTP_PORT',       587);
define('SMTP_USER',       '');
define('SMTP_PASS',       '');
define('SMTP_ENCRYPTION', 'tls');
define('MAIL_FROM_NAME',  APP_NAME);
define('MAIL_FROM_EMAIL', 'noreply@example.com');

// ── Upload limits ────────────────────────────────────────────
define('MAX_UPLOAD_SIZE', 26214400); // 25 MB
define('ALLOWED_MIME_TYPES', [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
    'video/mp4', 'video/webm', 'video/ogg',
    'audio/mpeg', 'audio/wav', 'audio/ogg',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/zip',
    'text/plain',
    'text/csv',
]);

// ── Debug ────────────────────────────────────────────────────
define('DEBUG_MODE', true);
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ── Helpers ──────────────────────────────────────────────────
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): bool {
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function auth_required(): void {
    if (empty($_SESSION['user_id'])) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            json_response(['error' => 'No autenticado'], 401);
        }
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

function is_admin(): bool {
    return ($_SESSION['user_role'] ?? '') === 'admin';
}

function time_ago(\DateTime|string $date): string {
    $ts  = is_string($date) ? strtotime($date) : $date->getTimestamp();
    $diff = time() - $ts;
    return match(true) {
        $diff < 60     => 'hace ' . $diff . 's',
        $diff < 3600   => 'hace ' . floor($diff / 60) . 'm',
        $diff < 86400  => 'hace ' . floor($diff / 3600) . 'h',
        $diff < 604800 => 'hace ' . floor($diff / 86400) . 'd',
        default        => date('d/m/Y', $ts),
    };
}

function format_size(int $bytes): string {
    $units = ['B','KB','MB','GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < 3) { $bytes /= 1024; $i++; }
    return round($bytes, 1) . ' ' . $units[$i];
}

function generate_uuid(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
        mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
}

require_once APP_ROOT . '/config/Database.php';
require_once APP_ROOT . '/classes/EmailManager.php';
require_once APP_ROOT . '/classes/ChatManager.php';
require_once APP_ROOT . '/classes/CampaignManager.php';
require_once APP_ROOT . '/classes/ContactManager.php';
require_once APP_ROOT . '/classes/TemplateManager.php';

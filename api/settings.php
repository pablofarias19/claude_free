<?php
require_once dirname(__DIR__) . '/config/config.php';
auth_required();
if (!is_admin()) json_response(['error' => 'Sin permisos'], 403);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($method === 'POST') {
    if (!csrf_verify()) json_response(['error' => 'Token inválido'], 403);

    switch ($action) {
        case 'test_smtp':
            try {
                $host  = Database::query("SELECT setting_value FROM settings WHERE setting_key='smtp_host'")->fetchColumn() ?: (defined('SMTP_HOST') ? SMTP_HOST : '');
                $port  = (int)(Database::query("SELECT setting_value FROM settings WHERE setting_key='smtp_port'")->fetchColumn() ?: 587);
                $user  = Database::query("SELECT setting_value FROM settings WHERE setting_key='smtp_user'")->fetchColumn() ?: '';
                $pass  = Database::query("SELECT setting_value FROM settings WHERE setting_key='smtp_pass'")->fetchColumn() ?: '';
                $enc   = Database::query("SELECT setting_value FROM settings WHERE setting_key='smtp_encryption'")->fetchColumn() ?: 'tls';

                if (!$host) json_response(['error' => 'Servidor SMTP no configurado'], 400);

                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = $host;
                    $mail->Port       = $port;
                    $mail->SMTPAuth   = (bool)$user;
                    $mail->Username   = $user;
                    $mail->Password   = $pass;
                    $mail->SMTPSecure = $enc ?: false;
                    $mail->smtpConnect();
                    $mail->smtpClose();
                    json_response(['success' => true]);
                } else {
                    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
                    $prefix = $enc === 'ssl' ? 'ssl://' : '';
                    $sock = @stream_socket_client("{$prefix}{$host}:{$port}", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
                    if (!$sock) json_response(['error' => "No se pudo conectar: $errstr ($errno)"], 500);
                    fclose($sock);
                    json_response(['success' => true, 'note' => 'Conexión TCP OK (PHPMailer no disponible para autenticación completa)']);
                }
            } catch (Exception $e) {
                json_response(['error' => $e->getMessage()], 500);
            }

        default:
            json_response(['error' => 'Acción desconocida'], 400);
    }
}

json_response(['error' => 'Método no permitido'], 405);

<?php
$allowedOrigins = ['https://www.sucesionlegal.com.ar', 'https://sucesionlegal.com.ar'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: https://www.sucesionlegal.com.ar');
}
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Vary: Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); exit; }

require_once dirname(__DIR__) . '/config/config.php';

$name      = trim($_POST['name']      ?? '');
$email     = trim($_POST['email']     ?? '');
$phone     = trim($_POST['phone']     ?? '');
$topic     = trim($_POST['topic']     ?? '');
$sessionId = trim($_POST['session_id'] ?? '');

if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
    exit;
}

// ── Guardar en contacts ────────────────────────────────────────
try {
    $existing = Database::fetch("SELECT id FROM contacts WHERE email = ?", [$email]);
    if (!$existing) {
        Database::insert('contacts', [
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone ?: null,
            'company'       => 'Consulta web',
            'is_subscribed' => 1,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }
} catch (Exception $e) {}

// ── Log en chat_messages ───────────────────────────────────────
if ($sessionId) {
    $session = Database::fetch("SELECT id FROM chat_sessions WHERE session_token = ?", [$sessionId]);
    if ($session) {
        Database::insert('chat_messages', [
            'session_id'  => $session['id'],
            'sender_type' => 'system',
            'content'     => "Solicitud de consulta — Nombre: $name | Email: $email | Tel: $phone | Motivo: $topic",
        ]);
    }
}

// ── Enviar email de notificación ───────────────────────────────
$to      = 'pablofarias19@gmail.com';
$subject = "Nueva consulta desde la web — $name";
$fecha   = date('d/m/Y H:i');
$body    = "Nueva solicitud de consulta recibida el $fecha\n\n"
         . "Nombre:  $name\n"
         . "Email:   $email\n"
         . "Teléfono: " . ($phone ?: '—') . "\n"
         . "Motivo:  " . ($topic ?: '—') . "\n\n"
         . "Respondé directamente a: $email\n"
         . "---\nEnviado desde sucesionlegal.com.ar";

$headers  = "From: sucesiones@fariasortiz.com.ar\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$sent = mail($to, "=?UTF-8?B?" . base64_encode($subject) . "?=", $body, $headers);

echo json_encode(['ok' => true, 'email_sent' => $sent]);

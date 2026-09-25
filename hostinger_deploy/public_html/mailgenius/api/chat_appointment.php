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

$fecha   = date('d/m/Y H:i');
$from    = "sucesiones@fariasortiz.com.ar";
$mailer  = "X-Mailer: PHP/" . phpversion();

// ── Email al estudio ───────────────────────────────────────────
$subjectEstudio = "Nueva consulta desde la web — $name";
$bodyEstudio    = "Nueva solicitud de consulta recibida el $fecha\n\n"
                . "Nombre:   $name\n"
                . "Email:    $email\n"
                . "Teléfono: " . ($phone ?: '—') . "\n"
                . "Motivo:   " . ($topic ?: '—') . "\n\n"
                . "Respondé directamente a: $email\n"
                . "---\nEnviado desde sucesionlegal.com.ar";

$headersEstudio  = "From: $from\r\n";
$headersEstudio .= "Reply-To: $email\r\n";
$headersEstudio .= $mailer;

$sent = mail('pablofarias19@gmail.com',
    "=?UTF-8?B?" . base64_encode($subjectEstudio) . "?=",
    $bodyEstudio, $headersEstudio);

// ── Email de confirmación al cliente ──────────────────────────
$subjectCliente = "Recibimos tu consulta — Pablo Farias Abogados";
$bodyCliente    = "Hola $name,\n\n"
                . "Recibimos tu solicitud de consulta el $fecha. Nos pondremos en contacto contigo a la brevedad dentro del horario de atención (lunes a viernes, 9 a 18 hs).\n\n"
                . "Si tenés urgencia, podés escribirnos directamente por WhatsApp al +54 9 11 6848-0793.\n\n"
                . "─────────────────────────────────────\n"
                . "NUESTROS SERVICIOS\n"
                . "─────────────────────────────────────\n"
                . "✔ Declaratoria de herederos\n"
                . "✔ Partición de bienes e inscripciones registrales\n"
                . "✔ Sucesiones con herederos en el exterior\n"
                . "✔ Tasaciones y liquidación de herencias\n"
                . "✔ Asesoramiento en toda Argentina — gestión 100% remota disponible\n\n"
                . "Primera consulta sin cargo y sin compromiso.\n\n"
                . "─────────────────────────────────────\n"
                . "CONTACTO\n"
                . "─────────────────────────────────────\n"
                . "📱 WhatsApp: +54 9 11 6848-0793\n"
                . "📧 Email:    sucesiones@fariasortiz.com.ar\n"
                . "🌐 Web:      https://www.sucesionlegal.com.ar\n"
                . "🕐 Horario:  lun–vie 9:00 a 18:00 hs\n\n"
                . "Muchas gracias por contactarnos.\n\n"
                . "Pablo Farias Abogados\n"
                . "Especialistas en Derecho Sucesorio";

$headersCliente  = "From: Pablo Farias Abogados <$from>\r\n";
$headersCliente .= "Reply-To: $from\r\n";
$headersCliente .= $mailer;

mail($email,
    "=?UTF-8?B?" . base64_encode($subjectCliente) . "?=",
    $bodyCliente, $headersCliente);

echo json_encode(['ok' => true, 'email_sent' => $sent]);

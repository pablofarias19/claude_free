<?php
/**
 * Public chat API — no authentication required.
 * - Finds bot responses from DB (trigger_keywords matching)
 * - Logs all visitor conversations to chat_sessions / chat_messages
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://www.sucesionlegal.com.ar');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }

require_once dirname(__DIR__) . '/config/config.php';

$message    = trim($_POST['message']    ?? '');
$sessionId  = trim($_POST['session_id'] ?? '');
$visitorName= trim($_POST['name']       ?? 'Visitante');
$action     = trim($_POST['action']     ?? 'chat');

if (!$message || mb_strlen($message) > 500) {
    echo json_encode(['response' => null]);
    exit;
}

// ── Normalize text ─────────────────────────────────────────────
function normalize_msg(string $s): string {
    $s = mb_strtolower($s, 'UTF-8');
    return str_replace(
        ['á','é','í','ó','ú','ü','ñ'],
        ['a','e','i','o','u','u','n'],
        $s
    );
}

// ── Get or create chat session ─────────────────────────────────
if (!$sessionId) {
    $sessionId = bin2hex(random_bytes(16));
    Database::insert('chat_sessions', [
        'session_token'  => $sessionId,
        'visitor_name'   => $visitorName,
        'visitor_ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
        'page_url'       => $_SERVER['HTTP_REFERER'] ?? 'sucesionlegal.com.ar',
        'status'         => 'bot',
    ]);
}

// ── Log visitor message ────────────────────────────────────────
$session = Database::fetch("SELECT id FROM chat_sessions WHERE session_token = ?", [$sessionId]);
$sessionDbId = $session['id'] ?? null;

if ($sessionDbId) {
    Database::insert('chat_messages', [
        'session_id'  => $sessionDbId,
        'sender_type' => 'visitor',
        'content'     => $message,
    ]);
}

// ── Find matching bot response ─────────────────────────────────
$norm = normalize_msg($message);
$rows = Database::fetchAll(
    "SELECT * FROM chat_bot_responses WHERE is_active = 1 ORDER BY priority DESC, id ASC",
    []
);

$matched = null;
foreach ($rows as $r) {
    $keywords = json_decode($r['trigger_keywords'] ?? '[]', true) ?? [];
    foreach ($keywords as $kw) {
        if (str_contains($norm, normalize_msg((string)$kw))) {
            $matched = $r;
            break 2;
        }
    }
}

$responseText = $matched
    ? $matched['response_text']
    : 'Para darte una respuesta precisa, contactá al estudio: 📱 WhatsApp +54 9 11 6848-0793. Primera consulta sin cargo.';

$quickReplies = $matched
    ? (json_decode($matched['quick_replies'] ?? '[]', true) ?? [])
    : [['text'=>'WhatsApp','value'=>'whatsapp']];

// ── Log bot response ───────────────────────────────────────────
if ($sessionDbId) {
    Database::insert('chat_messages', [
        'session_id'  => $sessionDbId,
        'sender_type' => 'bot',
        'content'     => $responseText,
    ]);
}

echo json_encode([
    'response'      => $responseText,
    'quick_replies' => $quickReplies,
    'type'          => $matched['response_type'] ?? 'text',
    'session_id'    => $sessionId,
    'matched'       => $matched !== null,
]);

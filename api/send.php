<?php
require_once dirname(__DIR__) . '/config/config.php';
auth_required();

$method = $_SERVER['REQUEST_METHOD'];

// GET endpoints for inbox
if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    $mgr    = new EmailManager();
    switch ($action) {
        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            $e  = $mgr->getEmail($id);
            if (!$e) json_response(['error' => 'No encontrado'], 404);
            json_response($e);
        default:
            json_response(['error' => 'Acción desconocida'], 400);
    }
}

if ($method !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}
if (!csrf_verify()) {
    json_response(['error' => 'Token inválido'], 403);
}

$action = $_POST['action'] ?? 'send';
$mgr    = new EmailManager();

$toRaw  = trim($_POST['to']  ?? '');
$ccRaw  = trim($_POST['cc']  ?? '');
$bccRaw = trim($_POST['bcc'] ?? '');

$toList  = array_filter(array_map('trim', explode(',', $toRaw)));
$ccList  = array_filter(array_map('trim', explode(',', $ccRaw)));
$bccList = array_filter(array_map('trim', explode(',', $bccRaw)));

$data = [
    'user_id'    => $_SESSION['user_id'],
    'to'         => $toList,
    'cc'         => $ccList,
    'bcc'        => $bccList,
    'subject'    => trim($_POST['subject']    ?? ''),
    'from_email' => trim($_POST['from_email'] ?? MAIL_FROM_EMAIL),
    'from_name'  => MAIL_FROM_NAME,
    'body_html'  => $_POST['body_html'] ?? '',
    'body_text'  => strip_tags($_POST['body_html'] ?? ''),
    'priority'   => $_POST['priority'] ?? 'normal',
    'attachments'=> $_FILES['attachments'] ?? [],
];

// Expand FILES array
$attachments = [];
if (!empty($_FILES['attachments']['name'])) {
    $count = count($_FILES['attachments']['name']);
    for ($i = 0; $i < $count; $i++) {
        $attachments[] = [
            'name'     => $_FILES['attachments']['name'][$i],
            'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
            'size'     => $_FILES['attachments']['size'][$i],
            'type'     => $_FILES['attachments']['type'][$i],
            'error'    => $_FILES['attachments']['error'][$i],
        ];
    }
}
$data['attachments'] = $attachments;

switch ($action) {
    case 'send':
        $result = $mgr->send($data);
        json_response($result);

    case 'draft':
        $emailId = !empty($_POST['email_id']) ? (int)$_POST['email_id'] : null;
        if ($emailId) {
            Database::update('emails', [
                'to_emails' => json_encode($toList),
                'subject'   => $data['subject'],
                'body_html' => $data['body_html'],
            ], ['id' => $emailId, 'type' => 'draft']);
            json_response(['success' => true, 'email_id' => $emailId]);
        } else {
            $id = $mgr->saveDraft($data);
            json_response(['success' => true, 'email_id' => $id]);
        }

    case 'schedule':
        $scheduledAt = $_POST['scheduled_at'] ?? '';
        if (!$scheduledAt) {
            json_response(['error' => 'Fecha de programación requerida'], 400);
        }
        $result = $mgr->sendScheduled($data, $scheduledAt);
        json_response($result);

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            Database::delete('emails', ['id' => $id]);
            json_response(['success' => true]);
        }
        json_response(['error' => 'ID requerido'], 400);

    case 'cancel':
        $id = (int)($_POST['id'] ?? 0);
        $mgr2 = new EmailManager();
        json_response(['success' => $mgr2->cancelScheduled($id)]);

    default:
        json_response(['error' => 'Acción desconocida'], 400);
}

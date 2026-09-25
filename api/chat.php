<?php
require_once dirname(__DIR__) . '/config/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$mgr    = new ChatManager();

// Public endpoints (for widget visitors)
$publicActions = ['create_session', 'send_visitor', 'get_messages_public', 'widget_config'];

if (!in_array($action, $publicActions)) {
    auth_required();
}

if ($method === 'GET') {
    switch ($action) {
        case 'messages':
            $sessionId = (int)($_GET['session'] ?? 0);
            $since     = (int)($_GET['since']   ?? 0);
            if (!$sessionId) json_response(['error' => 'Session requerida'], 400);
            $msgs = $mgr->getMessages($sessionId, $since);
            $mgr->markRead($sessionId, 'agent');
            json_response(['messages' => $msgs]);

        case 'sessions':
            $status = $_GET['status'] ?? '';
            $data   = $mgr->getSessions($status);
            json_response($data);

        case 'canned':
            $search = $_GET['q'] ?? '';
            json_response($mgr->getCanned($search));

        case 'stats':
            json_response($mgr->getStats());

        // Public: visitor polls new messages
        case 'get_messages_public':
            $token = $_GET['token'] ?? '';
            $since = (int)($_GET['since'] ?? 0);
            if (!$token) json_response(['error' => 'Token requerido'], 400);
            $session = $mgr->getSessionByToken($token);
            if (!$session) json_response(['error' => 'Sesión no encontrada'], 404);
            $msgs = $mgr->getMessages($session['id'], $since);
            $mgr->markRead($session['id'], 'visitor');
            json_response([
                'messages' => $msgs,
                'status'   => $session['status'],
                'agent'    => $session['agent_name'] ?? null,
            ]);

        // Public: widget config
        case 'widget_config':
            $apiKey = $_GET['key'] ?? '';
            $widget = Database::fetch("SELECT * FROM chat_widgets WHERE api_key = ? AND is_active = 1", [$apiKey]);
            if (!$widget) json_response(['error' => 'Widget no encontrado'], 404);
            unset($widget['api_key']);
            json_response($widget);

        default:
            json_response(['error' => 'Acción desconocida'], 400);
    }
}

if ($method === 'POST') {
    // CSRF only for agent actions
    if (!in_array($action, $publicActions) && !csrf_verify()) {
        json_response(['error' => 'Token inválido'], 403);
    }

    switch ($action) {
        case 'send':
            $sessionId = (int)($_POST['session_id'] ?? 0);
            $content   = trim($_POST['content'] ?? '');
            if (!$sessionId || !$content) json_response(['error' => 'Datos incompletos'], 400);

            $result = $mgr->sendMessage($sessionId, [
                'sender_type' => $_POST['sender_type'] ?? 'agent',
                'sender_id'   => $_SESSION['user_id']  ?? null,
                'sender_name' => $_SESSION['user_name'] ?? null,
                'content'     => $content,
                'message_type'=> $_POST['message_type'] ?? 'text',
            ]);
            json_response($result);

        // Public: visitor sends message
        case 'send_visitor':
            $token   = $_POST['token']   ?? '';
            $content = trim($_POST['content'] ?? '');
            if (!$token || !$content) json_response(['error' => 'Datos incompletos'], 400);
            $session = $mgr->getSessionByToken($token);
            if (!$session) json_response(['error' => 'Sesión no encontrada'], 404);
            $result = $mgr->sendMessage($session['id'], [
                'sender_type' => 'visitor',
                'sender_name' => $session['visitor_name'],
                'content'     => $content,
            ]);
            json_response($result);

        // Public: create chat session
        case 'create_session':
            $name  = trim($_POST['name']  ?? '');
            $email = trim($_POST['email'] ?? '');
            $result = $mgr->createSession([
                'name'     => $name  ?: 'Visitante',
                'email'    => $email ?: null,
                'page_url' => $_POST['page_url'] ?? null,
                'referrer' => $_POST['referrer']  ?? null,
            ]);
            json_response($result);

        case 'assign':
            $sessionId = (int)($_POST['session_id'] ?? 0);
            $agentId   = (int)($_POST['agent_id']   ?? 0);
            $ok = $mgr->assignAgent($sessionId, $agentId);
            json_response(['success' => $ok]);

        case 'close':
            $sessionId = (int)($_POST['session_id'] ?? 0);
            $ok = $mgr->closeSession($sessionId);
            json_response(['success' => $ok]);

        case 'to_email':
            $sessionId = (int)($_POST['session_id'] ?? 0);
            $emailId   = $mgr->transferToEmail($sessionId, $_SESSION['user_id']);
            json_response(['success' => (bool)$emailId, 'email_id' => $emailId]);

        case 'upload':
            $sessionId = (int)($_POST['session_id'] ?? 0);
            $file      = $_FILES['file'] ?? null;
            if (!$sessionId || !$file) json_response(['error' => 'Datos incompletos'], 400);
            $result = $mgr->uploadChatFile($sessionId, $file);
            json_response($result);

        default:
            json_response(['error' => 'Acción desconocida'], 400);
    }
}

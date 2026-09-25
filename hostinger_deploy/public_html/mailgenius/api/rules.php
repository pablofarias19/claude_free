<?php
require_once dirname(__DIR__) . '/config/config.php';
auth_required();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$eng    = new ResponseEngine();

if ($method === 'GET') {
    switch ($action) {
        case 'list':
            json_response(['data' => $eng->getRules()]);

        case 'categories':
            json_response($eng->getCategories());

        default:
            json_response(['error' => 'Acción desconocida'], 400);
    }
}

if ($method === 'POST') {
    if (!csrf_verify()) json_response(['error' => 'Token inválido'], 403);

    switch ($action) {
        case 'save':
            $id  = (int)($_POST['id'] ?? 0) ?: null;
            $ret = $eng->saveRule($_POST, $id);
            json_response(['success' => true, 'id' => $ret]);

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            json_response(['success' => $eng->deleteRule($id)]);

        case 'toggle':
            $id    = (int)($_POST['id'] ?? 0);
            $state = (int)($_POST['is_active'] ?? 0);
            Database::update('response_rules', ['is_active' => $state], ['id' => $id]);
            json_response(['success' => true]);

        case 'reorder':
            $order = json_decode($_POST['order'] ?? '[]', true);
            foreach ($order as $priority => $ruleId) {
                Database::update('response_rules', ['priority' => (int)$priority + 1], ['id' => (int)$ruleId]);
            }
            json_response(['success' => true]);

        case 'test':
            $subject = trim($_POST['subject'] ?? '');
            $body    = trim($_POST['body']    ?? '');
            $from    = trim($_POST['from']    ?? '');
            $result  = $eng->classify(['subject' => $subject, 'body' => $body, 'from' => $from]);
            json_response(['matched' => $result]);

        // Bot responses
        case 'save_bot':
            $id   = (int)($_POST['id'] ?? 0) ?: null;
            $data = [
                'trigger_word'   => trim($_POST['trigger_word'] ?? ''),
                'response_text'  => trim($_POST['response_text'] ?? ''),
                'quick_replies'  => $_POST['quick_replies'] ?? '[]',
                'is_exact_match' => (int)($_POST['is_exact_match'] ?? 0),
                'priority'       => (int)($_POST['priority'] ?? 1),
                'category'       => trim($_POST['category'] ?? 'General') ?: 'General',
                'is_active'      => 1,
            ];
            if (!$data['trigger_word'] || !$data['response_text']) {
                json_response(['error' => 'Campos requeridos'], 400);
            }
            if ($id) {
                Database::update('chat_bot_responses', $data, ['id' => $id]);
                json_response(['success' => true, 'id' => $id]);
            } else {
                $newId = Database::insert('chat_bot_responses', $data);
                json_response(['success' => true, 'id' => $newId]);
            }

        case 'delete_bot':
            $id = (int)($_POST['id'] ?? 0);
            Database::delete('chat_bot_responses', ['id' => $id]);
            json_response(['success' => true]);

        case 'toggle_bot':
            $id    = (int)($_POST['id'] ?? 0);
            $state = (int)($_POST['is_active'] ?? 0);
            Database::update('chat_bot_responses', ['is_active' => $state], ['id' => $id]);
            json_response(['success' => true]);

        // Canned replies
        case 'toggle_canned':
            $id    = (int)($_POST['id'] ?? 0);
            $state = (int)($_POST['is_active'] ?? 0);
            Database::update('chat_canned', ['is_active' => $state], ['id' => $id]);
            json_response(['success' => true]);

        case 'save_canned':
            $id   = (int)($_POST['id'] ?? 0) ?: null;
            $data = [
                'title'    => trim($_POST['title']    ?? ''),
                'shortcut' => trim($_POST['shortcut'] ?? '') ?: null,
                'content'  => trim($_POST['content']  ?? ''),
                'category' => trim($_POST['category'] ?? '') ?: null,
            ];
            if (!$data['title'] || !$data['content']) json_response(['error' => 'Campos requeridos'], 400);
            if ($id) {
                Database::update('chat_canned', $data, ['id' => $id]);
                json_response(['success' => true, 'id' => $id]);
            } else {
                $newId = Database::insert('chat_canned', $data);
                json_response(['success' => true, 'id' => $newId]);
            }

        case 'delete_canned':
            $id = (int)($_POST['id'] ?? 0);
            Database::delete('chat_canned', ['id' => $id]);
            json_response(['success' => true]);

        default:
            json_response(['error' => 'Acción desconocida'], 400);
    }
}

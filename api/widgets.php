<?php
require_once dirname(__DIR__) . '/config/config.php';
auth_required();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($method === 'GET') {
    switch ($action) {
        case 'list':
            $rows = Database::fetchAll("SELECT * FROM chat_widgets ORDER BY created_at DESC");
            json_response(['data' => $rows]);

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            $w  = Database::fetch("SELECT * FROM chat_widgets WHERE id = ?", [$id]);
            if (!$w) json_response(['error' => 'No encontrado'], 404);
            json_response($w);

        default:
            json_response(['error' => 'Acción desconocida'], 400);
    }
}

if ($method === 'POST') {
    if (!csrf_verify()) json_response(['error' => 'Token inválido'], 403);

    switch ($action) {
        case 'save':
            $id   = (int)($_POST['id'] ?? 0) ?: null;
            $data = [
                'name'            => trim($_POST['name'] ?? ''),
                'primary_color'   => trim($_POST['primary_color'] ?? '#4f46e5'),
                'position'        => in_array($_POST['position'] ?? '', ['bottom-right','bottom-left']) ? $_POST['position'] : 'bottom-right',
                'domain'          => trim($_POST['allowed_domains'] ?? '') ?: null,
                'is_active'       => (int)($_POST['is_active'] ?? 1),
            ];
            if (!$data['name']) json_response(['error' => 'Nombre requerido'], 400);

            if ($id) {
                Database::update('chat_widgets', $data, ['id' => $id]);
                json_response(['success' => true, 'id' => $id]);
            } else {
                $data['api_key'] = bin2hex(random_bytes(16));
                $newId = Database::insert('chat_widgets', $data);
                json_response(['success' => true, 'id' => $newId, 'api_key' => $data['api_key']]);
            }

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            Database::delete('chat_widgets', ['id' => $id]);
            json_response(['success' => true]);

        case 'toggle':
            $id = (int)($_POST['id'] ?? 0);
            $w  = Database::fetch("SELECT is_active FROM chat_widgets WHERE id = ?", [$id]);
            if (!$w) json_response(['error' => 'No encontrado'], 404);
            Database::update('chat_widgets', ['is_active' => $w['is_active'] ? 0 : 1], ['id' => $id]);
            json_response(['success' => true, 'is_active' => !$w['is_active']]);

        default:
            json_response(['error' => 'Acción desconocida'], 400);
    }
}

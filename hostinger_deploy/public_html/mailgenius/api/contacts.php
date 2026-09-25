<?php
require_once dirname(__DIR__) . '/config/config.php';
auth_required();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$mgr    = new ContactManager();

if ($method === 'GET') {
    switch ($action) {
        case 'list':
            $groupId = (int)($_GET['group'] ?? 0);
            $search  = $_GET['q'] ?? '';
            json_response(['data' => $mgr->getAll($groupId, $search)]);

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            $c  = $mgr->get($id);
            if (!$c) json_response(['error' => 'No encontrado'], 404);
            json_response($c);

        case 'groups':
            json_response($mgr->getGroups());

        case 'stats':
            json_response($mgr->getStats());

        default:
            json_response(['error' => 'Acción desconocida'], 400);
    }
}

if ($method === 'POST') {
    if (!csrf_verify()) json_response(['error' => 'Token inválido'], 403);

    switch ($action) {
        case 'save':
            $id  = (int)($_POST['id'] ?? 0) ?: null;
            $ret = $mgr->save($_POST, $id);
            json_response(['success' => true, 'id' => $ret]);

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            json_response(['success' => $mgr->delete($id)]);

        case 'delete_bulk':
            $ids = array_map('intval', json_decode($_POST['ids'] ?? '[]', true));
            $count = 0;
            foreach ($ids as $id) {
                if ($mgr->delete($id)) $count++;
            }
            json_response(['success' => true, 'deleted' => $count]);

        case 'import':
            $file = $_FILES['csv'] ?? null;
            if (!$file) json_response(['error' => 'Archivo requerido'], 400);
            $groupId = (int)($_POST['group_id'] ?? 0) ?: null;
            $result  = $mgr->importCsv($file['tmp_name'], $groupId);
            json_response($result);

        case 'save_group':
            $id   = (int)($_POST['id'] ?? 0) ?: null;
            $name = trim($_POST['name'] ?? '');
            if (!$name) json_response(['error' => 'Nombre requerido'], 400);
            $ret  = $mgr->saveGroup(['name' => $name, 'description' => $_POST['description'] ?? null], $id);
            json_response(['success' => true, 'id' => $ret]);

        case 'delete_group':
            $id = (int)($_POST['id'] ?? 0);
            Database::delete('contact_groups', ['id' => $id]);
            json_response(['success' => true]);

        default:
            json_response(['error' => 'Acción desconocida'], 400);
    }
}

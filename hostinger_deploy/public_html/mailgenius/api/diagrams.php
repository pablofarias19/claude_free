<?php
require_once dirname(__DIR__) . '/config/config.php';
auth_required();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($method === 'GET') {
    switch ($action) {
        case 'list':
            $rows = Database::fetchAll("SELECT d.*, rc.name AS cat_name FROM diagrams d LEFT JOIN response_categories rc ON rc.id = d.category_id ORDER BY d.updated_at DESC");
            json_response(['data' => $rows]);

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            $d  = Database::fetch("SELECT * FROM diagrams WHERE id = ?", [$id]);
            if (!$d) json_response(['error' => 'No encontrado'], 404);
            json_response($d);

        default:
            json_response(['error' => 'Acción desconocida'], 400);
    }
}

if ($method === 'POST') {
    if (!csrf_verify()) json_response(['error' => 'Token inválido'], 403);

    switch ($action) {
        case 'create':
            $name = trim($_POST['name'] ?? '');
            if (!$name) json_response(['error' => 'Nombre requerido'], 400);
            $id = Database::insert('diagrams', [
                'name'        => $name,
                'description' => $_POST['description'] ?? null,
                'category_id' => $_POST['category_id'] ?: null,
                'nodes'       => '[]',
                'connections' => '[]',
                'is_active'   => 1,
            ]);
            json_response(['success' => true, 'id' => $id]);

        case 'save':
            $id          = (int)($_POST['id'] ?? 0);
            $nodesJson   = $_POST['nodes']       ?? '[]';
            $connsJson   = $_POST['connections'] ?? '[]';

            // Validate JSON
            json_decode($nodesJson);
            if (json_last_error() !== JSON_ERROR_NONE) {
                json_response(['error' => 'JSON de nodos inválido'], 400);
            }

            if ($id) {
                Database::update('diagrams', [
                    'name'        => trim($_POST['name'] ?? ''),
                    'category_id' => $_POST['category_id'] ?: null,
                    'nodes'       => $nodesJson,
                    'connections' => $connsJson,
                ], ['id' => $id]);
                json_response(['success' => true]);
            } else {
                $newId = Database::insert('diagrams', [
                    'name'        => trim($_POST['name'] ?? ''),
                    'category_id' => $_POST['category_id'] ?: null,
                    'nodes'       => $nodesJson,
                    'connections' => $connsJson,
                    'is_active'   => 1,
                ]);
                json_response(['success' => true, 'id' => $newId]);
            }

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            Database::delete('diagrams', ['id' => $id]);
            json_response(['success' => true]);

        case 'toggle':
            $id = (int)($_POST['id'] ?? 0);
            $d  = Database::fetch("SELECT is_active FROM diagrams WHERE id = ?", [$id]);
            if (!$d) json_response(['error' => 'No encontrado'], 404);
            Database::update('diagrams', ['is_active' => $d['is_active'] ? 0 : 1], ['id' => $id]);
            json_response(['success' => true, 'is_active' => !$d['is_active']]);

        default:
            json_response(['error' => 'Acción desconocida'], 400);
    }
}

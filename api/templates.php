<?php
require_once dirname(__DIR__) . '/config/config.php';
auth_required();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$mgr    = new TemplateManager();

if ($method === 'GET') {
    switch ($action) {
        case 'list':
            $cat    = (int)($_GET['category'] ?? 0);
            $search = $_GET['q'] ?? '';
            json_response(['data' => $mgr->getAll($cat, $search)]);

        case 'get':
            $id  = (int)($_GET['id'] ?? 0);
            $tpl = $mgr->get($id);
            if (!$tpl) json_response(['error' => 'No encontrada'], 404);
            json_response($tpl);

        case 'categories':
            json_response($mgr->getCategories());

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

        case 'apply':
            $id   = (int)($_POST['id'] ?? 0);
            $vars = json_decode($_POST['vars'] ?? '{}', true);
            $result = $mgr->applyVars($id, $vars);
            if (!$result) json_response(['error' => 'Plantilla no encontrada'], 404);
            $mgr->incrementUsage($id);
            json_response($result);

        default:
            json_response(['error' => 'Acción desconocida'], 400);
    }
}

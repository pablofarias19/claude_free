<?php
require_once dirname(__DIR__) . '/config/config.php';
auth_required();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$mgr    = new CampaignManager();

if ($method === 'GET') {
    switch ($action) {
        case 'list':
            json_response(['data' => $mgr->getAll()]);

        case 'get':
            $id   = (int)($_GET['id'] ?? 0);
            $camp = $mgr->get($id);
            if (!$camp) json_response(['error' => 'No encontrada'], 404);
            json_response($camp);

        case 'stats':
            $id   = (int)($_GET['id'] ?? 0);
            if ($id) {
                $camp = $mgr->get($id);
                json_response($camp ?: ['error' => 'No encontrada']);
            }
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

        case 'start':
            $id = (int)($_POST['id'] ?? 0);
            json_response(['success' => $mgr->start($id)]);

        case 'pause':
            $id = (int)($_POST['id'] ?? 0);
            json_response(['success' => $mgr->pause($id)]);

        case 'schedule':
            $id  = (int)($_POST['id'] ?? 0);
            $at  = $_POST['scheduled_at'] ?? '';
            json_response(['success' => $mgr->schedule($id, $at)]);

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            json_response(['success' => $mgr->delete($id)]);

        case 'process_batch':
            $id  = (int)($_POST['id'] ?? 0);
            $cnt = $mgr->processBatch($id);
            json_response(['success' => true, 'sent' => $cnt]);

        default:
            json_response(['error' => 'Acción desconocida'], 400);
    }
}

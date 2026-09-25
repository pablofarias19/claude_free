<?php
require_once dirname(__DIR__) . '/config/config.php';
auth_required();

$action = $_GET['action'] ?? 'list';
$userId = $_SESSION['user_id'];

switch ($action) {
    case 'list':
        $rows = Database::fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30",
            [$userId]
        );
        json_response($rows);

    case 'read':
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            Database::update('notifications', ['is_read' => 1], ['id' => $id, 'user_id' => $userId]);
        }
        json_response(['success' => true]);

    case 'read_all':
        Database::get()->prepare(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ?"
        )->execute([$userId]);
        json_response(['success' => true]);

    case 'count':
        $count = Database::query(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        )->fetchColumn();
        json_response(['count' => (int)$count]);

    default:
        json_response(['error' => 'Acción desconocida'], 400);
}

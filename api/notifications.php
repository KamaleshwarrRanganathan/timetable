<?php
require_once 'config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $user_id = $_GET['user_id'] ?? null;
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT n.*, od.status as od_status FROM notifications n LEFT JOIN od_requests od ON n.related_id = od.id AND n.type = 'od_request' WHERE n.user_id = ? ORDER BY n.created_at DESC");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    else {
        echo json_encode([]);
    }
    exit();
}

if ($method === 'PUT' && isset($routeParts[2]) && $routeParts[2] === 'read') {
    $id = $routeParts[1];
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(["message" => "Marked read"]);
    exit();
}

http_response_code(404);
echo json_encode(["error" => "Endpoint not found"]);
?>

<?php
require_once 'config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    // Find a substitute (different from current)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'teacher' AND id != ? LIMIT 1");
    $stmt->execute([$data->teacher_id]);
    $substitute = $stmt->fetch();
    $subId = $substitute ? $substitute['id'] : null;
    
    // Save absence
    $stmt = $pdo->prepare("INSERT INTO absences (teacher_id, absence_date, reason, substitute_teacher_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$data->teacher_id, $data->date, $data->reason, $subId]);
    
    // Create Notifications
    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $notifStmt->execute([$data->teacher_id, "Your absence for {$data->date} is registered. Substitute assigned."]);
    
    if ($subId) {
        $notifStmt->execute([$subId, "You have been assigned as a substitute on {$data->date}."]);
    }
    
    echo json_encode(["message" => "Absence recorded and substitute assigned.", "substitute_id" => $subId]);
    exit();
}

http_response_code(404);
echo json_encode(["error" => "Endpoint not found"]);
?>

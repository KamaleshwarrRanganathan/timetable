<?php
require_once 'config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($routeParts[1]) ? $routeParts[1] : '';

if ($method === 'GET') {
    if ($action === 'teachers') {
        $stmt = $pdo->query("SELECT id, username, role, name, email FROM users WHERE role != 'admin'");
        echo json_encode($stmt->fetchAll());
        exit();
    }
    
    // Generic GET for subjects, classrooms, classes
    $allowed_tables = ['subjects', 'classrooms', 'classes'];
    if (in_array($action, $allowed_tables)) {
        $stmt = $pdo->query("SELECT * FROM $action");
        echo json_encode($stmt->fetchAll());
        exit();
    }
    
    http_response_code(404);
    echo json_encode(["error" => "Resource not found"]);

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if ($action === 'teachers') {
        $password = password_hash($data->password ?? '123', PASSWORD_BCRYPT);
        $role = $data->role ?? 'teacher';
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, name, email) VALUES (?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$data->username, $password, $role, $data->name, $data->email]);
            echo json_encode(["message" => "User added successfully", "id" => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode(["error" => "Could not add user. Username/Email might exist."]);
        }
        exit();
    }
    
    if ($action === 'subjects') {
        $stmt = $pdo->prepare("INSERT INTO subjects (name, code) VALUES (?, ?)");
        $stmt->execute([$data->name, $data->code]);
        echo json_encode(["message" => "Subject added successfully"]);
        exit();
    }
    
    if ($action === 'classrooms') {
        $stmt = $pdo->prepare("INSERT INTO classrooms (name, capacity) VALUES (?, ?)");
        $stmt->execute([$data->name, $data->capacity]);
        echo json_encode(["message" => "Classroom added successfully"]);
        exit();
    }
    
    if ($action === 'classes') {
        $stmt = $pdo->prepare("INSERT INTO classes (name) VALUES (?)");
        $stmt->execute([$data->name]);
        echo json_encode(["message" => "Class added successfully"]);
        exit();
    }
    
    http_response_code(404);
    echo json_encode(["error" => "Resource not found"]);
}
?>

<?php
require_once 'config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($routeParts[1]) ? $routeParts[1] : '';

if ($method === 'POST' && $action === 'login') {
    $data = json_decode(file_get_contents("php://input"));
    
    // Frontend sends actual username ID in 'username' field, EXCEPT for student where it sends 
    // it in 'username' (as roll no) and actual name in 'name'. 
    // Wait, looking at frontend: student sends rollno in 'username'. HOD sends username in 'username'.
    
    if (!isset($data->username) || !isset($data->password) || !isset($data->role)) {
        http_response_code(400);
        echo json_encode(["error" => "Missing credentials"]);
        exit();
    }
    
    $loginId = $data->username; // This holds 'hod1' for HOD, 'student1' for Student
    $password = $data->password;
    $role = $data->role;
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, password, role, name, email FROM users WHERE username = ? AND role = ?");
        $stmt->execute([$loginId, $role]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Success
            echo json_encode([
                "token" => "mock-jwt-token-" . $user['id'], // Mock JWT for simplicity
                "user" => [
                    "id" => $user['id'],
                    "username" => $user['username'],
                    "role" => $user['role'],
                    "name" => $user['name']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                "error" => "Invalid credentials or role.",
                "debug_payload" => ["loginId" => $loginId, "role" => $role, "pwd" => $password],
                "debug_db_found" => $user ? true : false
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
?>

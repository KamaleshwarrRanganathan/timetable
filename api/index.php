<?php
// Handle CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle Preflight Request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$routeString = $_GET['route'] ?? '';
$routeString = ltrim($routeString, '/');

if (empty($routeString)) {
    echo json_encode(["message" => "Automated Scheduling System PHP API is running..."]);
    exit();
}

// Clean query strings if they arrived in the route string
if (strpos($routeString, '?') !== false) {
    $routeString = explode('?', $routeString)[0];
}

$routeParts = explode('/', $routeString);
$resource = $routeParts[0]; // e.g., auth, admin, timetable

// Route requests to appropriate files
switch ($resource) {
    case 'auth':
        require_once 'auth.php';
        break;
    case 'admin':
        require_once 'admin.php';
        break;
    case 'timetable':
        require_once 'timetable.php';
        break;
    case 'notifications':
        require_once 'notifications.php';
        break;
    case 'absences':
        require_once 'absences.php';
        break;
    case 'student':
        require_once 'student.php';
        break;
    default:
        http_response_code(404);
        echo json_encode(["error" => "Endpoint not found"]);
        break;
}
?>

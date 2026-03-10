<?php
$host = 'localhost';
$db_name = 'scheduling_system';
$username = 'root';
$password = '';

try {
    $dsn = "mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die(json_encode([
        "error" => "Connection failed: " . $e->getMessage()
    ]));
}
?>

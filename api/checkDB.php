<?php
require_once 'config/db.php';
try {
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($users);
    
    // Check column definitions for users table
    $schemaStmt = $pdo->query("DESCRIBE users");
    print_r($schemaStmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

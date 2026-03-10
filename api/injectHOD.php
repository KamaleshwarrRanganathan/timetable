<?php
require_once 'config/db.php';
$passwordHash = password_hash('123', PASSWORD_BCRYPT);
try {
    // Delete any existing hod1 just in case role string mismatched
    $pdo->exec("DELETE FROM users WHERE username = 'hod1'");
    
    // Explicitly insert hod1 with role 'hod' matching frontend exactly
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, name, email) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['hod1', $passwordHash, 'hod', 'Jane Smith', 'hod@college.edu']);
    
    echo "HOD successfully (re)injected into database.";
} catch (Exception $e) {
    echo "Error inserting HOD: " . $e->getMessage();
}
?>

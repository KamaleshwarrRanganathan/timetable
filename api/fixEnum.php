<?php
require_once 'config/db.php';

try {
    // 1. Update the schema ENUM to allow 'hod'
    echo "Updating schema ENUM constraint...<br>";
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'teacher', 'student', 'hod') NOT NULL");
    
    // 2. Delete any corrupted mock users
    $pdo->exec("DELETE FROM users WHERE username = 'hod1'");
    
    // 3. Inject HOD Correctly
    echo "Inserting HOD user...<br>";
    $passwordHash = password_hash('123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, name, email) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['hod1', $passwordHash, 'hod', 'Jane Smith', 'hod@college.edu']);
    
    echo "Done! The database now officially supports the HOD role!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

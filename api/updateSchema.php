<?php
require_once 'config/db.php';

try {
    echo "Updating users table...<br>";
    $pdo->exec("ALTER TABLE users 
                ADD COLUMN course_year INT DEFAULT NULL AFTER email,
                ADD COLUMN section VARCHAR(10) DEFAULT NULL AFTER course_year");
    
    echo "Updating classes table...<br>";
    $pdo->exec("ALTER TABLE classes 
                ADD COLUMN course_year INT DEFAULT NULL AFTER name,
                ADD COLUMN section VARCHAR(10) DEFAULT NULL AFTER course_year");
                
    echo "Database schema successfully updated with Course Years and Sections!";
} catch (Exception $e) {
    echo "Error updating schema: " . $e->getMessage();
}
?>

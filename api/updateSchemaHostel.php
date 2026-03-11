<?php
require_once 'config/db.php';
try {
    $pdo->exec("ALTER TABLE student_profiles ADD COLUMN hostel VARCHAR(10) DEFAULT 'No'");
    echo "Column added successfully";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
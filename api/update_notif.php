<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo->exec("ALTER TABLE notifications ADD COLUMN type VARCHAR(50) DEFAULT 'general'");
    $pdo->exec("ALTER TABLE notifications ADD COLUMN related_id INT DEFAULT NULL");
    echo "Columns added successfully";
}
catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist";
    }
    else {
        echo "Error: " . $e->getMessage();
    }
}
?>

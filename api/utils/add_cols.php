<?php
require 'api/config/db.php';
try {
    $pdo->exec('ALTER TABLE subjects ADD COLUMN course_year INT');
    $pdo->exec('ALTER TABLE subjects ADD COLUMN semester INT');
    echo "Columns added successfully.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

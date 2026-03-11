<?php
require_once __DIR__ . '/../config/db.php';

try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $schema = [];
    foreach ($tables as $table) {
        $stmt = $pdo->query("DESCRIBE `$table`");
        $schema[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    echo json_encode($schema, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo $e->getMessage();
}

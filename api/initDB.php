<?php
$host = 'localhost';
$username = 'root';
$password = '';
$db_name = 'scheduling_system';

try {
    // Connect without DB to create it
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Creating database if it doesn't exist...<br>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
    $pdo->exec("USE `$db_name`");
    
    echo "Reading schema.sql...<br>";
    $schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
    if ($schema === false) {
        throw new Exception("Could not read schema.sql");
    }
    
    // Execute schema statements
    $queries = explode(';', $schema);
    foreach ($queries as $query) {
        $trimmed = trim($query);
        if (!empty($trimmed)) {
            $pdo->exec($trimmed);
        }
    }
    echo "Schema imported successfully.<br>";
    
    echo "Seeding default users...<br>";
    $defaultPass = password_hash('123', PASSWORD_BCRYPT);
    
    $users = [
        ['admin', $defaultPass, 'admin', 'Super Admin', 'admin@college.edu'],
        ['teacher1', $defaultPass, 'teacher', 'John Doe', 'john@college.edu'],
        ['hod1', $defaultPass, 'hod', 'Jane Smith', 'hod@college.edu'],
        ['student1', $defaultPass, 'student', 'Student 1', 'student@college.edu']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, role, name, email) VALUES (?, ?, ?, ?, ?)");
    foreach ($users as $u) {
        $stmt->execute($u);
    }
    
    echo "<strong>Database successfully initialized!</strong><br>";
    echo "You can now login to the application using default credentials.";
    
} catch (Exception $e) {
    echo "<strong style='color:red;'>Initialization failed:</strong> " . $e->getMessage();
}
?>

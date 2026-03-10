<?php
require_once 'config/db.php';
$passwordHash = password_hash('123', PASSWORD_BCRYPT);
$pdo->exec("UPDATE users SET password = '$passwordHash'");
echo "All passwords forcibly reset to '123' encrypt";
?>

<?php
require_once __DIR__ . '/config/db.php';

echo "Setting up teacher_profiles table if it doesn't exist...<br>\n";

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS teacher_profiles (
            user_id INT PRIMARY KEY,
            gender VARCHAR(10),
            phone VARCHAR(20),
            address TEXT,
            work_experience VARCHAR(50),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
} catch (PDOException $e) {
    echo "Warning: Error setting up table: " . $e->getMessage() . "<br>";
}

$csvFile = dirname(__DIR__) . '/database/teachers.csv';
if (!file_exists($csvFile)) {
    die("CSV file not found!");
}

$handle = fopen($csvFile, "r");
if ($handle !== FALSE) {
    // Read header (Roll No,Name,Gender,Phone,Email,Address,Password,Work Experience)
    $header = fgetcsv($handle, 1000, ",");

    $pdo->beginTransaction();
    try {
        $stmtUser = $pdo->prepare("INSERT INTO users (username, password, role, name, email) VALUES (?, ?, 'teacher', ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), email=VALUES(email), password=VALUES(password)");

        $stmtProfile = $pdo->prepare("INSERT INTO teacher_profiles (user_id, gender, phone, address, work_experience) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE gender=VALUES(gender), phone=VALUES(phone), address=VALUES(address), work_experience=VALUES(work_experience)");

        $count = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $rollNo = trim($data[0]);
            $name = trim($data[1]);
            $gender = trim($data[2]);
            $phone = trim($data[3]);
            $email = trim($data[4]);
            $address = trim($data[5]);
            $password = password_hash(trim($data[6]), PASSWORD_BCRYPT);
            $workExperience = trim($data[7]);

            // Insert user
            $stmtUser->execute([$rollNo, $password, $name, $email]);

            // Get user_id
            $stmtGetId = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmtGetId->execute([$rollNo]);
            $userId = $stmtGetId->fetchColumn();

            // Insert profile
            $stmtProfile->execute([$userId, $gender, $phone, $address, $workExperience]);

            $count++;
        }
        $pdo->commit();
        echo "Successfully inserted/updated $count teachers.\n";
    }
    catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
    fclose($handle);
}
else {
    echo "Could not open CSV file.";
}
?>

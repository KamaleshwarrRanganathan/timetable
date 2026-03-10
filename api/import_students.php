<?php
require_once __DIR__ . '/config/db.php';

echo "Updating schema to include new columns...<br>\n";
// Quick alter if table exists
try {
    $pdo->exec("ALTER TABLE student_profiles ADD COLUMN dob DATE");
    $pdo->exec("ALTER TABLE student_profiles ADD COLUMN gender VARCHAR(10)");
    $pdo->exec("ALTER TABLE student_profiles ADD COLUMN course VARCHAR(100)");
}
catch (PDOException $e) {
// Ignore error if columns already exist
}

$csvFile = dirname(__DIR__) . '/database/students.csv';
if (!file_exists($csvFile)) {
    die("CSV file not found!");
}

$handle = fopen($csvFile, "r");
if ($handle !== FALSE) {
    // Read header
    $header = fgetcsv($handle, 1000, ",");

    $pdo->beginTransaction();
    try {
        $stmtUser = $pdo->prepare("INSERT INTO users (username, password, role, name, email, course_year, section) VALUES (?, ?, 'student', ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), email=VALUES(email), password=VALUES(password), course_year=VALUES(course_year), section=VALUES(section)");

        $stmtProfile = $pdo->prepare("INSERT INTO student_profiles (user_id, dob, gender, course, phone, address, semester, fees) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE dob=VALUES(dob), gender=VALUES(gender), course=VALUES(course), phone=VALUES(phone), address=VALUES(address), semester=VALUES(semester), fees=VALUES(fees)");

        $count = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // CSV columns: Roll No.,Name,DOB,Gender,Gmail,Phone,Address,Course,Current Semester,Year,Section,Fees,Password
            $rollNo = $data[0];
            $name = $data[1];
            $dob = $data[2];
            $gender = $data[3];
            $email = $data[4];
            $phone = $data[5];
            $address = $data[6];
            $course = $data[7];
            $semester = $data[8];
            $year = $data[9];
            $section = $data[10];
            $fees = $data[11];
            $password = password_hash($data[12], PASSWORD_BCRYPT);

            // Insert user
            $stmtUser->execute([$rollNo, $password, $name, $email, $year, $section]);

            // Get user_id
            $stmtGetId = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmtGetId->execute([$rollNo]);
            $userId = $stmtGetId->fetchColumn();

            // Insert profile
            $stmtProfile->execute([$userId, $dob, $gender, $course, $phone, $address, $semester, $fees]);

            $count++;
        }
        $pdo->commit();
        echo "Successfully inserted/updated $count students.\n";
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

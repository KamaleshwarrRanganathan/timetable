<?php
require_once __DIR__ . '/config/db.php';

$csvFile = dirname(__DIR__) . '/database/subjects.csv';
if (!file_exists($csvFile)) {
    die("CSV file not found!");
}

$handle = fopen($csvFile, "r");
if ($handle !== FALSE) {
    // Read header: Year,Semester,Course Code,Subject Name,Category
    $header = fgetcsv($handle, 1000, ",");

    $pdo->beginTransaction();
    try {
        $stmtSubject = $pdo->prepare("INSERT INTO subjects (name, code, course_year, semester) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), course_year=VALUES(course_year), semester=VALUES(semester)");

        $count = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $year = trim($data[0]);
            $semester = trim($data[1]);
            $code = trim($data[2]);
            $name = trim($data[3]);
            $category = trim($data[4]);

            $stmtSubject->execute([$name, $code, $year, $semester]);
            $count++;
        }
        $pdo->commit();
        echo "Successfully inserted/updated $count subjects.\n";
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

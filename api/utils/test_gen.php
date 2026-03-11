<?php
require 'api/config/db.php';
require 'api/utils/generatorLogic.php';

try {
    generateTimetable($pdo);
    echo "Timetable assigned successfully.\n";

    $stmts = [
        "Total entries" => "SELECT COUNT(*) FROM timetable",
        "Teacher Double Booking" => "SELECT teacher_id, day_of_week, start_time, COUNT(*) as c FROM timetable GROUP BY teacher_id, day_of_week, start_time HAVING c > 1",
        "Room Double Booking" => "SELECT classroom_id, day_of_week, start_time, COUNT(*) as c FROM timetable GROUP BY classroom_id, day_of_week, start_time HAVING c > 1",
        "Class Double Booking" => "SELECT class_id, day_of_week, start_time, COUNT(*) as c FROM timetable GROUP BY class_id, day_of_week, start_time HAVING c > 1"
    ];

    foreach ($stmts as $name => $sql) {
        $stmt = $pdo->query($sql);
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "[$name]: " . count($res) . " conflicts.\n";
        if (count($res) > 0) {
            print_r($res);
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

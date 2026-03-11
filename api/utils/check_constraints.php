<?php
require 'api/config/db.php';

$stmt = $pdo->query('SELECT class_id, day_of_week, subject_id, count(*) as count FROM timetable GROUP BY class_id, day_of_week, subject_id HAVING count > 2');
$res = $stmt->fetchAll();
echo "Max 2 per day violations: " . count($res) . "\n";

$stmt2 = $pdo->query('
SELECT t1.class_id, t1.day_of_week, t1.start_time, t2.start_time, t1.subject_id 
FROM timetable t1 
JOIN timetable t2 ON t1.class_id = t2.class_id AND t1.day_of_week = t2.day_of_week AND t1.subject_id = t2.subject_id 
WHERE t2.start_time > t1.start_time AND TIMEDIFF(t2.start_time, t1.end_time) <= "00:10:00"
');
$res2 = $stmt2->fetchAll();
echo "Consecutive class violations: " . count($res2) . "\n";

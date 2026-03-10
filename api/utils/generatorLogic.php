<?php
function generateTimetable($pdo) {
    // 1. Fetch all required entities
    $classesStmt = $pdo->query("SELECT * FROM classes");
    $classes = $classesStmt->fetchAll();

    $subjectsStmt = $pdo->query("SELECT * FROM subjects");
    $subjects = $subjectsStmt->fetchAll();

    $teachersStmt = $pdo->query("SELECT id, name FROM users WHERE role = 'teacher'");
    $teachers = $teachersStmt->fetchAll();

    $roomsStmt = $pdo->query("SELECT * FROM classrooms");
    $classrooms = $roomsStmt->fetchAll();

    if (empty($classes) || empty($subjects) || empty($teachers) || empty($classrooms)) {
        throw new Exception("Please add at least 1 class, 1 subject, 1 teacher, and 1 classroom first.");
    }

    // Days and time slots
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $timeSlots = [
        ['start' => '09:00:00', 'end' => '10:00:00'],
        ['start' => '10:00:00', 'end' => '11:00:00'],
        ['start' => '11:15:00', 'end' => '12:15:00']
    ];

    $pdo->beginTransaction();

    try {
        // Clear old timetable
        $pdo->exec("DELETE FROM timetable");

        // Simple round-robin assignment for demo purposes
        foreach ($classes as $c) {
            foreach ($days as $day) {
                for ($i = 0; $i < count($timeSlots); $i++) {
                    $subj = $subjects[$i % count($subjects)];
                    $teacher = $teachers[$i % count($teachers)];
                    $room = $classrooms[$i % count($classrooms)];
                    $slot = $timeSlots[$i];

                    $stmt = $pdo->prepare("INSERT INTO timetable (class_id, subject_id, teacher_id, classroom_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $c['id'], 
                        $subj['id'], 
                        $teacher['id'], 
                        $room['id'], 
                        $day, 
                        $slot['start'], 
                        $slot['end']
                    ]);
                }
            }
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>

<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->beginTransaction();

    // 1. Create missing classes (Year 1 to Year 4, Section A)
    $classesData = [
        ['name' => '1st Year A', 'course_year' => 1, 'section' => 'A'],
        ['name' => '2nd Year A', 'course_year' => 2, 'section' => 'A'],
        ['name' => '3rd Year A', 'course_year' => 3, 'section' => 'A'],
        ['name' => '4th Year A', 'course_year' => 4, 'section' => 'A']
    ];
    $insertClassStmt = $pdo->prepare("INSERT INTO classes (name, course_year, section) VALUES (?, ?, ?)");
    foreach ($classesData as $c) {
        $insertClassStmt->execute([$c['name'], $c['course_year'], $c['section']]);
    }

    // 2. Create missing classrooms
    $classroomData = [
        ['name' => 'Room 101'],
        ['name' => 'Room 102'],
        ['name' => 'Room 103'],
        ['name' => 'Room 104']
    ];
    $insertRoomStmt = $pdo->prepare("INSERT INTO classrooms (name) VALUES (?)");
    foreach ($classroomData as $r) {
        $insertRoomStmt->execute([$r['name']]);
    }

    // 3. Map subjects to teachers
    // Fetch all teachers
    $teachers = $pdo->query("SELECT id FROM users WHERE role='teacher'")->fetchAll(PDO::FETCH_COLUMN);
    // Fetch all subjects
    $subjects = $pdo->query("SELECT id FROM subjects")->fetchAll(PDO::FETCH_COLUMN);

    if (empty($teachers) || empty($subjects)) {
        throw new Exception("Teachers or subjects are missing from the database.");
    }

    $insertMappingStmt = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)");
    
    // Distribute subjects sequentially among available teachers
    $teacherCount = count($teachers);
    foreach ($subjects as $index => $subjectId) {
        $teacherId = $teachers[$index % $teacherCount];
        $insertMappingStmt->execute([$teacherId, $subjectId]);
    }

    $pdo->commit();
    echo "Successfully seeded missing classes, classrooms, and teacher-subject mappings.\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}

<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->beginTransaction();

    // 1. Create missing Section B classes (Year 1 to Year 4)
    $classesData = [
        ['name' => '1st Year B', 'course_year' => 1, 'section' => 'B'],
        ['name' => '2nd Year B', 'course_year' => 2, 'section' => 'B'],
        ['name' => '3rd Year B', 'course_year' => 3, 'section' => 'B'],
        ['name' => '4th Year B', 'course_year' => 4, 'section' => 'B']
    ];
    $insertClassStmt = $pdo->prepare("INSERT INTO classes (name, course_year, section) VALUES (?, ?, ?)");
    foreach ($classesData as $c) {
        $insertClassStmt->execute([$c['name'], $c['course_year'], $c['section']]);
    }

    // 2. Create missing classrooms for Section B
    $classroomData = [
        ['name' => 'Room 105'],
        ['name' => 'Room 106'],
        ['name' => 'Room 107'],
        ['name' => 'Room 108']
    ];
    $insertRoomStmt = $pdo->prepare("INSERT INTO classrooms (name) VALUES (?)");
    foreach ($classroomData as $r) {
        $insertRoomStmt->execute([$r['name']]);
    }

    $pdo->commit();
    echo "Successfully seeded Section B classes and new classrooms.\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}

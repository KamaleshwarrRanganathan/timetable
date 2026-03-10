<?php
require_once 'config/db.php';
require_once 'utils/generatorLogic.php'; // We will put the complex logic here

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Return formatted timetable
    $year = $_GET['year'] ?? '';
    $section = $_GET['section'] ?? '';
    
    $query = "
        SELECT t.id, t.day_of_week, t.start_time, t.end_time,
               c.name as class_name, c.course_year, c.section as class_section,
               s.name as subject_name,
               u.name as teacher_name, cr.name as classroom_name
        FROM timetable t
        JOIN classes c ON t.class_id = c.id
        JOIN subjects s ON t.subject_id = s.id
        JOIN users u ON t.teacher_id = u.id
        JOIN classrooms cr ON t.classroom_id = cr.id
        WHERE 1=1
    ";
    
    $params = [];
    if ($year !== '') {
        $query .= " AND c.course_year = ?";
        $params[] = $year;
    }
    if ($section !== '') {
        $query .= " AND c.section = ?";
        $params[] = $section;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
} elseif ($method === 'POST') {
    try {
        // Run generation logic utility
        generateTimetable($pdo);
        echo json_encode(["message" => "Timetable generated successfully!"]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(["error" => $e->getMessage()]);
    }
}
?>

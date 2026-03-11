<?php
require_once 'config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $teacher_id = $data->teacher_id;
    $date = $data->date;
    $reason = $data->reason;
    
    // Find day of week for the given date (e.g., 'Monday')
    $dayOfWeek = date('l', strtotime($date));

    // Get the absent teacher's subjects and hours for that day
    $ttStmt = $pdo->prepare("
        SELECT t.id, t.start_time, t.end_time, t.class_id, s.name as subject_name, c.name as class_name, c.course_year, c.section 
        FROM timetable t
        JOIN subjects s ON t.subject_id = s.id
        JOIN classes c ON t.class_id = c.id
        WHERE t.teacher_id = ? AND t.day_of_week = ?
    ");
    $ttStmt->execute([$teacher_id, $dayOfWeek]);
    $classesToSub = $ttStmt->fetchAll(PDO::FETCH_ASSOC);

    // Find a substitute (different from current)
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'teacher' AND id != ? LIMIT 1");
    $stmt->execute([$teacher_id]);
    $substitute = $stmt->fetch();
    $subId = $substitute ? $substitute['id'] : null;
    $subName = $substitute ? $substitute['name'] : 'Unknown';
    
    // Save absence
    $stmt = $pdo->prepare("INSERT INTO absences (teacher_id, absence_date, reason, substitute_teacher_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$teacher_id, $date, $reason, $subId]);
    
    // Create Notifications
    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)");
    
    // 1. Notify absent teacher
    $notifStmt->execute([$teacher_id, "Your absence for {$date} is registered. Substitute: {$subName}.", "absence"]);
    
    // Get HODs and Absent Teacher Name
    $hodStmt = $pdo->query("SELECT id FROM users WHERE role = 'hod'");
    $hods = $hodStmt->fetchAll();
    
    $teacherStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $teacherStmt->execute([$teacher_id]);
    $absentTeacherName = $teacherStmt->fetchColumn() ?: 'Unknown';

    if ($subId && count($classesToSub) > 0) {
        $subMessage = "You have been assigned as a substitute for {$absentTeacherName} on {$date}.\nClasses:\n";
        $hodMessage = "{$absentTeacherName} is absent on {$date}. Substitute: {$subName}.\nAffected classes:\n";

        foreach ($classesToSub as $cls) {
            $timeSlot = substr($cls['start_time'], 0, 5) . " to " . substr($cls['end_time'], 0, 5);
            $classLine = "- {$timeSlot}: {$cls['subject_name']} for {$cls['class_name']}\n";
            $subMessage .= $classLine;
            $hodMessage .= $classLine;
            
            // Notify students of this class
            $studentStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'student' AND course_year = ? AND section = ?");
            $studentStmt->execute([$cls['course_year'], $cls['section']]);
            $students = $studentStmt->fetchAll();
            
            $studentMsg = "Notice: {$absentTeacherName} is absent on {$date}. {$cls['subject_name']} ({$timeSlot}) will be handled by {$subName}.";
            foreach ($students as $student) {
                $notifStmt->execute([$student['id'], $studentMsg, "class_update"]);
            }
        }
        
        // 2. Notify Alternative (Substitute) Teacher
        $notifStmt->execute([$subId, trim($subMessage), "substitution"]);
        
        // 3. Notify HOD
        foreach ($hods as $hod) {
            $notifStmt->execute([$hod['id'], trim($hodMessage), "absence_alert"]);
        }
    } else {
        // If no classes or no substitute
        if ($subId) {
            $notifStmt->execute([$subId, "You are a substitute on {$date}, but no classes to cover.", "substitution"]);
        }
        foreach ($hods as $hod) {
            $notifStmt->execute([$hod['id'], "{$absentTeacherName} is absent on {$date}. No classes scheduled for them.", "absence_alert"]);
        }
    }
    
    echo json_encode(["message" => "Absence recorded, substitute assigned, and notifications sent.", "substitute_id" => $subId]);
    exit();
}

http_response_code(404);
echo json_encode(["error" => "Endpoint not found"]);
?>

<?php
require_once 'config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$resource = isset($routeParts[1]) ? $routeParts[1] : '';

// --- GET REQUESTS ---
if ($method === 'GET') {
    if ($resource === 'profile') {
        $user_id = $_GET['user_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT id, username, role, name, email, course_year, section FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user) {
            $pStmt = $pdo->prepare("SELECT phone, address, semester, arrears, fees FROM student_profiles WHERE user_id = ?");
            $pStmt->execute([$user_id]);
            $profile = $pStmt->fetch();
            if ($profile) {
                $user = array_merge($user, $profile);
            }
            echo json_encode($user);
            exit();
        }
        http_response_code(404);
        echo json_encode(["error" => "User not found"]);
        exit();
    }

    if ($resource === 'list') {
        $year = $_GET['year'] ?? '';
        $section = $_GET['section'] ?? '';

        $query = "SELECT u.id, u.username as roll_no, u.name, u.email, u.course_year as year, u.section, p.dob, p.gender, p.course, p.phone, p.address, p.semester, p.arrears, p.fees 
                  FROM users u 
                  LEFT JOIN student_profiles p ON u.id = p.user_id 
                  WHERE u.role = 'student'";
        $params = [];
        if ($year !== '' && strtolower($year) !== 'all') {
            $query .= " AND u.course_year = ?";
            $params[] = $year;
        }
        if ($section !== '' && strtolower($section) !== 'all') {
            $query .= " AND u.section = ?";
            $params[] = $section;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit();
    }

    if ($resource === 'results') {
        $year = $_GET['year'] ?? '';
        $section = $_GET['section'] ?? '';

        $query = "SELECT r.*, u.name as student_name FROM exam_results r JOIN users u ON r.student_id = u.id WHERE 1=1";
        $params = [];
        if ($year !== '' && strtolower($year) !== 'all') {
            $query .= " AND u.course_year = ?";
            $params[] = $year;
        }
        if ($section !== '' && strtolower($section) !== 'all') {
            $query .= " AND u.section = ?";
            $params[] = $section;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        exit();
    }

    if ($resource === 'exam-timetable') {
        $stmt = $pdo->query("SELECT * FROM exam_timetable");
        echo json_encode($stmt->fetchAll());
        exit();
    }

    if ($resource === 'od-requests') {
        $year = $_GET['year'] ?? '';
        $section = $_GET['section'] ?? '';

        $query = "
            SELECT od.id, od.student_id, od.reason, DATE_FORMAT(od.request_date, '%Y-%m-%d') as date, od.status, u.name as student_name
            FROM od_requests od
            JOIN users u ON od.student_id = u.id
            WHERE 1=1
        ";
        $params = [];
        if ($year !== '' && strtolower($year) !== 'all') {
            $query .= " AND u.course_year = ?";
            $params[] = $year;
        }
        if ($section !== '' && strtolower($section) !== 'all') {
            $query .= " AND u.section = ?";
            $params[] = $section;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        exit();
    }

    if ($resource === 'absences') {
        $year = $_GET['year'] ?? '';
        $section = $_GET['section'] ?? '';

        $query = "
            SELECT a.id, a.student_id, DATE_FORMAT(a.absence_date, '%Y-%m-%d') as date, a.reason, u.name as student_name
            FROM student_absences a
            JOIN users u ON a.student_id = u.id
            WHERE 1=1
        ";
        $params = [];
        if ($year !== '' && strtolower($year) !== 'all') {
            $query .= " AND u.course_year = ?";
            $params[] = $year;
        }
        if ($section !== '' && strtolower($section) !== 'all') {
            $query .= " AND u.section = ?";
            $params[] = $section;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        exit();
    }
}

// --- POST REQUESTS ---
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if ($resource === 'od-requests') {
        $stmt = $pdo->prepare("INSERT INTO od_requests (student_id, reason, request_date, status) VALUES (?, ?, ?, 'Pending')");
        $stmt->execute([$data->student_id, $data->reason, $data->date]);
        $od_id = $pdo->lastInsertId();

        // Notify HOD
        $hodStmt = $pdo->query("SELECT id FROM users WHERE role = 'hod' LIMIT 1");
        $hod = $hodStmt->fetch();
        if ($hod) {
            $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type, related_id) VALUES (?, ?, 'od_request', ?)");
            $notif->execute([$hod['id'], "OD Request from {$data->student_name}: {$data->reason}", $od_id]);
        }
        echo json_encode(["message" => "OD Request submitted"]);
        exit();
    }

    if ($resource === 'absences') {
        $stmt = $pdo->prepare("INSERT INTO student_absences (student_id, absence_date, reason) VALUES (?, ?, ?)");
        $stmt->execute([$data->student_id, $data->date, $data->reason]);
        echo json_encode(["message" => "Absence posted"]);
        exit();
    }
}

// --- PUT REQUESTS ---
if ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));

    if ($resource === 'profile') {
        $stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
        $stmt->execute([$data->user_id]);
        if ($stmt->fetch()) {
            $upd = $pdo->prepare("UPDATE student_profiles SET phone=?, address=?, semester=?, arrears=?, fees=? WHERE user_id=?");
            $upd->execute([$data->phone, $data->address, $data->semester, $data->arrears, $data->fees, $data->user_id]);
        }
        else {
            $ins = $pdo->prepare("INSERT INTO student_profiles (user_id, phone, address, semester, arrears, fees) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->execute([$data->user_id, $data->phone, $data->address, $data->semester, $data->arrears, $data->fees]);
        }
        echo json_encode(["message" => "Profile updated"]);
        exit();
    }

    // Action OD Request: /api/student/od-requests/{id}/{action}
    if ($resource === 'od-requests' && isset($routeParts[2]) && isset($routeParts[3])) {
        $id = $routeParts[2];
        $action = $routeParts[3];
        $status = $action === 'approve' ? 'Approved' : 'Rejected';

        $upd = $pdo->prepare("UPDATE od_requests SET status = ? WHERE id = ?");
        $upd->execute([$status, $id]);

        // Notify student
        $sel = $pdo->prepare("SELECT student_id FROM od_requests WHERE id = ?");
        $sel->execute([$id]);
        $req = $sel->fetch();
        if ($req) {
            $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type, related_id) VALUES (?, ?, 'od_decision', ?)");
            $notif->execute([$req['student_id'], "Your OD Request was {$status} by HOD.", $id]);
        }
        echo json_encode(["message" => "OD Request {$status}"]);
        exit();
    }
}

http_response_code(404);
echo json_encode(["error" => "Endpoint not found"]);
?>

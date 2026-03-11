<?php
function generateTimetable($pdo) {
    // 1. Fetch all required entities
    $classesStmt = $pdo->query("SELECT * FROM classes");
    $classes = $classesStmt->fetchAll();

    $subjectsStmt = $pdo->query("SELECT * FROM subjects");
    $allSubjects = $subjectsStmt->fetchAll();

    $teacherSubjectsStmt = $pdo->query("SELECT teacher_id, subject_id FROM teacher_subjects");
    $tsMapping = $teacherSubjectsStmt->fetchAll();

    $roomsStmt = $pdo->query("SELECT * FROM classrooms");
    $classrooms = $roomsStmt->fetchAll();

    if (empty($classes) || empty($allSubjects) || empty($tsMapping) || empty($classrooms)) {
        throw new Exception("Please add/seed classes, subjects, teacher-subject mappings, and classrooms first.");
    }

    // Days and time slots
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $timeSlots = [
        ['start' => '09:00:00', 'end' => '09:40:00'],
        ['start' => '09:40:00', 'end' => '10:20:00'],
        // Break 10:20 - 10:30
        ['start' => '10:30:00', 'end' => '11:10:00'],
        ['start' => '11:10:00', 'end' => '11:50:00'],
        // Break 11:50 - 12:00
        ['start' => '12:00:00', 'end' => '12:40:00'],
        ['start' => '12:40:00', 'end' => '13:20:00'],
        // Break 13:20 - 13:30
        ['start' => '13:30:00', 'end' => '14:10:00'],
        ['start' => '14:10:00', 'end' => '14:50:00'],
    ];

    // Tracker for constraints
    $teacherSchedule = []; // [teacher_id][day][slot]
    $roomSchedule = [];    // [room_id][day][slot]
    $classSchedule = [];   // [class_id][day][slot]

    $pdo->beginTransaction();

    try {
        // Clear old timetable
        $pdo->exec("DELETE FROM timetable");

        // Subject assignment logic
        // Group subjects by year
        $subjectsByYear = [];
        foreach ($allSubjects as $s) {
            $year = $s['course_year'] ?: 1; // Default to 1 if null
            if (!isset($subjectsByYear[$year])) {
                $subjectsByYear[$year] = [];
            }
            // Find teachers for this subject
            $subjectTeachers = [];
            foreach ($tsMapping as $ts) {
                if ($ts['subject_id'] == $s['id']) {
                    $subjectTeachers[] = $ts['teacher_id'];
                }
            }
            if (!empty($subjectTeachers)) {
                $s['teachers'] = $subjectTeachers;
                $subjectsByYear[$year][] = $s;
            }
        }

        $timetableEntries = [];

        foreach ($classes as $c) {
            $classId = $c['id'];
            $courseYear = $c['course_year'];
            
            $classSubjects = $subjectsByYear[$courseYear] ?? [];
            if (empty($classSubjects)) {
                continue; // No subjects for this class's year
            }

            // Track how many times a subject is assigned to this class to balance them
            $subjectAssignedCounts = [];
            foreach ($classSubjects as $cs) {
                $subjectAssignedCounts[$cs['id']] = 0;
            }

            foreach ($days as $dayIndex => $day) {
                // Track daily subject counts
                $subjectAssignedCountsPerDay = [];
                foreach ($classSubjects as $cs) {
                    $subjectAssignedCountsPerDay[$cs['id']] = 0;
                }

                foreach ($timeSlots as $slotIndex => $slot) {
                    
                    // Shuffle subjects to distribute them evenly
                    $availableSubjects = $classSubjects;
                    usort($availableSubjects, function($a, $b) use ($subjectAssignedCounts) {
                        return $subjectAssignedCounts[$a['id']] <=> $subjectAssignedCounts[$b['id']];
                    });

                    $assignedSlot = false;

                    foreach ($availableSubjects as $subj) {
                        // Max 6 lectures per week per subject
                        if ($subjectAssignedCounts[$subj['id']] >= 6) {
                            continue;
                        }

                        // Max 2 lectures per day for the same subject
                        if ($subjectAssignedCountsPerDay[$subj['id']] >= 2) {
                            continue;
                        }

                        // No consecutive class of the same subject (back-to-back)
                        if ($slotIndex > 0 && isset($classSchedule[$classId][$day][$slotIndex - 1])) {
                            if ($classSchedule[$classId][$day][$slotIndex - 1] === $subj['id']) {
                                continue;
                            }
                        }

                        $teacherCandidates = $subj['teachers'];
                        // Ensure it uses specific mapped teachers
                        // shuffle($teacherCandidates); // Keep shuffle if multiple, but ensure strict adherence

                        foreach ($teacherCandidates as $teacherId) {
                            // Check teacher availability
                            if (isset($teacherSchedule[$teacherId][$day][$slotIndex])) {
                                continue;
                            }

                            // Find a free room
                            $selectedRoomId = null;
                            foreach ($classrooms as $room) {
                                if (!isset($roomSchedule[$room['id']][$day][$slotIndex])) {
                                    $selectedRoomId = $room['id'];
                                    break;
                                }
                            }

                            if ($selectedRoomId) {
                                // We found a valid combination
                                $teacherSchedule[$teacherId][$day][$slotIndex] = true;
                                $roomSchedule[$selectedRoomId][$day][$slotIndex] = true;
                                $classSchedule[$classId][$day][$slotIndex] = $subj['id'];

                                $subjectAssignedCounts[$subj['id']]++;
                                $subjectAssignedCountsPerDay[$subj['id']]++;

                                $timetableEntries[] = [
                                    'class_id' => $classId,
                                    'subject_id' => $subj['id'],
                                    'teacher_id' => $teacherId,
                                    'classroom_id' => $selectedRoomId,
                                    'day_of_week' => $day,
                                    'start_time' => $slot['start'],
                                    'end_time' => $slot['end']
                                ];

                                $assignedSlot = true;
                                break; // Break out of teacher loop
                            }
                        }

                        if ($assignedSlot) {
                            break; // Break out of subjects loop, move to next slot
                        }
                    }
                }
            }
        }

        // Insert into database
        $insertQuery = "INSERT INTO timetable (class_id, subject_id, teacher_id, classroom_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($insertQuery);
        
        foreach ($timetableEntries as $entry) {
            $stmt->execute([
                $entry['class_id'],
                $entry['subject_id'],
                $entry['teacher_id'],
                $entry['classroom_id'],
                $entry['day_of_week'],
                $entry['start_time'],
                $entry['end_time']
            ]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>

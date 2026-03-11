<?php
require 'api/config/db.php';
$classes = $pdo->query('SELECT * FROM classes')->fetchAll(PDO::FETCH_ASSOC);
$teachers = $pdo->query('SELECT id, name FROM users WHERE role=\'teacher\'')->fetchAll(PDO::FETCH_ASSOC);
$classrooms = $pdo->query('SELECT * FROM classrooms')->fetchAll(PDO::FETCH_ASSOC);
$subjects = $pdo->query('SELECT * FROM subjects')->fetchAll(PDO::FETCH_ASSOC);

echo "Classes: " . count($classes) . "\n";
print_r($classes);
echo "\nTeachers: " . count($teachers) . "\n";
print_r($teachers);
echo "\nClassrooms: " . count($classrooms) . "\n";
print_r($classrooms);
echo "\nSubjects: " . count($subjects) . "\n";
print_r(array_slice($subjects, 0, 5));

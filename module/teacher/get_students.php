<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification de la session
if (!isset($_SESSION['login_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

$teacher_id = $_SESSION['login_id'];
$course_id = $_GET['course_id'] ?? '';

if (!$course_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID du cours manquant']);
    exit();
}

// Vérifier que le cours appartient bien à l'enseignant
$check_query = "SELECT 1 FROM student_teacher_course 
               WHERE teacher_id = ? AND course_id = ?";
$check_result = db_fetch_one($check_query, [$teacher_id, $course_id], 'ss');

if (!$check_result) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Cours non trouvé ou accès non autorisé']);
    exit();
}

// Récupérer la liste des élèves du cours
$query = "
SELECT 
    s.id,
    s.name
FROM students s
JOIN student_teacher_course stc ON s.id = stc.student_id
WHERE stc.course_id = ?
ORDER BY s.name";

$students = db_fetch_all($query, [$course_id], 's');

header('Content-Type: application/json');
echo json_encode($students);
?> 
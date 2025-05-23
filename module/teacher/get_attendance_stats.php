<?php
require_once('../../db/config.php');
require_once('../../service/db_utils.php');

// Vérification de la session
if (!isset($_SESSION['login_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

// Vérification des paramètres
if (!isset($_POST['course_id']) || !isset($_POST['course_time'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres manquants']);
    exit();
}

$course_id = $_POST['course_id'];
$course_time = $_POST['course_time'];
$teacher_id = $_SESSION['login_id'];

try {
    // Récupérer les statistiques de présence pour le créneau horaire spécifié
    $stats = db_fetch_row(
        "WITH unique_students AS (
            SELECT DISTINCT student_id
            FROM student_teacher_course
            WHERE course_id = ?
        ),
        attendance_stats AS (
            SELECT 
                s.id,
                CASE WHEN a.attendedid IS NOT NULL THEN 1 ELSE 0 END as is_present
            FROM students s
            INNER JOIN unique_students us ON s.id = us.student_id
            LEFT JOIN attendance a ON s.id = a.attendedid 
                AND DATE(a.date) = CURDATE()
                AND TIME(a.date) = ?
        )
        SELECT 
            COUNT(*) as total_students,
            SUM(is_present) as present_count,
            COUNT(*) - SUM(is_present) as absent_count
        FROM attendance_stats",
        [$course_id, $course_time],
        'ss'
    );

    if (!$stats) {
        throw new Exception("Erreur lors de la récupération des statistiques");
    }

    // Retourner les statistiques au format JSON
    header('Content-Type: application/json');
    echo json_encode([
        'total_students' => (int)$stats['total_students'],
        'present_count' => (int)$stats['present_count'],
        'absent_count' => (int)$stats['absent_count']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 
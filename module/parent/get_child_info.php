<?php
include_once('main.php');
include_once('../../service/db_utils.php');

header('Content-Type: application/json');

// Vérification de l'ID de l'enfant
$child_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$child_id) {
    echo json_encode(['error' => 'ID de l\'enfant non spécifié']);
    exit();
}

// Vérification que l'enfant appartient bien au parent connecté
$child = db_fetch_row(
    "SELECT s.*, c.name as class_name, 
            a.username as admin_creator,
            (SELECT COUNT(*) FROM attendance WHERE attendedid = s.id) as total_attendance,
            (SELECT AVG(score) FROM exam_marks WHERE studentid = s.id) as average_score
     FROM students s
     LEFT JOIN class c ON s.classid = c.id
     LEFT JOIN admin a ON s.created_by = a.id
     WHERE s.id = ? AND s.parentid = ?",
    [$child_id, $check],
    'ii'
);

if (!$child) {
    echo json_encode(['error' => 'Enfant non trouvé ou accès non autorisé']);
    exit();
}

// Calcul du taux de présence
$total_school_days = db_fetch_row(
    "SELECT COUNT(DISTINCT date) as total FROM attendance",
    []
);

$attendance_rate = $total_school_days['total'] > 0 
    ? round(($child['total_attendance'] / $total_school_days['total']) * 100, 2)
    : 0;

// Préparation des données à renvoyer
$response = [
    'id' => $child['id'],
    'name' => $child['name'],
    'class' => $child['class_name'] ?? 'Non assignée',
    'birthdate' => date('d/m/Y', strtotime($child['dob'])),
    'average' => $child['average_score'] ? number_format($child['average_score'], 2) . '/20' : 'Non disponible',
    'attendance' => $attendance_rate . '%',
    'phone' => $child['phone'],
    'email' => $child['email'],
    'address' => $child['address'],
    'parent_id' => $child['parentid'],
    'created_by' => $child['admin_creator'] ?? 'Inconnu',
    'admission_date' => date('d/m/Y', strtotime($child['addmissiondate']))
];

echo json_encode($response);
?> 
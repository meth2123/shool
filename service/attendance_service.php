<?php
include_once('db_utils.php');

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Vérification de la session
session_start();
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Récupération des paramètres
$action = $_POST['action'] ?? '';
$student_id = $_POST['student_id'] ?? '';
$period = $_POST['period'] ?? 'thismonth';

// Validation des paramètres
if (empty($action) || empty($student_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit();
}

// Traitement des différentes actions
switch ($action) {
    case 'get_presence':
        $query = "SELECT a.date, c.name as course_name
                 FROM attendance a
                 INNER JOIN course c ON c.studentid = a.attendedid
                 WHERE a.attendedid = ?";
        
        $params = [$student_id];
        $types = 's';
        
        if ($period === 'thismonth') {
            $query .= " AND MONTH(a.date) = MONTH(CURRENT_DATE) AND YEAR(a.date) = YEAR(CURRENT_DATE)";
        }
        
        $query .= " ORDER BY a.date DESC";
        break;

    case 'get_absence':
        // Pour les absences, on cherche les jours où il n'y a pas d'enregistrement dans attendance
        $query = "SELECT DISTINCT c.name as course_name, d.date
                 FROM course c
                 CROSS JOIN (
                     SELECT DISTINCT date 
                     FROM attendance 
                     WHERE MONTH(date) = MONTH(CURRENT_DATE) AND YEAR(date) = YEAR(CURRENT_DATE)
                 ) d
                 LEFT JOIN attendance a ON a.attendedid = c.studentid AND a.date = d.date
                 WHERE c.studentid = ? AND a.id IS NULL";
        
        $params = [$student_id];
        $types = 's';
        
        if ($period === 'thismonth') {
            $query .= " AND MONTH(d.date) = MONTH(CURRENT_DATE) AND YEAR(d.date) = YEAR(CURRENT_DATE)";
        }
        
        $query .= " ORDER BY d.date DESC";
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        exit();
}

// Exécution de la requête
try {
    $results = db_fetch_all($query, $params, $types);
    
    // Formatage des dates
    if ($results) {
        foreach ($results as &$result) {
            $date = new DateTime($result['date']);
            $result['date_formatted'] = $date->format('d/m/Y');
            $result['status'] = $action === 'get_presence' ? 'present' : 'absent';
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $results ?: []
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des données : ' . $e->getMessage()
    ]);
} 
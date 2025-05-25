<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

header('Content-Type: application/json');

if (!isset($_SESSION['login_id'])) {
    echo json_encode(['error' => 'Session expirée, veuillez vous reconnecter']);
    exit();
}

$admin_id = $_SESSION['login_id'];
$teaid = isset($_GET['teaid']) ? $_GET['teaid'] : null;

if (!$teaid) {
    echo json_encode(['error' => 'ID enseignant non spécifié']);
    exit();
}

// Vérifier si l'enseignant appartient à cet administrateur
$check_sql = "SELECT id FROM teachers WHERE id = ? AND created_by = ?";
$check_stmt = $link->prepare($check_sql);
$check_stmt->bind_param("ss", $teaid, $admin_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['error' => 'Enseignant non trouvé ou non autorisé']);
    exit();
}

// Récupérer les présences du mois courant
$present_sql = "SELECT DATE_FORMAT(date, '%d/%m/%Y') as formatted_date 
                FROM attendance 
                WHERE attendedid = ? 
                AND MONTH(date) = MONTH(CURDATE()) 
                AND YEAR(date) = YEAR(CURDATE())
                ORDER BY date DESC";

$present_stmt = $link->prepare($present_sql);
$present_stmt->bind_param("s", $teaid);
$present_stmt->execute();
$present_result = $present_stmt->get_result();

$present_html = '';
if ($present_result->num_rows > 0) {
    while ($row = $present_result->fetch_assoc()) {
        $present_html .= '<div class="alert alert-success mb-2">' . 
                        htmlspecialchars($row['formatted_date']) . 
                        ' - Présent</div>';
    }
} else {
    $present_html = '<div class="alert alert-light text-center">Aucune présence trouvée</div>';
}

// Récupérer les absences du mois courant
$absent_sql = "SELECT DATE_FORMAT(date, '%d/%m/%Y') as formatted_date 
               FROM teacher_absences 
               WHERE teacher_id = ? 
               AND MONTH(date) = MONTH(CURDATE()) 
               AND YEAR(date) = YEAR(CURDATE())
               ORDER BY date DESC";

$absent_stmt = $link->prepare($absent_sql);
$absent_stmt->bind_param("s", $teaid);
$absent_stmt->execute();
$absent_result = $absent_stmt->get_result();

$absent_html = '';
if ($absent_result->num_rows > 0) {
    while ($row = $absent_result->fetch_assoc()) {
        $absent_html .= '<div class="alert alert-danger mb-2">' . 
                       htmlspecialchars($row['formatted_date']) . 
                       ' - Absent</div>';
    }
} else {
    $absent_html = '<div class="alert alert-light text-center">Aucune absence trouvée</div>';
}

echo json_encode([
    'present' => $present_html,
    'absent' => $absent_html
]);

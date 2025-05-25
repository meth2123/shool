<?php
// Fichier pour l'exportation des rapports en PDF et Excel
// Important: Aucune sortie avant les en-têtes HTTP pour l'exportation PDF
// Désactiver le tampon de sortie pour éviter les problèmes avec TCPDF
ob_start();

include_once('main.php');
include_once('../../service/mysqlcon.php');
include_once('../../service/db_utils.php');

// Vérifier que l'utilisateur est bien un administrateur
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../index.php?error=unauthorized");
    exit();
}

$admin_id = $_SESSION['login_id'];

// Récupérer les informations de l'administrateur
$admin_info_sql = "SELECT name, email FROM admin WHERE id = ?";
$admin_stmt = $link->prepare($admin_info_sql);
$admin_stmt->bind_param("s", $admin_id);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$admin_info = $admin_result->fetch_assoc();

// Filtres pour les rapports
$grade_filter = isset($_GET['grade_filter']) ? intval($_GET['grade_filter']) : 12;
$class_filter = isset($_GET['class_filter']) && $_GET['class_filter'] !== '' ? $_GET['class_filter'] : null;
$export_type = isset($_GET['export']) ? $_GET['export'] : '';

// Récupérer les classes disponibles pour le filtre
$classes = db_fetch_all(
    "SELECT * FROM class WHERE created_by = ? ORDER BY name",
    [$admin_id],
    's'
);

// Trouver le nom de la classe sélectionnée
$class_name = "Toutes les classes";
if ($class_filter !== null) {
    foreach ($classes as $class) {
        if ($class['id'] === $class_filter) {
            $class_name = $class['name'];
            break;
        }
    }
}

// Requête pour les enseignants avec des élèves ayant des notes inférieures au seuil
$sql = "SELECT 
    t.id as teacher_id,
    t.name as teacher,
    c.id as course_id,
    c.name as course,
    cl.name as class_name,
    COUNT(DISTINCT stc.student_id) as no_of_std,
    MIN(CASE WHEN stc.grade IS NOT NULL THEN stc.grade ELSE NULL END) as min_grade,
    MAX(CASE WHEN stc.grade IS NOT NULL THEN stc.grade ELSE NULL END) as max_grade,
    AVG(CASE WHEN stc.grade IS NOT NULL THEN stc.grade ELSE NULL END) as avg_grade
FROM teachers t
JOIN student_teacher_course stc ON CAST(t.id AS CHAR) = CAST(stc.teacher_id AS CHAR)
JOIN course c ON CAST(stc.course_id AS CHAR) = CAST(c.id AS CHAR)
JOIN class cl ON CAST(stc.class_id AS CHAR) = CAST(cl.id AS CHAR)
WHERE t.created_by = ?";

// Ajouter le filtre de note si nécessaire (seulement si inférieur à 20)
if ($grade_filter < 20) {
    $sql .= " AND (stc.grade <= ? OR stc.grade IS NULL)";
}

// Ajouter le filtre de classe si spécifié
if ($class_filter !== null) {
    $sql .= " AND CAST(stc.class_id AS CHAR) = ?";
}

$sql .= " GROUP BY c.id, t.id, cl.id ORDER BY no_of_std DESC, avg_grade ASC";

// Préparer les paramètres en fonction des filtres appliqués
$params = [$admin_id];
$types = 's';

if ($grade_filter < 20) {
    $params[] = $grade_filter;
    $types .= 'i';
}

if ($class_filter !== null) {
    $params[] = $class_filter;
    $types .= 's';
}

// Exécuter la requête
$stmt = $link->prepare($sql);

if ($stmt) {
    // Utiliser bind_param avec un tableau de paramètres
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $teachers_data = $result->fetch_all(MYSQLI_ASSOC);
    $num_rows = count($teachers_data);
} else {
    $teachers_data = [];
    $num_rows = 0;
}

// Statistiques pour le tableau de bord
$total_students_in_trouble = 0;
$teachers_concerned = [];
$courses_concerned = [];
$class_distribution = [];

foreach ($teachers_data as $row) {
    $total_students_in_trouble += $row['no_of_std'];
    $teachers_concerned[$row['teacher_id']] = $row['teacher'];
    $courses_concerned[$row['course_id']] = $row['course'];
    
    if (!isset($class_distribution[$row['class_name']])) {
        $class_distribution[$row['class_name']] = 0;
    }
    $class_distribution[$row['class_name']] += $row['no_of_std'];
}

// Récupérer les élèves en difficulté pour chaque classe
$class_students = [];

if ($class_filter !== null) {
    $students_sql = "SELECT 
        s.id, 
        s.name, 
        AVG(stc.grade) as avg_grade,
        COUNT(DISTINCT stc.course_id) as courses_count,
        COUNT(CASE WHEN stc.grade < ? THEN 1 ELSE NULL END) as low_grades_count
    FROM students s
    JOIN student_teacher_course stc ON CAST(s.id AS CHAR) = CAST(stc.student_id AS CHAR)
    WHERE s.classid = ? AND s.created_by = ?
    GROUP BY s.id
    HAVING low_grades_count > 0
    ORDER BY avg_grade ASC";
    
    $students_stmt = $link->prepare($students_sql);
    $students_stmt->bind_param("iss", $grade_filter, $class_filter, $admin_id);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
    $class_students = $students_result->fetch_all(MYSQLI_ASSOC);
}

// Vider le tampon de sortie avant d'envoyer des en-têtes
ob_end_clean();

// Exportation en PDF
if ($export_type === 'pdf') {
    // Inclure la bibliothèque TCPDF
    require_once('../../vendor/autoload.php');
    
    // Créer une nouvelle instance de TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Définir les informations du document
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($admin_info['name']);
    $pdf->SetTitle('Rapport de Performance - ' . date('Y-m-d'));
    $pdf->SetSubject('Élèves en difficulté');
    
    // Définir les marges
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    // Supprimer les en-têtes et pieds de page par défaut
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true);
    
    // Définir l'auto-saut de page
    $pdf->SetAutoPageBreak(true, 15);
    
    // Ajouter une page
    $pdf->AddPage();
    
    // En-tête du rapport
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Rapport de Performance', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Généré le ' . date('d/m/Y à H:i'), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Informations sur les filtres
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Filtres appliqués:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 7, 'Note minimale:', 0, 0);
    $pdf->Cell(0, 7, $grade_filter . '/20', 0, 1);
    $pdf->Cell(40, 7, 'Classe:', 0, 0);
    $pdf->Cell(0, 7, $class_name, 0, 1);
    $pdf->Ln(5);
    
    // Statistiques
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Statistiques:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 7, 'Total des élèves en difficulté:', 0, 0);
    $pdf->Cell(0, 7, $total_students_in_trouble, 0, 1);
    $pdf->Cell(60, 7, 'Enseignants concernés:', 0, 0);
    $pdf->Cell(0, 7, count($teachers_concerned), 0, 1);
    $pdf->Cell(60, 7, 'Cours concernés:', 0, 0);
    $pdf->Cell(0, 7, count($courses_concerned), 0, 1);
    $pdf->Ln(5);
    
    // Tableau des enseignants
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Évaluation des Enseignants:', 0, 1, 'L');
    
    // En-tête du tableau
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(50, 7, 'Enseignant', 1, 0, 'C', true);
    $pdf->Cell(50, 7, 'Cours', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Classe', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Élèves', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Min', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Max', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Moyenne', 1, 1, 'C', true);
    
    // Données du tableau
    $pdf->SetFont('helvetica', '', 9);
    foreach ($teachers_data as $row) {
        $pdf->Cell(50, 7, $row['teacher'], 1, 0, 'L');
        $pdf->Cell(50, 7, $row['course'], 1, 0, 'L');
        $pdf->Cell(30, 7, $row['class_name'], 1, 0, 'L');
        $pdf->Cell(20, 7, $row['no_of_std'], 1, 0, 'C');
        $pdf->Cell(20, 7, $row['min_grade'] !== null ? number_format((float)$row['min_grade'], 2) : 'N/A', 1, 0, 'C');
        $pdf->Cell(20, 7, $row['max_grade'] !== null ? number_format((float)$row['max_grade'], 2) : 'N/A', 1, 0, 'C');
        $pdf->Cell(20, 7, $row['avg_grade'] !== null ? number_format((float)$row['avg_grade'], 2) : 'N/A', 1, 1, 'C');
    }
    
    // Si une classe spécifique est sélectionnée, ajouter la liste des élèves
    if ($class_filter !== null && !empty($class_students)) {
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Élèves en difficulté dans la classe ' . $class_name . ':', 0, 1, 'L');
        
        // En-tête du tableau
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(40, 7, 'ID', 1, 0, 'C', true);
        $pdf->Cell(60, 7, 'Nom', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Moyenne', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Nombre de cours', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Cours en difficulté', 1, 1, 'C', true);
        
        // Données du tableau
        $pdf->SetFont('helvetica', '', 9);
        foreach ($class_students as $student) {
            $pdf->Cell(40, 7, $student['id'], 1, 0, 'L');
            $pdf->Cell(60, 7, $student['name'], 1, 0, 'L');
            $pdf->Cell(30, 7, number_format((float)$student['avg_grade'], 2) . '/20', 1, 0, 'C');
            $pdf->Cell(30, 7, $student['courses_count'], 1, 0, 'C');
            $pdf->Cell(30, 7, $student['low_grades_count'], 1, 1, 'C');
        }
    }
    
    // Pied de page
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, 'Page ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), 0, 0, 'C');
    
    // Générer le PDF
    $pdf->Output('rapport_performance_' . date('Ymd') . '.pdf', 'D');
    exit();
}

// Exportation en Excel
if ($export_type === 'excel') {
    // Définir les en-têtes pour le téléchargement
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="rapport_performance_' . date('Ymd') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Créer le contenu Excel (format HTML compatible)
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rapport de Performance</title>
    <style>
        table { border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Rapport de Performance</h1>
    <p>Généré le ' . date('d/m/Y à H:i') . '</p>
    
    <h2>Filtres appliqués</h2>
    <table>
        <tr>
            <th>Note minimale</th>
            <td>' . $grade_filter . '/20</td>
        </tr>
        <tr>
            <th>Classe</th>
            <td>' . $class_name . '</td>
        </tr>
    </table>
    
    <h2>Statistiques</h2>
    <table>
        <tr>
            <th>Total des élèves en difficulté</th>
            <td>' . $total_students_in_trouble . '</td>
        </tr>
        <tr>
            <th>Enseignants concernés</th>
            <td>' . count($teachers_concerned) . '</td>
        </tr>
        <tr>
            <th>Cours concernés</th>
            <td>' . count($courses_concerned) . '</td>
        </tr>
    </table>
    
    <h2>Évaluation des Enseignants</h2>
    <table>
        <tr>
            <th>Enseignant</th>
            <th>Cours</th>
            <th>Classe</th>
            <th>Nombre d\'Élèves</th>
            <th>Note Min</th>
            <th>Note Max</th>
            <th>Note Moyenne</th>
        </tr>';
    
    foreach ($teachers_data as $row) {
        echo '<tr>
            <td>' . htmlspecialchars($row['teacher']) . '</td>
            <td>' . htmlspecialchars($row['course']) . '</td>
            <td>' . htmlspecialchars($row['class_name']) . '</td>
            <td>' . $row['no_of_std'] . '</td>
            <td>' . ($row['min_grade'] !== null ? number_format((float)$row['min_grade'], 2) : 'N/A') . '</td>
            <td>' . ($row['max_grade'] !== null ? number_format((float)$row['max_grade'], 2) : 'N/A') . '</td>
            <td>' . ($row['avg_grade'] !== null ? number_format((float)$row['avg_grade'], 2) : 'N/A') . '</td>
        </tr>';
    }
    
    echo '</table>';
    
    // Si une classe spécifique est sélectionnée, ajouter la liste des élèves
    if ($class_filter !== null && !empty($class_students)) {
        echo '<h2>Élèves en difficulté dans la classe ' . $class_name . '</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Moyenne</th>
                <th>Nombre de cours</th>
                <th>Cours en difficulté</th>
            </tr>';
        
        foreach ($class_students as $student) {
            echo '<tr>
                <td>' . htmlspecialchars($student['id']) . '</td>
                <td>' . htmlspecialchars($student['name']) . '</td>
                <td>' . number_format((float)$student['avg_grade'], 2) . '/20</td>
                <td>' . $student['courses_count'] . '</td>
                <td>' . $student['low_grades_count'] . '</td>
            </tr>';
        }
        
        echo '</table>';
    }
    
    echo '</body></html>';
    exit();
}

// Si on arrive ici, rediriger vers la page de rapport
header('Location: report.php?grade_filter=' . $grade_filter . '&class_filter=' . ($class_filter !== null ? $class_filter : ''));
exit();
?>

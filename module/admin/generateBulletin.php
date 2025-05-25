<?php
// Démarrer la mise en tampon de sortie pour éviter les erreurs TCPDF
ob_start();

include_once('main.php');
include_once('../../service/db_utils.php');
require_once('../../vendor/autoload.php'); // TCPDF est maintenant installé via Composer

// Vérification des droits d'administrateur
if (!isset($check) || !isset($login_session)) {
    die('Accès non autorisé.');
}

$admin_id = $_SESSION['login_id'];
$student_id = $_GET['student'] ?? '';
$class_id = $_GET['class'] ?? '';
$period = $_GET['period'] ?? '1';

if (empty($student_id) || empty($class_id)) {
    die('Paramètres manquants.');
}

// Récupération des informations de l'élève
$student = db_fetch_row(
    "SELECT s.*, c.name as class_name
     FROM students s
     JOIN class c ON CAST(s.classid AS CHAR) = CAST(c.id AS CHAR)
     WHERE s.id = ? AND s.created_by = ? AND c.id = ?",
    [$student_id, $admin_id, $class_id],
    'sss'
);

if (!$student) {
    die('Élève non trouvé.');
}

// Récupération des informations du parent
$parent = db_fetch_row(
    "SELECT name as parent_name, phone as parent_phone
     FROM parent
     WHERE id = ?",
    [$student['parentid']],
    's'
);

// Récupération des notes de l'élève avec les coefficients des matières
$grades = db_fetch_all(
    "SELECT 
        c.name as course_name,
        c.coefficient as course_coefficient,
        t.name as teacher_name,
        stc.grade_type,
        stc.grade_number,
        stc.grade,
        stc.coefficient as grade_coefficient,
        stc.semester
     FROM student_teacher_course stc
     JOIN course c ON stc.course_id = c.id
     JOIN teachers t ON stc.teacher_id = t.id
     WHERE stc.student_id = ?
     AND stc.class_id = ?
     AND stc.semester = ?
     ORDER BY c.name, stc.grade_type, stc.grade_number",
    [$student_id, $class_id, $period],
    'sss'
);

// Calcul des moyennes par matière
$course_averages = [];
foreach ($grades as $grade) {
    $course = $grade['course_name'];
    if (!isset($course_averages[$course])) {
        $course_averages[$course] = [
            'total' => 0,
            'total_coefficient' => 0,
            'course_coefficient' => $grade['course_coefficient'] ?? 1,
            'grades' => []
        ];
    }
    $course_averages[$course]['grades'][] = $grade;
    
    // Calcul de la moyenne pondérée pour cette note
    $grade_value = $grade['grade'] ?? 0;
    $grade_coefficient = $grade['grade_coefficient'] ?? 1;
    $course_averages[$course]['total'] += $grade_value * $grade_coefficient;
    $course_averages[$course]['total_coefficient'] += $grade_coefficient;
}

// Calcul de la moyenne générale pondérée
$total_weighted_average = 0;
$total_course_coefficients = 0;

foreach ($course_averages as $course => $data) {
    if ($data['total_coefficient'] > 0) {
        // Calcul de la moyenne de la matière
        $course_averages[$course]['average'] = $data['total'] / $data['total_coefficient'];
        
        // Ajout à la moyenne générale pondérée
        $total_weighted_average += $course_averages[$course]['average'] * $data['course_coefficient'];
        $total_course_coefficients += $data['course_coefficient'];
    }
}

$general_average = $total_course_coefficients > 0 ? $total_weighted_average / $total_course_coefficients : 0;

// Création du PDF
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 20);
        $this->Cell(0, 15, 'Bulletin Scolaire', 0, false, 'C', 0, '', 0, false, 'M', 'M');
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Création d'une nouvelle instance de PDF
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configuration du document
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Système de Gestion Scolaire');
$pdf->SetTitle('Bulletin - ' . $student['name']);

// Configuration des marges
$pdf->SetMargins(15, 40, 15);
$pdf->SetHeaderMargin(20);
$pdf->SetFooterMargin(10);

// Ajout d'une page
$pdf->AddPage();

// En-tête du bulletin
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Année scolaire ' . date('Y') . '-' . (date('Y') + 1), 0, 1, 'C');
$pdf->Cell(0, 10, ($period == '1' ? '1er' : ($period == '2' ? '2ème' : '3ème')) . ' Trimestre', 0, 1, 'C');
$pdf->Ln(10);

// Informations de l'élève
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Informations de l\'élève', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 7, 'Nom :', 0, 0);
$pdf->Cell(0, 7, $student['name'], 0, 1);
$pdf->Cell(40, 7, 'Classe :', 0, 0);
$pdf->Cell(0, 7, $student['class_name'], 0, 1);
$pdf->Cell(40, 7, 'Date de naissance :', 0, 0);
$pdf->Cell(0, 7, $student['dob'], 0, 1);
$pdf->Ln(5);

// Informations du parent
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Informations du parent', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
if ($parent) {
    $pdf->Cell(40, 7, 'Nom :', 0, 0);
    $pdf->Cell(0, 7, $parent['parent_name'], 0, 1);
    $pdf->Cell(40, 7, 'Téléphone :', 0, 0);
    $pdf->Cell(0, 7, $parent['parent_phone'], 0, 1);
} else {
    $pdf->Cell(0, 7, 'Les informations du parent ne sont pas disponibles.', 0, 1);
}
$pdf->Ln(10);

// Tableau des notes
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Notes', 0, 1, 'L');

// En-tête du tableau
$pdf->SetFont('helvetica', 'B', 10);
$header = array('Matière', 'Enseignant', 'Devoir 1', 'Devoir 2', 'Examen', 'Moyenne');
$w = array(50, 40, 25, 25, 25, 25);

// Couleurs, ligne et police
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(0);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.1);
$pdf->SetFont('helvetica', 'B', 9);

// En-tête
for($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// Données
$pdf->SetFont('helvetica', '', 9);
$pdf->SetFillColor(255, 255, 255);

foreach ($course_averages as $course => $data) {
    $devoir1 = '';
    $devoir2 = '';
    $examen = '';
    
    foreach ($data['grades'] as $grade) {
        if ($grade['grade_type'] === 'devoir') {
            if ($grade['grade_number'] == 1) {
                $devoir1 = isset($grade['grade']) ? number_format($grade['grade'], 2) . '/20' : '-';
            } else {
                $devoir2 = isset($grade['grade']) ? number_format($grade['grade'], 2) . '/20' : '-';
            }
        } else {
            $examen = isset($grade['grade']) ? number_format($grade['grade'], 2) . '/20' : '-';
        }
    }

    $pdf->Cell($w[0], 6, $course . ' (coef ' . $data['course_coefficient'] . ')', 1, 0, 'L');
    $pdf->Cell($w[1], 6, $data['grades'][0]['teacher_name'], 1, 0, 'L');
    $pdf->Cell($w[2], 6, $devoir1, 1, 0, 'C');
    $pdf->Cell($w[3], 6, $devoir2, 1, 0, 'C');
    $pdf->Cell($w[4], 6, $examen, 1, 0, 'C');
    $pdf->Cell($w[5], 6, isset($data['average']) ? number_format($data['average'], 2) . '/20' : '-', 1, 0, 'C');
    $pdf->Ln();
}

// Moyenne générale
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(array_sum($w) - $w[5], 7, 'Moyenne générale :', 1, 0, 'R');
$pdf->Cell($w[5], 7, isset($general_average) ? number_format($general_average, 2) . '/20' : '-', 1, 1, 'C');

// Nettoyer tout tampon de sortie avant de générer le PDF
ob_end_clean();

// Sortie du PDF
$pdf->Output('bulletin_' . $student_id . '_' . $period . '.pdf', 'D');
// Terminer le script pour éviter toute sortie supplémentaire
exit();
?>
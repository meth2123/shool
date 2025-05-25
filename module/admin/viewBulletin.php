<?php
include_once('main.php');
include_once('includes/auth_check.php');
include_once('../../service/db_utils.php');

// La vérification de la session admin est déjà faite dans auth_check.php

// Connexion à la base de données
require_once('../../db/config.php');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// L'ID de l'administrateur et le login_session sont déjà définis dans auth_check.php
// $loged_user_name est défini dans le template layout.php
$student_id = $_GET['student'] ?? '';
$class_id = $_GET['class'] ?? '';
$period = $_GET['period'] ?? '1';

// Vérifier que l'admin a accès à cette classe
$class = db_fetch_row(
    "SELECT * FROM class WHERE id = ? AND (created_by = ? OR created_by = '21')",
    [$class_id, $admin_id],
    'ss'
);

if (!$class) {
    die("Accès non autorisé à cette classe.");
}

// Récupérer les informations de l'élève
$student = db_fetch_row(
    "SELECT * FROM students WHERE id = ? AND classid = ?",
    [$student_id, $class_id],
    'ss'
);

if (!$student) {
    die("Élève non trouvé dans cette classe.");
}

// Récupérer les notes de l'élève pour la période
$grades = db_fetch_all(
    "SELECT 
        c.name as course_name,
        c.coefficient as course_coefficient,
        stc.grade_type,
        stc.grade_number,
        stc.grade,
        stc.coefficient as grade_coefficient,
        stc.semester,
        t.name as teacher_name
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

// Supprimer la requête des absences et les statistiques
$query = "
SELECT 
    DATE_FORMAT(a.date, '%d/%m/%Y') as date,
    TIME(a.date) as course_time,
    c.name as course_name,
    t.name as teacher_name,
    'present' as status
FROM attendance a
JOIN student_teacher_course stc ON a.attendedid = stc.student_id
JOIN course c ON stc.course_id = c.id
JOIN teachers t ON stc.teacher_id = t.id
WHERE stc.student_id = ?
ORDER BY a.date DESC, TIME(a.date) ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$absences = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Supprimer le calcul des statistiques d'absence
$total_absences = 0;
$justified_absences = 0;
$unjustified_absences = 0;

// Supprimer la requête des cours et la boucle de calcul des absences
$courses_query = "
SELECT DISTINCT c.id, c.name, t.name as teacher_name
FROM student_teacher_course stc
JOIN course c ON stc.course_id = c.id
JOIN teachers t ON stc.teacher_id = t.id
WHERE stc.student_id = ?";

$stmt = $conn->prepare($courses_query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Supprimer la boucle de calcul des absences
foreach ($courses as $course) {
    $absence_query = "
    SELECT DATE(a.date) as date
    FROM attendance a
    WHERE a.attendedid = ?
    AND DATE(a.date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    AND DATE(a.date) <= CURDATE()";
    
    $stmt = $conn->prepare($absence_query);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $present_dates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Convertir les dates en tableau pour faciliter la recherche
    $present_dates_array = array_column($present_dates, 'date');
    
    // Vérifier chaque jour des 30 derniers jours
    for ($i = 0; $i < 30; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        if (!in_array($date, $present_dates_array)) {
            $total_absences++;
            $unjustified_absences++; // Pour l'instant, toutes les absences sont non justifiées
        }
    }
}

// Calculer les moyennes par matière
$course_averages = [];
foreach ($grades as $grade) {
    $course_name = $grade['course_name'];
    if (!isset($course_averages[$course_name])) {
        $course_averages[$course_name] = [
            'total_points' => 0,
            'total_coefficients' => 0,
            'course_coefficient' => $grade['course_coefficient'] ?? 1, // Coefficient de la matière
            'grades' => [],
            'grade_count' => 0 // Ajouter un compteur pour le nombre d'évaluations
        ];
    }
    $course_averages[$course_name]['grades'][] = $grade;
    $course_averages[$course_name]['grade_count']++; // Incrémenter le compteur
    // Utiliser uniquement le coefficient de la matière pour chaque note
    $weighted_grade = $grade['grade'] * ($grade['course_coefficient'] ?? 1);
    $course_averages[$course_name]['total_points'] += $weighted_grade;
    $course_averages[$course_name]['total_coefficients'] += ($grade['course_coefficient'] ?? 1);
}

// Calculer la moyenne générale
$total_points = 0;
$total_course_coefficients = 0;
foreach ($course_averages as $course) {
    if ($course['total_coefficients'] > 0) {
        $course_average = $course['total_points'] / $course['total_coefficients'];
        $total_points += $course_average * $course['course_coefficient'];
        $total_course_coefficients += $course['course_coefficient'];
    }
}

$general_average = $total_course_coefficients > 0 ? $total_points / $total_course_coefficients : 0;

// Récupérer les moyennes de tous les élèves de la classe pour calculer le rang
$class_averages = db_fetch_all(
    "WITH student_grades AS (
        SELECT 
            s.id as student_id,
            s.name as student_name,
            stc.grade,
            stc.coefficient as grade_coefficient,
            c.coefficient as course_coefficient,
            c.name as course_name
        FROM students s
        JOIN student_teacher_course stc ON CAST(stc.student_id AS CHAR) = CAST(s.id AS CHAR)
        JOIN course c ON stc.course_id = c.id
        WHERE s.classid = ?
        AND stc.class_id = ?
        AND stc.semester = ?
    ),
    course_averages AS (
        SELECT 
            student_id,
            student_name,
            course_name,
            course_coefficient,
            ROUND(
                SUM(grade * grade_coefficient) / NULLIF(SUM(grade_coefficient), 0),
                2
            ) as course_average
        FROM student_grades
        GROUP BY student_id, student_name, course_name, course_coefficient
    )
    SELECT 
        student_id,
        student_name,
        ROUND(
            SUM(course_average * course_coefficient) / NULLIF(SUM(course_coefficient), 0),
            2
        ) as general_average
    FROM course_averages
    GROUP BY student_id, student_name
    ORDER BY general_average DESC",
    [$class_id, $class_id, $period],
    'sss'
);

// Calculer le rang de l'élève
$student_rank = 0;
$total_students = count($class_averages);
foreach ($class_averages as $index => $student) {
    if ($student['student_id'] === $student_id) {
        $student_rank = $index + 1;
        break;
    }
}

// Déterminer la mention
$mention = '';
if ($general_average >= 16) {
    $mention = 'Très Bien';
} elseif ($general_average >= 14) {
    $mention = 'Bien';
} elseif ($general_average >= 12) {
    $mention = 'Assez Bien';
} elseif ($general_average >= 10) {
    $mention = 'Passable';
} else {
    $mention = 'Insuffisant';
}

// Fonction utilitaire pour gérer les valeurs nulles avec htmlspecialchars
function safe_html($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Déterminer la couleur de la mention pour Bootstrap
$mention_color = '';
if ($general_average >= 16) {
    $mention_color = 'text-success';
} elseif ($general_average >= 14) {
    $mention_color = 'text-primary';
} elseif ($general_average >= 12) {
    $mention_color = 'text-info';
} elseif ($general_average >= 10) {
    $mention_color = 'text-secondary';
} else {
    $mention_color = 'text-danger';
}

$content = '
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="text-center mb-4">
                <h1 class="h3 mb-2">Bulletin de Notes</h1>
                <p class="text-muted">Semestre ' . safe_html($period) . '</p>
            </div>

            <!-- Informations de l\'élève -->
            <div class="card mb-4 bg-light">
                <div class="card-body">
                    <h2 class="h5 mb-3">Informations de l\'élève</h2>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="small text-muted mb-1">Nom</p>
                            <p class="fw-medium">' . safe_html($student['name'] ?? $student_id) . '</p>
                        </div>
                        <div class="col-md-6">
                            <p class="small text-muted mb-1">Classe</p>
                            <p class="fw-medium">' . safe_html($class['name'] ?? $class_id) . '</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes par matière -->
            <div class="mb-4">
                <h2 class="h5 mb-3">Notes par matière</h2>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Matière</th>
                                <th>Type</th>
                                <th>Note</th>
                                <th>Coefficient</th>
                                <th>Professeur</th>
                            </tr>
                        </thead>
                        <tbody>';

foreach ($course_averages as $course_name => $course) {
    $rowspan = count($course['grades']);
    $first = true;
    
    foreach ($course['grades'] as $grade) {
        $content .= '
            <tr>
                ' . ($first ? '<td rowspan="' . $rowspan . '" class="align-middle fw-medium">' . 
                    safe_html($course_name) . ' <span class="badge bg-secondary">coef ' . safe_html($course['course_coefficient']) . '</span></td>' : '') . '
                <td>' . 
                    ($grade['grade_type'] === 'devoir' ? 'Devoir ' : 'Examen ') . 
                    safe_html($grade['grade_number']) . '</td>
                <td>' . 
                    safe_html($grade['grade']) . '/20</td>
                <td>' . 
                    safe_html($course['course_coefficient']) . '</td>
                <td>' . 
                    safe_html($grade['teacher_name']) . '</td>
            </tr>';
        $first = false;
    }
    
    // Afficher la moyenne de la matière
    $course_average = $course['total_coefficients'] > 0 ? 
        round($course['total_points'] / $course['total_coefficients'], 2) : 0;
    
    // Calculer le coefficient total (coefficient de la matière × nombre d'évaluations)
    $total_coefficient = $course['course_coefficient'] * $course['grade_count'];
    
    // Déterminer la classe de couleur pour la moyenne
    $avg_color_class = '';
    if ($course_average >= 16) {
        $avg_color_class = 'text-success fw-bold';
    } elseif ($course_average >= 14) {
        $avg_color_class = 'text-primary fw-bold';
    } elseif ($course_average >= 12) {
        $avg_color_class = 'text-info fw-bold';
    } elseif ($course_average >= 10) {
        $avg_color_class = 'text-secondary fw-bold';
    } else {
        $avg_color_class = 'text-danger fw-bold';
    }
    
    $content .= '
        <tr class="table-light">
            <td colspan="2" class="fw-medium">
                Moyenne ' . safe_html($course_name) . '
            </td>
            <td class="' . $avg_color_class . '">' . 
                number_format($course_average, 2) . '/20</td>
            <td colspan="2" class="small">
                Coef. matière: ' . safe_html($course['course_coefficient']) . ' | 
                Coef. total: ' . number_format($total_coefficient, 2) . ' (' . 
                safe_html($course['grade_count']) . ' éval. × ' . 
                safe_html($course['course_coefficient']) . ')
            </td>
        </tr>';
}

$content .= '
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Résultats généraux -->
        <div class="card bg-light mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Résultats généraux</h2>
                <div class="row text-center">
                    <div class="col-md-4">
                        <p class="small text-muted mb-1">Moyenne générale</p>
                        <p class="display-6 fw-bold ' . ($general_average >= 10 ? 'text-success' : 'text-danger') . '">' . 
                            number_format($general_average, 2) . '/20</p>
                    </div>
                    <div class="col-md-4">
                        <p class="small text-muted mb-1">Rang</p>
                        <p class="display-6 fw-bold">' . $student_rank . '<span class="fs-6">/' . $total_students . '</span></p>
                    </div>
                    <div class="col-md-4">
                        <p class="small text-muted mb-1">Mention</p>
                        <p class="display-6 fw-bold ' . $mention_color . '">' . safe_html($mention) . '</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="d-flex justify-content-between">
            <a href="manageBulletins.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Retour
            </a>
            <div>
                <a href="generateBulletin.php?student=' . htmlspecialchars($student_id) . 
                   '&class=' . htmlspecialchars($class_id) . 
                   '&period=' . htmlspecialchars($period) . '"
                   class="btn btn-outline-success me-2">
                   <i class="fas fa-file-pdf me-2"></i>Générer PDF
                </a>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print me-2"></i>Imprimer
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .container, .container * {
        visibility: visible;
    }
    .container {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .btn, .d-flex.justify-content-between {
        display: none !important;
    }
}
</style>';

include('templates/layout.php');
?>

<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification de la session admin
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Connexion à la base de données
require_once('../../db/config.php');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$admin_id = $_SESSION['login_id'];
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

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Bulletin de Notes</h1>
            <p class="text-gray-600">Semestre ' . safe_html($period) . '</p>
        </div>

        <!-- Informations de l\'élève -->
        <div class="mb-8 p-4 bg-gray-50 rounded-lg">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Informations de l\'élève</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Nom</p>
                    <p class="font-medium">' . safe_html($student['student_name']) . '</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Classe</p>
                    <p class="font-medium">' . safe_html($class['name']) . '</p>
                </div>
            </div>
        </div>

        <!-- Notes par matière -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Notes par matière</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Matière</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Note</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Coefficient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Professeur</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">';

foreach ($course_averages as $course_name => $course) {
    $rowspan = count($course['grades']);
    $first = true;
    
    foreach ($course['grades'] as $grade) {
        $content .= '
            <tr>
                ' . ($first ? '<td rowspan="' . $rowspan . '" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">' . 
                    safe_html($course_name) . ' (coef ' . safe_html($course['course_coefficient']) . ')</td>' : '') . '
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                    ($grade['grade_type'] === 'devoir' ? 'Devoir ' : 'Examen ') . 
                    safe_html($grade['grade_number']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                    safe_html($grade['grade']) . '/20</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                    safe_html($course['course_coefficient']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                    safe_html($grade['teacher_name']) . '</td>
            </tr>';
        $first = false;
    }
    
    // Afficher la moyenne de la matière
    $course_average = $course['total_coefficients'] > 0 ? 
        round($course['total_points'] / $course['total_coefficients'], 2) : 0;
    
    // Calculer le coefficient total (coefficient de la matière × nombre d'évaluations)
    $total_coefficient = $course['course_coefficient'] * $course['grade_count'];
    
    $content .= '
        <tr class="bg-gray-50">
            <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                Moyenne ' . safe_html($course_name) . '
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">' . 
                number_format($course_average, 2) . '/20</td>
            <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
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
        <div class="mt-8 p-4 bg-gray-50 rounded-lg">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Résultats généraux</h2>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Moyenne générale</p>
                    <p class="text-2xl font-bold text-gray-900">' . number_format($general_average, 2) . '/20</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Rang</p>
                    <p class="text-2xl font-bold text-gray-900">' . $student_rank . '/' . $total_students . '</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Mention</p>
                    <p class="text-2xl font-bold text-gray-900">' . safe_html($mention) . '</p>
                </div>
            </div>
        </div>

        <!-- Bouton d\'impression -->
        <div class="mt-8 text-center">
            <button onclick="window.print()" 
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Imprimer le bulletin
            </button>
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
    }
    button {
        display: none;
    }
}
</style>';

include('templates/layout.php');
?> 
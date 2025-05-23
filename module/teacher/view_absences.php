<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification de la session
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

$teacher_id = $_SESSION['login_id'];
$student_id = $_GET['student'] ?? '';
$class_id = $_GET['class'] ?? '';

// Vérifier que l'enseignant a accès à cet élève
$student = db_fetch_row(
    "SELECT s.*, c.name as class_name 
     FROM students s
     JOIN class c ON s.classid = c.id
     JOIN student_teacher_course stc ON s.id = stc.student_id
     WHERE s.id = ? AND stc.teacher_id = ?",
    [$student_id, $teacher_id],
    'ss'
);

if (!$student) {
    die("Accès non autorisé à cet élève.");
}

// Récupérer les cours de l'enseignant pour cet élève
$courses = db_fetch_all(
    "SELECT DISTINCT c.id, c.name, t.name as teacher_name
     FROM course c
     JOIN student_teacher_course stc ON c.id = stc.course_id
     JOIN teachers t ON stc.teacher_id = t.id
     WHERE stc.student_id = ? AND stc.teacher_id = ?",
    [$student_id, $teacher_id],
    'ss'
);

// Récupérer les présences/absences pour chaque cours
$absences = [];
foreach ($courses as $course) {
    $query = "
    WITH RECURSIVE dates AS (
        SELECT CURDATE() - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY as date
        FROM (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as a
        CROSS JOIN (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as b
        CROSS JOIN (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as c
        WHERE CURDATE() - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND CURDATE() - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY <= CURDATE()
        AND WEEKDAY(CURDATE() - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY) < 5
    )
    SELECT 
        DATE_FORMAT(d.date, '%d/%m/%Y') as date,
        TIME(a.date) as course_time,
        c.name as course_name,
        t.name as teacher_name,
        CASE 
            WHEN a.attendedid IS NOT NULL THEN 'present'
            ELSE 'absent'
        END as status
    FROM dates d
    CROSS JOIN (SELECT ? as course_id, ? as course_name, ? as teacher_name) as c
    LEFT JOIN attendance a ON a.attendedid = ? 
        AND DATE(a.date) = d.date
        AND EXISTS (
            SELECT 1 FROM student_teacher_course stc 
            WHERE stc.student_id = a.attendedid 
            AND stc.course_id = c.course_id
            AND stc.teacher_id = ?
        )
    ORDER BY d.date DESC, TIME(a.date) ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $course['id'], $course['name'], $course['teacher_name'], $student_id, $teacher_id);
    $stmt->execute();
    $course_absences = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $absences = array_merge($absences, $course_absences);
}

// Calculer les statistiques
$total_absences = 0;
$justified_absences = 0;
$unjustified_absences = 0;

foreach ($absences as $absence) {
    if ($absence['status'] === 'absent') {
        $total_absences++;
        // Pour l'instant, toutes les absences sont non justifiées
        $unjustified_absences++;
    }
}

// Fonction utilitaire pour gérer les valeurs nulles avec htmlspecialchars
function safe_html($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Registre des Absences</h1>
            <p class="text-gray-600">Élève : ' . safe_html($student['name']) . ' - Classe : ' . safe_html($student['class_name']) . '</p>
        </div>

        <!-- Statistiques des absences -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600">Total des absences</p>
                <p class="text-2xl font-bold text-gray-900">' . $total_absences . '</p>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600">Absences justifiées</p>
                <p class="text-2xl font-bold text-green-600">' . $justified_absences . '</p>
            </div>
            <div class="bg-red-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600">Absences non justifiées</p>
                <p class="text-2xl font-bold text-red-600">' . $unjustified_absences . '</p>
            </div>
        </div>

        <!-- Liste des absences -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Heure</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Matière</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Professeur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';

if (!empty($absences)) {
    foreach ($absences as $absence) {
        $status_class = $absence['status'] === 'present' ? 'text-green-600' : 'text-red-600';
        $status_text = $absence['status'] === 'present' ? 'Présent' : 'Absent';
        
        $content .= '
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                    safe_html($absence['date']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                    safe_html($absence['course_time'] ?? '') . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                    safe_html($absence['course_name']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                    safe_html($absence['teacher_name']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm ' . $status_class . '">' . 
                    safe_html($status_text) . '</td>
            </tr>';
    }
} else {
    $content .= '
        <tr>
            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                Aucune absence enregistrée pour les 30 derniers jours
            </td>
        </tr>';
}

$content .= '
                </tbody>
            </table>
        </div>

        <!-- Bouton d\'impression -->
        <div class="mt-8 text-center">
            <button onclick="window.print()" 
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Imprimer le registre
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
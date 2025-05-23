<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification de la session (utilise la même méthode que main.php)
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$teacher_id = $_SESSION['login_id'];
$course_id = $_GET['course_id'] ?? '';

// Debug: Afficher les informations de débogage
echo '<div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
    <p>ID du professeur : ' . htmlspecialchars($teacher_id) . '</p>
    <p>ID du cours demandé : ' . htmlspecialchars($course_id) . '</p>
</div>';

// Vérifier que le professeur a accès à ce cours
$course = db_fetch_row(
    "SELECT c.*, cl.name as class_name, t.name as teacher_name, c.coefficient
     FROM course c 
     JOIN class cl ON c.classid = cl.id 
     JOIN teachers t ON c.teacherid = t.id
     WHERE c.id = ? AND c.teacherid = ?",
    [$course_id, $teacher_id],
    'ss'
);

// Debug: Afficher les détails du cours
if ($course) {
    echo '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
        <p>Cours trouvé :</p>
        <pre>' . print_r($course, true) . '</pre>
    </div>';
} else {
    echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
        <p>Cours non trouvé dans la base de données.</p>
    </div>';
}

if (!$course) {
    die("Accès non autorisé à ce cours.");
}

// Récupérer l'admin associé au professeur
$admin_info = db_fetch_row(
    "SELECT created_by FROM teachers WHERE id = ?",
    [$teacher_id],
    's'
);

if (!$admin_info) {
    die("Erreur : Impossible de trouver l'administrateur associé au professeur.");
}

$admin_id = $admin_info['created_by'];

// Traitement de la soumission des notes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_grades'])) {
    try {
        $link->begin_transaction();

        $grade_type = $_POST['grade_type'];
        $grade_number = $_POST['grade_number'];
        $semester = $_POST['semester'];
        $class_id = $course['classid'];

        foreach ($_POST['grades'] as $student_id => $grades) {
            if (!empty($grades['grade'])) {
                // Vérifier si une note existe déjà
                $existing_grade = db_fetch_row(
                    "SELECT id FROM student_teacher_course 
                     WHERE student_id = ? 
                     AND course_id = ? 
                     AND grade_type = ? 
                     AND grade_number = ? 
                     AND semester = ?",
                    [$student_id, $course_id, $grade_type, $grade_number, $semester],
                    'sssss'
                );

                if ($existing_grade) {
                    // Mettre à jour la note existante
                    $update_sql = "UPDATE student_teacher_course 
                                 SET grade = ?, 
                                     updated_at = CURRENT_TIMESTAMP 
                                 WHERE id = ?";
                    db_query($update_sql, [$grades['grade'], $existing_grade['id']], 'ds');
                } else {
                    // Insérer une nouvelle note avec l'admin associé au professeur
                    $insert_sql = "INSERT INTO student_teacher_course 
                                 (student_id, teacher_id, course_id, class_id, 
                                  grade_type, grade_number, grade, semester,
                                  created_by, created_at, updated_at) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 
                                         CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
                    db_query($insert_sql, [
                        $student_id,
                        $teacher_id,
                        $course_id,
                        $class_id,
                        $grade_type,
                        $grade_number,
                        $grades['grade'],
                        $semester,
                        $admin_id
                    ], 'ssssssdss');
                }
            }
        }

        $link->commit();
        $success_message = "Les notes ont été enregistrées avec succès.";
    } catch (Exception $e) {
        $link->rollback();
        $error_message = "Erreur lors de l'enregistrement des notes : " . $e->getMessage();
        
        // Debug: Afficher plus de détails sur l'erreur
        error_log("Erreur détaillée : " . $e->getMessage());
        error_log("SQL State : " . $e->getCode());
        error_log("Trace : " . $e->getTraceAsString());
    }
}

// Récupérer les élèves du cours
$students = db_fetch_all(
    "SELECT s.*, 
            (SELECT grade FROM student_teacher_course 
             WHERE student_id = s.id 
             AND course_id = ? 
             AND grade_type = ? 
             AND grade_number = ? 
             AND semester = ?
             LIMIT 1) as current_grade
     FROM students s 
     WHERE s.classid = ? 
     ORDER BY s.name",
    [$course_id, $_GET['grade_type'] ?? 'devoir', $_GET['grade_number'] ?? 1, $_GET['semester'] ?? 1, $course['classid']],
    'sssss'
);

// Fonction utilitaire pour sécuriser l'affichage des valeurs
function safe_html($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestion des Notes</h1>
        <p class="text-gray-600">Cours : ' . safe_html($course['name']) . ' - Classe : ' . safe_html($course['class_name']) . '</p>
        <p class="text-sm text-gray-500">Coefficient : ' . htmlspecialchars($course['coefficient']) . ' (défini par l\'administrateur)</p>
    </div>';

// Messages de succès/erreur
if (isset($success_message)) {
    $content .= '
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
        ' . safe_html($success_message) . '
    </div>';
}
if (isset($error_message)) {
    $content .= '
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
        ' . safe_html($error_message) . '
    </div>';
}

// Formulaire de saisie des notes
$content .= '
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <form method="GET" class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="course_id" value="' . safe_html($course_id) . '">
            <div>
                <label class="block text-sm font-medium text-gray-700">Type d\'évaluation</label>
                <select name="grade_type" onchange="this.form.submit()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="devoir" ' . (($_GET['grade_type'] ?? '') === 'devoir' ? 'selected' : '') . '>Devoir</option>
                    <option value="examen" ' . (($_GET['grade_type'] ?? '') === 'examen' ? 'selected' : '') . '>Examen</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Numéro</label>
                <select name="grade_number" onchange="this.form.submit()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="1" ' . (($_GET['grade_number'] ?? '') === '1' ? 'selected' : '') . '>1</option>
                    <option value="2" ' . (($_GET['grade_number'] ?? '') === '2' ? 'selected' : '') . '>2</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Semestre</label>
                <select name="semester" onchange="this.form.submit()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="1" ' . (($_GET['semester'] ?? '') === '1' ? 'selected' : '') . '>Semestre 1</option>
                    <option value="2" ' . (($_GET['semester'] ?? '') === '2' ? 'selected' : '') . '>Semestre 2</option>
                    <option value="3" ' . (($_GET['semester'] ?? '') === '3' ? 'selected' : '') . '>Semestre 3</option>
                </select>
            </div>
        </form>

        <form method="POST" class="space-y-6">
            <input type="hidden" name="grade_type" value="' . safe_html($_GET['grade_type'] ?? 'devoir') . '">
            <input type="hidden" name="grade_number" value="' . safe_html($_GET['grade_number'] ?? '1') . '">
            <input type="hidden" name="semester" value="' . safe_html($_GET['semester'] ?? '1') . '">

            <div class="mt-8">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Note</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appréciation</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">';

foreach ($students as $student) {
    $content .= '
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . safe_html($student['id']) . '</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . safe_html($student['name']) . '</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <input type="number" name="grades[' . safe_html($student['id']) . '][grade]" 
                       value="' . safe_html($student['current_grade']) . '"
                       min="0" max="20" step="0.5"
                       class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-20 sm:text-sm border-gray-300 rounded-md">
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <textarea name="grades[' . safe_html($student['id']) . '][comment]" 
                          rows="2"
                          class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                          placeholder="Appréciation..."></textarea>
            </td>
        </tr>';
}

$content .= '
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end mt-6">
                <button type="submit" name="submit_grades" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Enregistrer les notes
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Historique des notes</h2>
        
        <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Type d\'évaluation</label>
                <select id="filter_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Tous</option>
                    <option value="devoir">Devoir</option>
                    <option value="examen">Examen</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Numéro</label>
                <select id="filter_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Tous</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Semestre</label>
                <select id="filter_semester" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Tous</option>
                    <option value="1">Semestre 1</option>
                    <option value="2">Semestre 2</option>
                    <option value="3">Semestre 3</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Date</label>
                <input type="date" id="filter_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200" id="grades-table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Élève</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Numéro</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Note</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semestre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Professeur</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';

// Récupérer l'historique des notes avec plus de détails
$grade_history = db_fetch_all(
    "SELECT DISTINCT 
            stc.grade_type,
            stc.grade_number,
            stc.semester,
            stc.grade,
            s.name as student_name,
            c.name as course_name,
            cl.name as class_name,
            t.name as teacher_name,
            DATE_FORMAT(MAX(stc.created_at), '%d/%m/%Y %H:%i') as formatted_date
     FROM student_teacher_course stc 
     JOIN students s ON stc.student_id = s.id 
     JOIN course c ON stc.course_id = c.id
     JOIN class cl ON stc.class_id = cl.id
     JOIN teachers t ON stc.teacher_id = t.id
     WHERE stc.course_id = ? 
     AND stc.teacher_id = ?
     AND stc.class_id = ?
     AND stc.grade IS NOT NULL
     GROUP BY stc.grade_type, stc.grade_number, stc.semester, stc.grade, s.name, c.name, cl.name, t.name
     ORDER BY stc.semester ASC, stc.grade_type ASC, stc.grade_number ASC",
    [$course_id, $teacher_id, $course['classid']],
    'sss'
);

foreach ($grade_history as $grade) {
    $content .= '
        <tr class="grade-row" 
            data-type="' . safe_html($grade['grade_type']) . '"
            data-number="' . safe_html($grade['grade_number']) . '"
            data-semester="' . safe_html($grade['semester']) . '"
            data-date="' . date('Y-m-d', strtotime($grade['formatted_date'] ?? 'now')) . '">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . safe_html($grade['student_name']) . '</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                ($grade['grade_type'] === 'devoir' ? 'Devoir' : 'Examen') . '</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . safe_html($grade['grade_number']) . '</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . safe_html($grade['grade']) . '/20</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Semestre ' . safe_html($grade['semester']) . '</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . safe_html($grade['formatted_date']) . '</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . safe_html($grade['teacher_name']) . '</td>
        </tr>';
}

$content .= '
                </tbody>
            </table>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const filterType = document.getElementById("filter_type");
        const filterNumber = document.getElementById("filter_number");
        const filterSemester = document.getElementById("filter_semester");
        const filterDate = document.getElementById("filter_date");
        const rows = document.querySelectorAll(".grade-row");

        function applyFilters() {
            const typeValue = filterType.value;
            const numberValue = filterNumber.value;
            const semesterValue = filterSemester.value;
            const dateValue = filterDate.value;

            rows.forEach(row => {
                const type = row.dataset.type;
                const number = row.dataset.number;
                const semester = row.dataset.semester;
                const date = row.dataset.date;

                const typeMatch = !typeValue || type === typeValue;
                const numberMatch = !numberValue || number === numberValue;
                const semesterMatch = !semesterValue || semester === semesterValue;
                const dateMatch = !dateValue || date === dateValue;

                row.style.display = typeMatch && numberMatch && semesterMatch && dateMatch ? "" : "none";
            });
        }

        filterType.addEventListener("change", applyFilters);
        filterNumber.addEventListener("change", applyFilters);
        filterSemester.addEventListener("change", applyFilters);
        filterDate.addEventListener("change", applyFilters);
    });
    </script>
</div>';

// Utiliser le template enseignant
include('templates/layout.php');
?>

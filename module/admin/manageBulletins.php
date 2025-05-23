<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification des droits d'administrateur
if (!isset($check) || !isset($login_session)) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Erreur!</strong>
            <span class="block sm:inline">Accès non autorisé.</span>
          </div>';
    exit();
}

$admin_id = $_SESSION['login_id'];

// Récupération des classes créées par cet admin
$classes = db_fetch_all(
    "SELECT id, name FROM class WHERE created_by = ? ORDER BY name",
    [$admin_id],
    's'
);

// Filtres
$selected_class = $_GET['class'] ?? '';
$selected_period = $_GET['period'] ?? '';
$current_year = date('Y');
$school_year = (date('n') >= 9) ? $current_year . '-' . ($current_year + 1) : ($current_year - 1) . '-' . $current_year;

// Construction de la requête de base pour les bulletins
$query = "WITH student_grades AS (
    SELECT 
        s.id as student_id,
        s.name as student_name,
        c.id as class_id,
        c.name as class_name,
        co.name as course_name,
        co.coefficient as course_coefficient,
        stc.grade_type,
        stc.grade_number,
        stc.grade,
        stc.coefficient as grade_coefficient,
        stc.semester,
        t.name as teacher_name
    FROM class c
    JOIN students s ON CAST(s.classid AS CHAR) = CAST(c.id AS CHAR)
    JOIN student_teacher_course stc ON CAST(stc.student_id AS CHAR) = CAST(s.id AS CHAR)
        AND CAST(stc.class_id AS CHAR) = CAST(c.id AS CHAR)
    JOIN course co ON stc.course_id = co.id
    JOIN teachers t ON stc.teacher_id = t.id
    WHERE c.created_by = ?
    AND stc.semester = ?
    " . ($selected_class ? "AND c.id = ?" : "") . "
),
grade_calculations AS (
    SELECT 
        student_id,
        student_name,
        class_id,
        class_name,
        course_name,
        course_coefficient,
        teacher_name,
        -- Calcul des moyennes par matière
        ROUND(
            SUM(grade * grade_coefficient) / NULLIF(SUM(grade_coefficient), 0),
            2
        ) as course_average,
        -- Nombre de notes par matière
        COUNT(*) as grade_count,
        -- Liste des notes
        GROUP_CONCAT(
            CONCAT(
                grade_type, ' ', grade_number, ': ',
                grade, ' (coef ', grade_coefficient, ')'
            ) ORDER BY grade_type, grade_number
            SEPARATOR ' | '
        ) as grade_details
    FROM student_grades
    GROUP BY student_id, student_name, class_id, class_name, course_name, course_coefficient, teacher_name
)
SELECT 
    student_id,
    student_name,
    class_id,
    class_name,
    -- Moyenne générale pondérée
    ROUND(
        SUM(course_average * course_coefficient) / NULLIF(SUM(course_coefficient), 0),
        2
    ) as general_average,
    -- Nombre total de matières
    COUNT(DISTINCT course_name) as total_courses,
    -- Nombre total de notes
    SUM(grade_count) as total_grades,
    -- Détails des notes par matière
    GROUP_CONCAT(
        CONCAT(
            course_name, ' (', course_coefficient, '): ',
            course_average, '/20 - ', teacher_name,
            ' [', grade_details, ']'
        ) ORDER BY course_name
        SEPARATOR '\n'
    ) as course_details
FROM grade_calculations
GROUP BY student_id, student_name, class_id, class_name
ORDER BY class_name, student_name";

$params = [$admin_id, $selected_period ?: '1'];
$types = 'ss';

if ($selected_class) {
    $params[] = $selected_class;
    $types .= 's';
}

// Récupération des bulletins avec les calculs détaillés
$bulletins = db_fetch_all($query, $params, $types);

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Gestion des Bulletins</h1>
        <div class="text-gray-600">
            Année scolaire : ' . htmlspecialchars($school_year) . '
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Filtres</h2>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Classe</label>
                <select name="class" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Toutes les classes</option>';
                    foreach ($classes as $class) {
                        $content .= '<option value="' . htmlspecialchars($class['id']) . '" ' . 
                                  ($selected_class === $class['id'] ? 'selected' : '') . '>' .
                                  htmlspecialchars($class['name']) . '</option>';
                    }
$content .= '
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Période</label>
                <select name="period" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Toutes les périodes</option>
                    <option value="1" ' . ($selected_period === '1' ? 'selected' : '') . '>1er Trimestre</option>
                    <option value="2" ' . ($selected_period === '2' ? 'selected' : '') . '>2ème Trimestre</option>
                    <option value="3" ' . ($selected_period === '3' ? 'selected' : '') . '>3ème Trimestre</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Liste des bulletins -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Classe</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Élève</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Moyenne Générale</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matières</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';

if (empty($bulletins)) {
    $content .= '
        <tr>
            <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                Aucun bulletin trouvé pour les critères sélectionnés.
            </td>
        </tr>';
} else {
    foreach ($bulletins as $bulletin) {
        // Déterminer la mention
        $mention = '';
        if ($bulletin['general_average'] >= 16) {
            $mention = 'Très Bien';
        } elseif ($bulletin['general_average'] >= 14) {
            $mention = 'Bien';
        } elseif ($bulletin['general_average'] >= 12) {
            $mention = 'Assez Bien';
        } elseif ($bulletin['general_average'] >= 10) {
            $mention = 'Passable';
        } else {
            $mention = 'Insuffisant';
        }

        $content .= '
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ' . htmlspecialchars($bulletin['class_name']) . '
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ' . htmlspecialchars($bulletin['student_name']) . '
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <div class="font-medium ' . ($bulletin['general_average'] >= 10 ? 'text-green-600' : 'text-red-600') . '">
                    ' . number_format($bulletin['general_average'], 2) . '/20
                </div>
                <div class="text-xs text-gray-500">' . $mention . '</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">
                ' . $bulletin['total_courses'] . ' matières
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">
                ' . $bulletin['total_grades'] . ' notes
                <button type="button" 
                        class="ml-2 text-blue-600 hover:text-blue-900"
                        onclick="showGradeDetails(\'' . htmlspecialchars(addslashes($bulletin['course_details'])) . '\')">
                    Voir détails
                </button>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <a href="viewBulletin.php?student=' . htmlspecialchars($bulletin['student_id']) . 
                   '&class=' . htmlspecialchars($bulletin['class_id']) . 
                   '&period=' . htmlspecialchars($selected_period ?: '1') . '"
                   class="text-blue-600 hover:text-blue-900 mr-3">Voir</a>
                <a href="generateBulletin.php?student=' . htmlspecialchars($bulletin['student_id']) . 
                   '&class=' . htmlspecialchars($bulletin['class_id']) . 
                   '&period=' . htmlspecialchars($selected_period ?: '1') . '"
                   class="text-green-600 hover:text-green-900">Générer PDF</a>
            </td>
        </tr>';
    }
}

$content .= '
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal pour afficher les détails des notes -->
<div id="gradeDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Détails des notes</h3>
            <div id="gradeDetailsContent" class="mt-2 text-sm text-gray-500 whitespace-pre-line"></div>
            <div class="mt-4">
                <button type="button" 
                        onclick="closeGradeDetails()"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm">
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showGradeDetails(details) {
    document.getElementById("gradeDetailsContent").textContent = details;
    document.getElementById("gradeDetailsModal").classList.remove("hidden");
}

function closeGradeDetails() {
    document.getElementById("gradeDetailsModal").classList.add("hidden");
}
</script>';

include('templates/layout.php');
?> 
<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification de la session admin
if (!isset($check)) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Erreur!</strong>
            <span class="block sm:inline">Accès non autorisé.</span>
          </div>';
    exit();
}

$success_message = '';
$error_message = '';

// Debug des paramètres GET
error_log("Paramètres GET reçus : " . print_r($_GET, true));

$selected_class = $_GET['class_id'] ?? '';
$selected_semester = $_GET['semester'] ?? '1';

// Debug des paramètres sélectionnés
error_log("Classe sélectionnée : " . $selected_class);
error_log("Semestre sélectionné : " . $selected_semester);

// Récupérer les moyennes générales de toutes les classes
$selected_semester = isset($_GET['semester']) ? (int)$_GET['semester'] : 1;
global $link;
$escaped_semester = $link->real_escape_string($selected_semester);

// Vérifier d'abord s'il y a des notes pour ce semestre
$check_notes = "SELECT COUNT(*) as note_count FROM student_teacher_course WHERE semester = {$escaped_semester} AND grade IS NOT NULL";
$note_count = db_fetch_row($check_notes);
error_log("Nombre de notes trouvées pour le semestre {$selected_semester}: " . print_r($note_count, true));

$sql = "WITH student_grades AS (
    SELECT 
        stc.student_id,
        stc.class_id,
        stc.course_id,
        CAST(stc.grade AS DECIMAL(5,2)) as grade,
        c.coefficient,
        stc.semester,
        s.name as student_name,
        c.name as course_name
    FROM student_teacher_course stc
    JOIN students s ON stc.student_id = s.id
    JOIN course c ON stc.course_id = c.id
    WHERE stc.semester = {$escaped_semester}
    AND stc.grade IS NOT NULL
    AND stc.grade != ''
),
course_stats AS (
    SELECT 
        class_id,
        course_id,
        course_name,
        COUNT(DISTINCT student_id) as student_count,
        AVG(grade) as course_average,
        SUM(grade * COALESCE(coefficient, 1)) / SUM(COALESCE(coefficient, 1)) as weighted_average
    FROM student_grades
    GROUP BY class_id, course_id, course_name
),
class_stats AS (
    SELECT 
        c.id as class_id,
        c.name as class_name,
        COUNT(DISTINCT sg.student_id) as total_students,
        COALESCE(AVG(cs.weighted_average), 0) as class_average,
        COALESCE(
            SUM(CASE WHEN cs.weighted_average >= 10 THEN 1 ELSE 0 END) * 100.0 / 
            NULLIF(COUNT(DISTINCT cs.course_id), 0), 
            0
        ) as success_rate,
        COALESCE(MAX(cs.weighted_average), 0) as highest_average,
        COALESCE(MIN(cs.weighted_average), 0) as lowest_average
    FROM class c
    LEFT JOIN student_grades sg ON c.id = sg.class_id
    LEFT JOIN course_stats cs ON c.id = cs.class_id
    GROUP BY c.id, c.name
)
SELECT * FROM class_stats
ORDER BY class_name";

// Debug de la requête
error_log("Requête SQL : " . $sql);

$class_stats = db_fetch_all($sql);
error_log("Résultats de la requête : " . print_r($class_stats, true));

// Préparation des paramètres pour la requête
$params = [$selected_semester];
$types = 's';

// Récupération des notes détaillées si une classe est sélectionnée
$grades = [];
if ($selected_class) {
    $query = "WITH student_grades AS (
        SELECT 
            stc.student_id,
            stc.teacher_id,
            stc.class_id,
            stc.course_id,
            stc.semester,
            stc.grade_type,
            stc.grade_number,
            stc.grade,
            CASE 
                WHEN stc.grade_type = 'examen' THEN c.coefficient
                ELSE 1
            END as coefficient,
            c.name as course_name,
            c.coefficient as course_coefficient,
            t.name as teacher_name,
            s.name as student_name
        FROM student_teacher_course stc
        JOIN course c ON stc.course_id = c.id
        JOIN teachers t ON stc.teacher_id = t.id
        JOIN students s ON stc.student_id = s.id
        WHERE stc.class_id = ? 
        AND stc.semester = ?
    )
    SELECT 
        student_id,
        student_name,
        course_name,
        course_coefficient,
        teacher_name,
        -- Notes avec leurs coefficients
        MAX(CASE WHEN grade_type = 'devoir' AND grade_number = 1 THEN grade END) as devoir1,
        MAX(CASE WHEN grade_type = 'devoir' AND grade_number = 1 THEN coefficient END) as devoir1_coef,
        MAX(CASE WHEN grade_type = 'devoir' AND grade_number = 2 THEN grade END) as devoir2,
        MAX(CASE WHEN grade_type = 'devoir' AND grade_number = 2 THEN coefficient END) as devoir2_coef,
        MAX(CASE WHEN grade_type = 'examen' THEN grade END) as examen,
        MAX(CASE WHEN grade_type = 'examen' THEN coefficient END) as examen_coef,
        -- Calcul de la moyenne pondérée
        ROUND(
            (
                COALESCE(MAX(CASE WHEN grade_type = 'devoir' AND grade_number = 1 THEN grade * coefficient END), 0) +
                COALESCE(MAX(CASE WHEN grade_type = 'devoir' AND grade_number = 2 THEN grade * coefficient END), 0) +
                COALESCE(MAX(CASE WHEN grade_type = 'examen' THEN grade * coefficient END), 0)
            ) / NULLIF(
                COALESCE(MAX(CASE WHEN grade_type = 'devoir' AND grade_number = 1 THEN coefficient END), 0) +
                COALESCE(MAX(CASE WHEN grade_type = 'devoir' AND grade_number = 2 THEN coefficient END), 0) +
                COALESCE(MAX(CASE WHEN grade_type = 'examen' THEN coefficient END), 0),
                0
            ), 2
        ) as moyenne
    FROM student_grades
    GROUP BY student_id, student_name, course_name, course_coefficient, teacher_name
    ORDER BY student_name, course_name";

    // Préparation des paramètres pour la requête
    $params = array();
    $params[] = $selected_class; // Premier paramètre pour class_id
    for ($i = 0; $i < 12; $i++) {
        $params[] = $selected_semester; // 12 paramètres pour le semestre
    }
    $types = str_repeat('s', 13); // 13 paramètres de type string

    // Vérification du nombre de paramètres
    $param_count = substr_count($query, '?');
    error_log("Nombre de paramètres dans la requête SQL: " . $param_count);
    error_log("Nombre de paramètres fournis: " . count($params));
    error_log("Nombre de types: " . strlen($types));

    if ($param_count !== count($params)) {
        error_log("ERREUR: Le nombre de paramètres dans la requête (" . $param_count . ") ne correspond pas au nombre de paramètres fournis (" . count($params) . ")");
        $grades = [];
    } else {
        // Debug log pour vérifier la requête
        error_log("Requête SQL: " . $query);
        error_log("Paramètres: " . print_r($params, true));
        error_log("Types: " . $types);

        // Utilisation de db_fetch_all qui gère correctement la liaison des paramètres
        $grades = db_fetch_all($query, $params, $types);
    }

    // Debug log pour vérifier les résultats
    error_log("Nombre de notes trouvées: " . count($grades));
    if (!empty($grades)) {
        error_log("Premier enregistrement: " . print_r($grades[0], true));
    }
}

// Récupérer les informations de l'admin pour la signature
$admin_info = null;
if (isset($_SESSION['login_id'])) {
    $admin_info = db_fetch_row(
        "SELECT name FROM admin WHERE id = ?",
        [$_SESSION['login_id']],
        's'
    );
}

// Récupérer les informations du professeur principal
$teacher_info = null;
if ($selected_class) {
    $teacher_info = db_fetch_row(
        "SELECT t.name, t.id 
         FROM teachers t 
         JOIN student_teacher_course stc ON t.id = stc.teacher_id 
         WHERE stc.class_id = ? 
         GROUP BY t.id 
         ORDER BY COUNT(*) DESC 
         LIMIT 1",
        [$selected_class],
        's'
    );
}

// Fonction pour calculer la moyenne générale d'un étudiant
function calculateGeneralAverage($student_grades) {
    $total_weighted_sum = 0;
    $total_coefficients = 0;
    
    foreach ($student_grades as $grade) {
        if ($grade['moyenne'] !== null) {
            $total_weighted_sum += $grade['moyenne'];
            $total_coefficients += 1;
        }
    }
    
    return $total_coefficients > 0 ? round($total_weighted_sum / $total_coefficients, 2) : null;
}

// Fonction pour calculer le rang de l'étudiant
function calculateRank($student_grades, $all_students_grades) {
    $student_average = calculateGeneralAverage($student_grades);
    if ($student_average === null) return null;
    
    $rank = 1;
    foreach ($all_students_grades as $other_student_id => $other_student_data) {
        $other_average = calculateGeneralAverage($other_student_data['courses']);
        if ($other_average !== null && $other_average > $student_average) {
            $rank++;
        }
    }
    
    return $rank;
}

// Regrouper les notes par étudiant
$student_grades = [];
if ($grades) {
    foreach ($grades as $grade) {
        $student_id = $grade['student_id'];
        if (!isset($student_grades[$student_id])) {
            $student_grades[$student_id] = [
                'name' => $grade['student_name'],
                'courses' => [],
                'general_average' => 0,
                'rank' => 0
            ];
        }
        $student_grades[$student_id]['courses'][] = $grade;
    }
    
    // Calculer la moyenne générale et le rang pour chaque étudiant
    foreach ($student_grades as $student_id => &$student_data) {
        $student_data['general_average'] = calculateGeneralAverage($student_data['courses']);
    }
    
    // Calculer le rang après avoir calculé toutes les moyennes
    foreach ($student_grades as $student_id => &$student_data) {
        $student_data['rank'] = calculateRank($student_data['courses'], $student_grades);
    }
}

// Récupérer les informations de la classe
$class_info = null;
if ($selected_class) {
    $class_info = db_fetch_row(
        "SELECT name, section FROM class WHERE id = ?",
        [$selected_class],
        's'
    );
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Bulletins Scolaires</title>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
            .bulletin-page {
                page-break-after: always;
                margin: 0;
                padding: 20px;
            }
            body {
                background: white;
            }
            .bulletin-container {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
        .print-only {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6 no-print">
            <h1 class="text-3xl font-bold text-gray-800">Bulletins Scolaires</h1>
            <a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700">
                Retour au tableau de bord
            </a>
        </div>

        <!-- Formulaire de sélection -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 no-print">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Sélection de la classe -->
                <div>
                    <label for="class_id" class="block text-sm font-medium text-gray-700 mb-2">Classe</label>
                    <select name="class_id" id="class_id" onchange="this.form.submit()"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Toutes les classes</option>
                        <?php foreach ($class_stats as $class): ?>
                            <option value="<?php echo htmlspecialchars($class['class_id']); ?>"
                                    <?php echo $selected_class === $class['class_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sélection du semestre -->
                <div>
                    <label for="semester" class="block text-sm font-medium text-gray-700 mb-2">Semestre</label>
                    <select name="semester" id="semester" onchange="this.form.submit()"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="1" <?php echo $selected_semester === '1' ? 'selected' : ''; ?>>Semestre 1</option>
                        <option value="2" <?php echo $selected_semester === '2' ? 'selected' : ''; ?>>Semestre 2</option>
                        <option value="3" <?php echo $selected_semester === '3' ? 'selected' : ''; ?>>Semestre 3</option>
                    </select>
                </div>
            </form>
        </div>

        <?php if (!$selected_class): ?>
            <!-- Vue d'ensemble des moyennes par classe -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($class_stats as $class): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </h3>
                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <div>
                                    <p class="text-sm text-gray-600">Moyenne de classe</p>
                                    <p class="text-2xl font-bold <?php echo $class['total_students'] > 0 ? ($class['class_average'] >= 10 ? 'text-green-600' : 'text-red-600') : 'text-gray-400'; ?>">
                                        <?php echo $class['total_students'] > 0 ? number_format($class['class_average'], 2) : 'N/A'; ?>/20
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Taux de réussite</p>
                                    <p class="text-2xl font-bold <?php echo $class['total_students'] > 0 ? ($class['success_rate'] >= 50 ? 'text-green-600' : 'text-red-600') : 'text-gray-400'; ?>">
                                        <?php echo $class['total_students'] > 0 ? number_format($class['success_rate'], 1) : 'N/A'; ?>%
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Meilleure moyenne</p>
                                    <p class="text-lg font-semibold <?php echo $class['total_students'] > 0 ? 'text-green-600' : 'text-gray-400'; ?>">
                                        <?php echo $class['total_students'] > 0 ? number_format($class['highest_average'], 2) : 'N/A'; ?>/20
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Plus basse moyenne</p>
                                    <p class="text-lg font-semibold <?php echo $class['total_students'] > 0 ? 'text-red-600' : 'text-gray-400'; ?>">
                                        <?php echo $class['total_students'] > 0 ? number_format($class['lowest_average'], 2) : 'N/A'; ?>/20
                                    </p>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <p class="text-sm text-gray-600">
                                    <?php echo $class['total_students']; ?> élève<?php echo $class['total_students'] > 1 ? 's' : ''; ?>
                                    <?php if ($class['total_students'] == 0): ?>
                                        (aucune note enregistrée)
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="mt-4">
                                <a href="?class_id=<?php echo htmlspecialchars($class['class_id']); ?>&semester=<?php echo $selected_semester; ?>" 
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Voir les détails
                                    <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Affichage détaillé des bulletins pour la classe sélectionnée -->
            <?php if (!empty($student_grades)): ?>
                <!-- Liste des bulletins -->
                <div class="space-y-8">
                    <?php foreach ($student_grades as $student_id => $student_data): ?>
                        <div class="bulletin-page bg-white rounded-lg shadow-md overflow-hidden bulletin-container">
                            <!-- En-tête du bulletin -->
                            <div class="p-8 border-b-2 border-gray-200">
                                <div class="text-center mb-6">
                                    <h1 class="text-2xl font-bold text-gray-900">BULLETIN SCOLAIRE</h1>
                                    <p class="text-lg text-gray-600">Année Scolaire <?php echo date('Y'); ?>-<?php echo date('Y')+1; ?></p>
                                </div>
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-600">Classe: <span class="font-semibold"><?php echo htmlspecialchars($class_info['name']); ?></span></p>
                                        <p class="text-sm text-gray-600">Section: <span class="font-semibold"><?php echo htmlspecialchars($class_info['section']); ?></span></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Semestre: <span class="font-semibold"><?php echo $selected_semester; ?></span></p>
                                        <p class="text-sm text-gray-600">Date: <span class="font-semibold"><?php echo date('d/m/Y'); ?></span></p>
                                    </div>
                                </div>
                                <div class="border-t border-gray-200 pt-4">
                                    <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($student_data['name']); ?></p>
                                </div>
                            </div>
                            
                            <!-- Notes -->
                            <div class="p-8">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matière</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enseignant</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Devoir 1</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Devoir 2</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Examen</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Moyenne</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Coefficient</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php 
                                        $total_coefficient = 0;
                                        $total_weighted_sum = 0;
                                        foreach ($student_data['courses'] as $course): 
                                            $total_coefficient += $course['course_coefficient']; // Utilisation du coefficient du cours
                                            $total_weighted_sum += $course['moyenne'] * $course['course_coefficient'];
                                        ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($course['teacher_name']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                                    <?php echo $course['devoir1'] ? number_format($course['devoir1'], 2) : '-'; ?>
                                                    <?php if ($course['devoir1_coef']): ?>
                                                        <div class="text-xs text-gray-500">(<?php echo number_format($course['devoir1_coef'], 1); ?>)</div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                                    <?php echo $course['devoir2'] ? number_format($course['devoir2'], 2) : '-'; ?>
                                                    <?php if ($course['devoir2_coef']): ?>
                                                        <div class="text-xs text-gray-500">(<?php echo number_format($course['devoir2_coef'], 1); ?>)</div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                                    <?php echo $course['examen'] ? number_format($course['examen'], 2) : '-'; ?>
                                                    <?php if ($course['examen_coef']): ?>
                                                        <div class="text-xs text-gray-500">(<?php echo number_format($course['examen_coef'], 1); ?>)</div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium <?php echo $course['moyenne'] >= 10 ? 'text-green-600' : 'text-red-600'; ?>">
                                                    <?php echo number_format($course['moyenne'], 2); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                                    <?php echo number_format($course['course_coefficient'], 1); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Résultats -->
                            <div class="p-8 bg-gray-50 border-t-2 border-gray-200">
                                <div class="grid grid-cols-3 gap-4">
                                    <div class="text-center">
                                        <p class="text-sm text-gray-600">Moyenne Générale</p>
                                        <p class="text-xl font-bold <?php echo $student_data['general_average'] >= 10 ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo number_format($student_data['general_average'], 2); ?>
                                        </p>
                                        <p class="text-xs text-gray-500">(Total coefficients: <?php echo number_format($total_coefficient, 1); ?>)</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-sm text-gray-600">Rang</p>
                                        <p class="text-xl font-bold text-gray-900">
                                            <?php echo $student_data['rank']; ?>/<?php echo count($student_grades); ?>
                                        </p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-sm text-gray-600">Mentions</p>
                                        <p class="text-xl font-bold text-gray-900">
                                            <?php
                                            $mention = '';
                                            if ($student_data['general_average'] >= 16) {
                                                $mention = 'Très Bien';
                                            } elseif ($student_data['general_average'] >= 14) {
                                                $mention = 'Bien';
                                            } elseif ($student_data['general_average'] >= 12) {
                                                $mention = 'Assez Bien';
                                            } elseif ($student_data['general_average'] >= 10) {
                                                $mention = 'Passable';
                                            } else {
                                                $mention = 'Insuffisant';
                                            }
                                            echo $mention;
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Signature -->
                            <div class="p-8 border-t-2 border-gray-200">
                                <div class="grid grid-cols-2 gap-8">
                                    <div class="text-center">
                                        <p class="text-sm text-gray-600 mb-2">Le Professeur Principal</p>
                                        <?php if ($teacher_info): ?>
                                            <p class="text-sm font-semibold mb-8"><?php echo htmlspecialchars($teacher_info['name']); ?></p>
                                        <?php endif; ?>
                                        <div class="border-t border-gray-300 w-32 mx-auto"></div>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-sm text-gray-600 mb-2">Le Directeur</p>
                                        <?php if ($admin_info): ?>
                                            <p class="text-sm font-semibold mb-8"><?php echo htmlspecialchars($admin_info['name']); ?></p>
                                        <?php endif; ?>
                                        <div class="border-t border-gray-300 w-32 mx-auto"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Boutons d'action -->
                            <div class="px-8 py-4 bg-gray-50 border-t border-gray-200 no-print">
                                <div class="flex justify-end space-x-4">
                                    <?php if ($admin_info): ?>
                                        <button onclick="signBulletin('director')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                            Signer en tant que Directeur
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($teacher_info && $teacher_info['id'] === $_SESSION['login_id']): ?>
                                        <button onclick="signBulletin('teacher')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                            Signer en tant que Professeur Principal
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700">
                                        Imprimer le bulletin
                                    </button>
                                    <button onclick="exportToPDF('<?php echo $student_id; ?>')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                                        Exporter en PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative">
                    Aucune note n'a été enregistrée pour cette classe.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
    function signBulletin(role) {
        // TODO: Implémenter la signature électronique
        alert('Fonctionnalité de signature à implémenter');
    }

    function exportToPDF(studentId) {
        // TODO: Implémenter l'export PDF
        alert('Fonctionnalité d\'export PDF à implémenter');
    }
    </script>
</body>
</html>
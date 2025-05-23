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
$selected_class = $_GET['class'] ?? '';
$selected_course = $_GET['course_id'] ?? '';
$selected_teacher = $_GET['teacher_id'] ?? '';
$selected_semester = $_GET['semester'] ?? '1';
$status = $_GET['status'] ?? '';

// Debug: Afficher les paramètres reçus
echo "<div class='bg-blue-100 p-4 mb-4'>
    Debug: Classe=$selected_class, Cours=$selected_course, Enseignant=$selected_teacher, Semestre=$selected_semester, Status=$status
</div>";

// Récupération de toutes les classes
$classes = db_fetch_all(
    "SELECT * FROM class ORDER BY name",
    [],
    ''
);

// Récupération des cours pour la classe sélectionnée
$class_courses = [];
if ($selected_class) {
    $class_courses = db_fetch_all(
        "SELECT DISTINCT c.* 
         FROM course c 
         INNER JOIN student_teacher_course stc ON c.id = stc.course_id 
         WHERE stc.class_id = ?
         ORDER BY c.name",
        [$selected_class],
        's'
    );
}

// Récupération des enseignants pour la classe et le cours sélectionnés
$teachers = [];
if ($selected_class && $selected_course) {
    $teachers = db_fetch_all(
        "SELECT DISTINCT t.* 
         FROM teachers t 
         INNER JOIN student_teacher_course stc ON t.id = stc.teacher_id 
         WHERE stc.class_id = ? AND stc.course_id = ?
         ORDER BY t.name",
        [$selected_class, $selected_course],
        'ss'
    );
}

// Traitement de la soumission des notes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_grades'])) {
    try {
        foreach ($_POST['grades'] as $student_id => $grades) {
            foreach ($grades as $type => $grade) {
                if (is_numeric($grade)) {
                    // Debug: Afficher les informations d'insertion
                    echo "<div class='bg-blue-100 p-4 mb-4'>
                        Debug: Insertion - Étudiant=$student_id, Type=$type, Note=$grade
                    </div>";

                    // Vérifier le nombre de devoirs existants pour ce type
                    $existing_grades = db_fetch_all(
                        "SELECT COUNT(*) as count 
                         FROM student_teacher_course 
                         WHERE student_id = ? 
                         AND teacher_id = ? 
                         AND course_id = ? 
                         AND class_id = ? 
                         AND semester = ? 
                         AND grade_type = ?",
                        [$student_id, $selected_teacher, $selected_course, $selected_class, $selected_semester, $type],
                        'ssssss'
                    );

                    $grade_count = $existing_grades[0]['count'] ?? 0;
                    
                    // Si c'est un devoir et qu'il y a déjà 2 devoirs, on ne peut pas en ajouter plus
                    if ($type === 'devoir' && $grade_count >= 2) {
                        $error_message = "Impossible d'ajouter plus de 2 devoirs par semestre.";
                        continue;
                    }

                    // Déterminer le numéro du devoir
                    $grade_number = $grade_count + 1;

                    // Insérer ou mettre à jour la note
                    $result = db_query(
                        "INSERT INTO student_teacher_course 
                         (student_id, teacher_id, course_id, class_id, grade_type, grade, semester, grade_number, coefficient) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE 
                         grade = VALUES(grade),
                         grade_number = VALUES(grade_number),
                         coefficient = VALUES(coefficient),
                         updated_at = NOW()",
                        [
                            $student_id, 
                            $selected_teacher, 
                            $selected_course, 
                            $selected_class, 
                            $type, 
                            $grade, 
                            $selected_semester,
                            $grade_number,
                            $type === 'devoir' ? 1 : 2 // coefficient 1 pour devoir, 2 pour examen
                        ],
                        'sssssssss'
                    );

                    // Debug: Afficher le résultat de l'insertion
                    echo "<div class='bg-blue-100 p-4 mb-4'>
                        Debug: Résultat insertion - " . ($result ? "Succès" : "Échec") . "
                    </div>";
                }
            }
        }
        $success_message = "Les notes ont été enregistrées avec succès.";
    } catch (Exception $e) {
        $error_message = "Une erreur est survenue lors de l'enregistrement des notes: " . $e->getMessage();
    }
}

// Récupération des élèves et leurs notes
$students = [];
if ($selected_class && $selected_course && $selected_teacher) {
    // Debug: Afficher les paramètres de recherche
    echo "<div class='bg-blue-100 p-4 mb-4'>
        Debug: Recherche - Classe=$selected_class, Cours=$selected_course, Enseignant=$selected_teacher, Semestre=$selected_semester
    </div>";

    // Vérifier si les notes existent
    $check_grades = db_fetch_all(
        "SELECT * FROM student_teacher_course 
         WHERE teacher_id = ? 
         AND class_id = ? 
         AND course_id = ? 
         AND semester = ?",
        [$selected_teacher, $selected_class, $selected_course, $selected_semester],
        'ssss'
    );

    // Debug: Afficher les notes trouvées
    echo "<div class='bg-blue-100 p-4 mb-4'>
        Debug: Notes trouvées dans student_teacher_course:<br>";
    foreach ($check_grades as $grade) {
        echo "ID: " . $grade['id'] . 
             ", Étudiant: " . $grade['student_id'] . 
             ", Type: " . $grade['grade_type'] . 
             ", Note: " . $grade['grade'] . 
             ", Numéro: " . $grade['grade_number'] . 
             ", Coefficient: " . $grade['coefficient'] . 
             ", Créé le: " . $grade['created_at'] . 
             ", Mis à jour le: " . $grade['updated_at'] . "<br>";
    }
    echo "</div>";

    // Nouvelle requête pour récupérer les notes
    $query = "SELECT 
        s.id as student_id,
        s.name as student_name,
        MAX(CASE WHEN stc.grade_type = 'devoir' AND stc.grade_number = 1 THEN stc.grade END) as devoir1,
        MAX(CASE WHEN stc.grade_type = 'devoir' AND stc.grade_number = 2 THEN stc.grade END) as devoir2,
        MAX(CASE WHEN stc.grade_type = 'examen' THEN stc.grade END) as examen,
        MAX(CASE WHEN stc.grade_type = 'devoir' AND stc.grade_number = 1 THEN stc.coefficient END) as devoir1_coef,
        MAX(CASE WHEN stc.grade_type = 'devoir' AND stc.grade_number = 2 THEN stc.coefficient END) as devoir2_coef,
        MAX(CASE WHEN stc.grade_type = 'examen' THEN stc.coefficient END) as examen_coef,
        MAX(CASE WHEN stc.grade_type = 'devoir' AND stc.grade_number = 1 THEN stc.created_at END) as devoir1_date,
        MAX(CASE WHEN stc.grade_type = 'devoir' AND stc.grade_number = 2 THEN stc.created_at END) as devoir2_date,
        MAX(CASE WHEN stc.grade_type = 'examen' THEN stc.created_at END) as examen_date,
        MAX(CASE WHEN stc.grade_type = 'devoir' AND stc.grade_number = 1 THEN stc.updated_at END) as devoir1_updated,
        MAX(CASE WHEN stc.grade_type = 'devoir' AND stc.grade_number = 2 THEN stc.updated_at END) as devoir2_updated,
        MAX(CASE WHEN stc.grade_type = 'examen' THEN stc.updated_at END) as examen_updated
    FROM students s
    LEFT JOIN student_teacher_course stc ON s.id = stc.student_id
        AND stc.teacher_id = ?
        AND stc.class_id = ?
        AND stc.course_id = ?
        AND stc.semester = ?
    WHERE s.classid = ?
    GROUP BY s.id, s.name
    ORDER BY s.name";
    
    $params = [$selected_teacher, $selected_class, $selected_course, $selected_semester, $selected_class];
    $types = 'sssss';
    
    // Debug: Afficher la requête SQL
    echo "<div class='bg-blue-100 p-4 mb-4'>Debug: Requête SQL:<br>" . htmlspecialchars($query) . "</div>";
    
    $students = db_fetch_all($query, $params, $types);
    
    // Debug: Afficher le nombre d'élèves trouvés et leurs notes
    echo "<div class='bg-blue-100 p-4 mb-4'>
        Debug: Nombre d'élèves trouvés: " . count($students) . "<br>";
    foreach ($students as $student) {
        echo "Élève: " . htmlspecialchars($student['student_name']) . 
             ", Devoir 1: " . ($student['devoir1'] ?? 'non défini') . 
             " (coef: " . ($student['devoir1_coef'] ?? 'non défini') . ")" .
             ", Devoir 2: " . ($student['devoir2'] ?? 'non défini') . 
             " (coef: " . ($student['devoir2_coef'] ?? 'non défini') . ")" .
             ", Examen: " . ($student['examen'] ?? 'non défini') . 
             " (coef: " . ($student['examen_coef'] ?? 'non défini') . ")<br>";
    }
    echo "</div>";
}

// Récupération de l'historique des notes
$grade_history = [];
if ($selected_class && $selected_course && $selected_teacher) {
    $history_query = "SELECT 
        stc.*,
        s.name as student_name,
        DATE_FORMAT(stc.created_at, '%d/%m/%Y %H:%i') as created_date,
        DATE_FORMAT(stc.updated_at, '%d/%m/%Y %H:%i') as updated_date
    FROM student_teacher_course stc
    JOIN students s ON stc.student_id = s.id
    WHERE stc.teacher_id = ?
    AND stc.class_id = ?
    AND stc.course_id = ?
    AND stc.semester = ?
    ORDER BY stc.created_at DESC";

    $history_params = [$selected_teacher, $selected_class, $selected_course, $selected_semester];
    $history_types = 'ssss';

    $grade_history = db_fetch_all($history_query, $history_params, $history_types);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Gestion des Notes - Administration</title>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Gestion des Notes - Administration</h1>
            <a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700">
                Retour au tableau de bord
            </a>
        </div>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire de sélection -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Sélection de la classe -->
                <div>
                    <label for="class" class="block text-sm font-medium text-gray-700 mb-2">Classe</label>
                    <select name="class" id="class" onchange="this.form.submit()"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Sélectionner une classe</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo htmlspecialchars($class['id']); ?>"
                                    <?php echo $selected_class === $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sélection du cours -->
                <div>
                    <label for="course_id" class="block text-sm font-medium text-gray-700 mb-2">Cours</label>
                    <select name="course_id" id="course_id" onchange="this.form.submit()"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Sélectionner un cours</option>
                        <?php foreach ($class_courses as $course): ?>
                            <option value="<?php echo htmlspecialchars($course['id']); ?>"
                                    <?php echo $selected_course === $course['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sélection de l'enseignant -->
                <div>
                    <label for="teacher_id" class="block text-sm font-medium text-gray-700 mb-2">Enseignant</label>
                    <select name="teacher_id" id="teacher_id" onchange="this.form.submit()"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Sélectionner un enseignant</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo htmlspecialchars($teacher['id']); ?>"
                                    <?php echo $selected_teacher === $teacher['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($teacher['name']); ?>
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

                <!-- Filtre par statut -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                    <select name="status" id="status" onchange="this.form.submit()"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Tous les statuts</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>En attente</option>
                        <option value="validated" <?php echo $status === 'validated' ? 'selected' : ''; ?>>Validé</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejeté</option>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($selected_class && $selected_course && $selected_teacher): ?>
            <!-- Formulaire des notes -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">Notes des élèves</h2>
                </div>
                
                <?php if (empty($students)): ?>
                    <div class="p-6">
                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative">
                            Aucun élève trouvé pour cette classe et ce cours.
                        </div>
                    </div>
                <?php else: ?>
                    <form method="POST" class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Devoir 1 (coef: 1)</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Devoir 2 (coef: 1)</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Examen (coef: 2)</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($student['student_id']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($student['student_name']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <input type="number" name="grades[<?php echo htmlspecialchars($student['student_id']); ?>][devoir1]"
                                                       value="<?php echo htmlspecialchars($student['devoir1'] ?? ''); ?>"
                                                       min="0" max="20" step="0.5"
                                                       class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-20 sm:text-sm border-gray-300 rounded-md">
                                                <?php if ($student['devoir1_date']): ?>
                                                    <div class="text-xs text-gray-500">
                                                        Créé: <?php echo date('d/m/Y H:i', strtotime($student['devoir1_date'])); ?>
                                                        <?php if ($student['devoir1_updated']): ?>
                                                            <br>Modifié: <?php echo date('d/m/Y H:i', strtotime($student['devoir1_updated'])); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <input type="number" name="grades[<?php echo htmlspecialchars($student['student_id']); ?>][devoir2]"
                                                       value="<?php echo htmlspecialchars($student['devoir2'] ?? ''); ?>"
                                                       min="0" max="20" step="0.5"
                                                       class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-20 sm:text-sm border-gray-300 rounded-md">
                                                <?php if ($student['devoir2_date']): ?>
                                                    <div class="text-xs text-gray-500">
                                                        Créé: <?php echo date('d/m/Y H:i', strtotime($student['devoir2_date'])); ?>
                                                        <?php if ($student['devoir2_updated']): ?>
                                                            <br>Modifié: <?php echo date('d/m/Y H:i', strtotime($student['devoir2_updated'])); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <input type="number" name="grades[<?php echo htmlspecialchars($student['student_id']); ?>][examen]"
                                                       value="<?php echo htmlspecialchars($student['examen'] ?? ''); ?>"
                                                       min="0" max="20" step="0.5"
                                                       class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-20 sm:text-sm border-gray-300 rounded-md">
                                                <?php if ($student['examen_date']): ?>
                                                    <div class="text-xs text-gray-500">
                                                        Créé: <?php echo date('d/m/Y H:i', strtotime($student['examen_date'])); ?>
                                                        <?php if ($student['examen_updated']): ?>
                                                            <br>Modifié: <?php echo date('d/m/Y H:i', strtotime($student['examen_updated'])); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <button type="submit" name="submit_grades"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Enregistrer les notes
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Historique des notes -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mt-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">Historique des notes</h2>
                </div>
                
                <?php if (empty($grade_history)): ?>
                    <div class="p-6">
                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative">
                            Aucune note enregistrée pour cette classe et ce cours.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Élève</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Numéro</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Note</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coefficient</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Créé le</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modifié le</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($grade_history as $grade): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($grade['student_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($grade['grade_type'] === 'devoir' ? 'Devoir' : 'Examen'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($grade['grade_number']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($grade['grade']); ?>/20
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($grade['coefficient']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($grade['created_date']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($grade['updated_date']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <button onclick="editGrade(<?php echo htmlspecialchars(json_encode($grade)); ?>)"
                                                    class="text-blue-600 hover:text-blue-900">
                                                Modifier
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Modal de modification de note -->
            <div id="editGradeModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Modifier la note</h3>
                        <form method="POST" id="editGradeForm">
                            <input type="hidden" name="edit_student_id" id="edit_student_id">
                            <input type="hidden" name="edit_grade_type" id="edit_grade_type">
                            <input type="hidden" name="edit_grade_number" id="edit_grade_number">
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Note</label>
                                <input type="number" name="edit_grade" id="edit_grade"
                                       min="0" max="20" step="0.5"
                                       class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                            
                            <div class="flex justify-end space-x-2">
                                <button type="button" onclick="hideEditGradeModal()"
                                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">
                                    Annuler
                                </button>
                                <button type="submit" name="edit_grade_submit"
                                        class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                                    Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <script>
                function editGrade(grade) {
                    document.getElementById('edit_student_id').value = grade.student_id;
                    document.getElementById('edit_grade_type').value = grade.grade_type;
                    document.getElementById('edit_grade_number').value = grade.grade_number;
                    document.getElementById('edit_grade').value = grade.grade;
                    document.getElementById('editGradeModal').classList.remove('hidden');
                }

                function hideEditGradeModal() {
                    document.getElementById('editGradeModal').classList.add('hidden');
                }
            </script>
        <?php endif; ?>
    </div>
</body>
</html> 
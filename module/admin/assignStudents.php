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
$success_message = '';
$error_message = '';

// Traitement du formulaire d'assignation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id'] ?? '';
    $teacher_id = $_POST['teacher_id'] ?? '';
    $course_id = $_POST['course_id'] ?? '';
    $student_ids = $_POST['student_ids'] ?? [];

    if ($class_id && $teacher_id && $course_id && !empty($student_ids)) {
        try {
            // Vérifier que la classe appartient à l'admin connecté
            $class_check = db_fetch_row(
                "SELECT id FROM class WHERE id = ? AND created_by = ?",
                [$class_id, $admin_id],
                'ss'
            );

            // Vérifier que l'enseignant appartient à l'admin connecté
            $teacher_check = db_fetch_row(
                "SELECT id FROM teachers WHERE id = ? AND created_by = ?",
                [$teacher_id, $admin_id],
                'ss'
            );

            // Vérifier que le cours appartient à l'admin connecté
            $course_check = db_fetch_row(
                "SELECT id FROM course WHERE id = ? AND created_by = ?",
                [$course_id, $admin_id],
                'ss'
            );

            if ($class_check && $teacher_check && $course_check) {
                // Supprimer les anciennes assignations pour cette combinaison
                db_query(
                    "DELETE FROM student_teacher_course 
                    WHERE class_id = ? AND teacher_id = ? AND course_id = ? AND created_by = ?",
                    [$class_id, $teacher_id, $course_id, $admin_id],
                    'ssss'
                );

                // Insérer les nouvelles assignations
                foreach ($student_ids as $student_id) {
                    // Vérifier que l'étudiant appartient à l'admin connecté
                    $student_check = db_fetch_row(
                        "SELECT id FROM students WHERE id = ? AND created_by = ?",
                        [$student_id, $admin_id],
                        'ss'
                    );

                    if ($student_check) {
                        db_query(
                            "INSERT INTO student_teacher_course (student_id, teacher_id, course_id, class_id, created_by, created_at) 
                            VALUES (?, ?, ?, ?, ?, NOW())",
                            [$student_id, $teacher_id, $course_id, $class_id, $admin_id],
                            'sssss'
                        );
                    }
                }
                $success_message = 'Les assignations ont été mises à jour avec succès.';
            } else {
                $error_message = 'Vous n\'avez pas les droits pour effectuer cette assignation.';
            }
        } catch (Exception $e) {
            $error_message = 'Une erreur est survenue lors de l\'assignation.';
        }
    } else {
        $error_message = 'Veuillez remplir tous les champs requis.';
    }
}

// Récupération des classes de l'admin
$classes = db_fetch_all(
    "SELECT id, name FROM class WHERE created_by = ? ORDER BY name",
    [$admin_id],
    's'
);

// Récupération des enseignants créés par l'admin
$teachers = db_fetch_all(
    "SELECT id, name FROM teachers WHERE created_by = ? ORDER BY name",
    [$admin_id],
    's'
);

// Récupération des cours créés par l'admin
$courses = db_fetch_all(
    "SELECT id, name FROM course WHERE created_by = ? ORDER BY name",
    [$admin_id],
    's'
);

// Récupération des élèves si une classe est sélectionnée
$selected_class = $_GET['class'] ?? '';
$students = [];
$current_assignments = [];

if ($selected_class) {
    // Vérifier que la classe appartient à l'admin
    $class_check = db_fetch_row(
        "SELECT id FROM class WHERE id = ? AND created_by = ?",
        [$selected_class, $admin_id],
        'ss'
    );

    if ($class_check) {
        $students = db_fetch_all(
            "SELECT id, name FROM students WHERE classid = ? AND created_by = ? ORDER BY name",
            [$selected_class, $admin_id],
            'ss'
        );

        // Récupérer les assignations actuelles
        $current_assignments = db_fetch_all(
            "SELECT student_id, teacher_id, course_id 
            FROM student_teacher_course 
            WHERE class_id = ? AND created_by = ?",
            [$selected_class, $admin_id],
            'ss'
        );
    }
}

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Assignation des Élèves</h1>
    </div>

    ' . ($success_message ? '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">' . htmlspecialchars($success_message) . '</div>' : '') . '
    ' . ($error_message ? '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">' . htmlspecialchars($error_message) . '</div>' : '') . '

    <!-- Sélection de la classe -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Sélectionner une classe</h2>
        <form method="GET" class="grid grid-cols-1 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Classe</label>
                <select name="class" onchange="this.form.submit()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Sélectionner une classe</option>';
                    foreach ($classes as $class) {
                        $content .= '<option value="' . htmlspecialchars($class['id']) . '" ' . 
                                  ($selected_class === $class['id'] ? 'selected' : '') . '>' .
                                  htmlspecialchars($class['name']) . '</option>';
                    }
$content .= '
                </select>
            </div>
        </form>
    </div>';

if ($selected_class && !empty($students)) {
    $content .= '
    <!-- Formulaire d\'assignation -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Assigner les élèves</h2>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <input type="hidden" name="class_id" value="' . htmlspecialchars($selected_class) . '">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Enseignant</label>
                <select name="teacher_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Sélectionner un enseignant</option>';
                    foreach ($teachers as $teacher) {
                        $content .= '<option value="' . htmlspecialchars($teacher['id']) . '">' .
                                  htmlspecialchars($teacher['name']) . '</option>';
                    }
    $content .= '
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Cours</label>
                <select name="course_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Sélectionner un cours</option>';
                    foreach ($courses as $course) {
                        $content .= '<option value="' . htmlspecialchars($course['id']) . '">' .
                                  htmlspecialchars($course['name']) . '</option>';
                    }
    $content .= '
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Élèves</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-h-96 overflow-y-auto p-4 border rounded-md">';
                foreach ($students as $student) {
                    $content .= '
                    <div class="flex items-center">
                        <input type="checkbox" name="student_ids[]" value="' . htmlspecialchars($student['id']) . '" 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label class="ml-2 block text-sm text-gray-900">
                            ' . htmlspecialchars($student['name']) . '
                        </label>
                    </div>';
                }
    $content .= '
                </div>
            </div>

            <div class="md:col-span-2">
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Enregistrer les assignations
                </button>
            </div>
        </form>
    </div>

    <!-- Tableau des assignations actuelles -->
    <div class="mt-8 bg-white rounded-lg shadow-md overflow-hidden">
        <h2 class="text-xl font-semibold text-gray-800 p-6">Assignations actuelles</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Élève</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enseignant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cours</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';

    if (empty($current_assignments)) {
        $content .= '
                    <tr>
                        <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                            Aucune assignation trouvée pour cette classe.
                        </td>
                    </tr>';
    } else {
        foreach ($current_assignments as $assignment) {
            $student_name = db_fetch_row(
                "SELECT name FROM students WHERE id = ? AND created_by = ?",
                [$assignment['student_id'], $admin_id],
                'ss'
            )['name'];
            $teacher_name = db_fetch_row(
                "SELECT name FROM teachers WHERE id = ? AND created_by = ?",
                [$assignment['teacher_id'], $admin_id],
                'ss'
            )['name'];
            $course_name = db_fetch_row(
                "SELECT name FROM course WHERE id = ? AND created_by = ?",
                [$assignment['course_id'], $admin_id],
                'ss'
            )['name'];

            $content .= '
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($student_name) . '</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($teacher_name) . '</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($course_name) . '</td>
                    </tr>';
        }
    }

    $content .= '
                </tbody>
            </table>
        </div>
    </div>';
}

$content .= '
</div>';

include('templates/layout.php');
?> 
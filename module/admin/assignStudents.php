<?php
include_once('main.php');
include_once('includes/auth_check.php');
include_once('../../service/db_utils.php');

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// La vérification des droits d'administrateur est déjà faite dans auth_check.php
// L'ID de l'administrateur est déjà défini dans auth_check.php

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
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Assignation des Élèves</h1>
            </div>

            ' . ($success_message ? '<div class="alert alert-success mb-4">' . htmlspecialchars($success_message) . '</div>' : '') . '
            ' . ($error_message ? '<div class="alert alert-danger mb-4">' . htmlspecialchars($error_message) . '</div>' : '') . '

            <!-- Sélection de la classe -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Sélectionner une classe</h2>
                    <form method="GET">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="class-select" class="form-label">Classe</label>
                                    <select id="class-select" name="class" onchange="this.form.submit()" class="form-select">
                                        <option value="">Sélectionner une classe</option>';
                                        foreach ($classes as $class) {
                                            $content .= '<option value="' . htmlspecialchars($class['id']) . '" ' . 
                                                      ($selected_class === $class['id'] ? 'selected' : '') . '>' .
                                                      htmlspecialchars($class['name']) . '</option>';
                                        }
$content .= '
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>';

if ($selected_class && !empty($students)) {
    $content .= '
    <!-- Formulaire d\'assignation -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Assigner les élèves</h2>
            <form method="POST">
                <input type="hidden" name="class_id" value="' . htmlspecialchars($selected_class) . '">
                
                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="teacher-select" class="form-label">Enseignant</label>
                        <select id="teacher-select" name="teacher_id" required class="form-select">
                            <option value="">Sélectionner un enseignant</option>';
                            foreach ($teachers as $teacher) {
                                $content .= '<option value="' . htmlspecialchars($teacher['id']) . '">' .
                                          htmlspecialchars($teacher['name']) . '</option>';
                            }
    $content .= '
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="course-select" class="form-label">Cours</label>
                        <select id="course-select" name="course_id" required class="form-select">
                            <option value="">Sélectionner un cours</option>';
                            foreach ($courses as $course) {
                                $content .= '<option value="' . htmlspecialchars($course['id']) . '">' .
                                          htmlspecialchars($course['name']) . '</option>';
                            }
    $content .= '
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Élèves</label>
                    <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                        <div class="row">';
                        foreach ($students as $student) {
                            $content .= '
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="student_ids[]" 
                                           value="' . htmlspecialchars($student['id']) . '" id="student-' . htmlspecialchars($student['id']) . '">
                                    <label class="form-check-label" for="student-' . htmlspecialchars($student['id']) . '">
                                        ' . htmlspecialchars($student['name']) . '
                                    </label>
                                </div>
                            </div>';
                        }
    $content .= '
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Enregistrer les assignations
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau des assignations actuelles -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Assignations actuelles</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Élève</th>
                            <th>Enseignant</th>
                            <th>Cours</th>
                        </tr>
                    </thead>
                    <tbody>';

    if (empty($current_assignments)) {
        $content .= '
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">
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
                            <td>' . htmlspecialchars($student_name) . '</td>
                            <td>' . htmlspecialchars($teacher_name) . '</td>
                            <td>' . htmlspecialchars($course_name) . '</td>
                        </tr>';
        }
    }

    $content .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>';
}

$content .= '
        </div>
    </div>
</div>';

include('templates/layout.php');
?>

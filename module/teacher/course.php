<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification de la session (utilise la même méthode que main.php)
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$teacher_id = $_SESSION['login_id'];
// Accept both 'id' and 'course_id' parameters for better compatibility
$course_id = $_GET['id'] ?? $_GET['course_id'] ?? '';

// Définir la variable check pour le template layout.php
$check = $teacher_id;

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

if (!$course) {
    $content = '<div class="alert alert-danger" role="alert">
        <h4 class="alert-heading">Accès non autorisé</h4>
        <p>Vous n\'avez pas accès à ce cours ou le cours n\'existe pas.</p>
        <hr>
        <p class="mb-0">Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.</p>
    </div>';
    include('templates/layout.php');
    exit();
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
                // Vérifier si une note existe déjà en incluant teacher_id et class_id pour éviter les duplications
                $existing_grade = db_fetch_row(
                    "SELECT id FROM student_teacher_course 
                     WHERE student_id = ? 
                     AND teacher_id = ? 
                     AND course_id = ? 
                     AND class_id = ? 
                     AND grade_type = ? 
                     AND grade_number = ? 
                     AND semester = ?",
                    [$student_id, $teacher_id, $course_id, $class_id, $grade_type, $grade_number, $semester],
                    'sssssss'
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
             WHERE student_id = s.id COLLATE utf8mb4_general_ci 
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
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-2">Gestion des Notes</h1>
        <p class="text-muted">Cours : ' . safe_html($course['name']) . ' - Classe : ' . safe_html($course['class_name']) . '</p>
        <p class="small text-muted">Coefficient : ' . htmlspecialchars($course['coefficient']) . ' (défini par l\'administrateur)</p>
    </div>';

// Messages de succès/erreur
if (isset($success_message)) {
    $content .= '
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        ' . safe_html($success_message) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}
if (isset($error_message)) {
    $content .= '
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        ' . safe_html($error_message) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

// Formulaire de saisie des notes
$content .= '
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">Saisie des notes</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="mb-4">
                <input type="hidden" name="id" value="' . safe_html($course_id) . '">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="grade_type" class="form-label">Type d\'évaluation</label>
                        <select id="grade_type" name="grade_type" onchange="this.form.submit()" class="form-select">
                            <option value="devoir" ' . (($_GET['grade_type'] ?? '') === 'devoir' ? 'selected' : '') . '>Devoir</option>
                            <option value="examen" ' . (($_GET['grade_type'] ?? '') === 'examen' ? 'selected' : '') . '>Examen</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="grade_number" class="form-label">Numéro</label>
                        <select id="grade_number" name="grade_number" onchange="this.form.submit()" class="form-select">
                            <option value="1" ' . (($_GET['grade_number'] ?? '') === '1' ? 'selected' : '') . '>1</option>
                            <option value="2" ' . (($_GET['grade_number'] ?? '') === '2' ? 'selected' : '') . '>2</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="semester" class="form-label">Semestre</label>
                        <select id="semester" name="semester" onchange="this.form.submit()" class="form-select">
                            <option value="1" ' . (($_GET['semester'] ?? '') === '1' ? 'selected' : '') . '>Semestre 1</option>
                            <option value="2" ' . (($_GET['semester'] ?? '') === '2' ? 'selected' : '') . '>Semestre 2</option>
                            <option value="3" ' . (($_GET['semester'] ?? '') === '3' ? 'selected' : '') . '>Semestre 3</option>
                        </select>
                    </div>
                </div>
            </form>

        <form method="POST">
            <input type="hidden" name="grade_type" value="' . safe_html($_GET['grade_type'] ?? 'devoir') . '">
            <input type="hidden" name="grade_number" value="' . safe_html($_GET['grade_number'] ?? '1') . '">
            <input type="hidden" name="semester" value="' . safe_html($_GET['semester'] ?? '1') . '">

            <div class="table-responsive mt-4">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Nom</th>
                            <th scope="col">Note</th>
                            <th scope="col">Appréciation</th>
                        </tr>
                    </thead>
                    <tbody>';

foreach ($students as $student) {
    $content .= '
        <tr>
            <td>' . safe_html($student['id']) . '</td>
            <td>' . safe_html($student['name']) . '</td>
            <td>
                <input type="number" name="grades[' . safe_html($student['id']) . '][grade]" 
                       value="' . safe_html($student['current_grade']) . '"
                       min="0" max="20" step="0.5"
                       class="form-control form-control-sm" style="width: 80px;">
            </td>
            <td>
                <textarea name="grades[' . safe_html($student['id']) . '][comment]" 
                          rows="2"
                          class="form-control form-control-sm"
                          placeholder="Appréciation..."></textarea>
            </td>
        </tr>';
}

$content .= '
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" name="submit_grades" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Enregistrer les notes
                </button>
            </div>
        </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">Historique des notes</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label for="filter_type" class="form-label">Type d\'évaluation</label>
                    <select id="filter_type" class="form-select">
                        <option value="">Tous</option>
                        <option value="devoir">Devoir</option>
                        <option value="examen">Examen</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_number" class="form-label">Numéro</label>
                    <select id="filter_number" class="form-select">
                        <option value="">Tous</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_semester" class="form-label">Semestre</label>
                    <select id="filter_semester" class="form-select">
                        <option value="">Tous</option>
                        <option value="1">Semestre 1</option>
                        <option value="2">Semestre 2</option>
                        <option value="3">Semestre 3</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_date" class="form-label">Date</label>
                    <input type="date" id="filter_date" class="form-control">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover" id="grades-table">
                    <thead>
                        <tr>
                            <th scope="col">Élève</th>
                            <th scope="col">Type</th>
                            <th scope="col">Numéro</th>
                            <th scope="col">Note</th>
                            <th scope="col">Semestre</th>
                            <th scope="col">Date</th>
                            <th scope="col">Professeur</th>
                        </tr>
                    </thead>
                    <tbody>';

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
     JOIN students s ON stc.student_id = s.id COLLATE utf8mb4_general_ci
     JOIN course c ON stc.course_id = c.id COLLATE utf8mb4_general_ci
     JOIN class cl ON stc.class_id = cl.id COLLATE utf8mb4_general_ci
     JOIN teachers t ON stc.teacher_id = t.id COLLATE utf8mb4_general_ci
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
            <td>' . safe_html($grade['student_name']) . '</td>
            <td>' . ($grade['grade_type'] === 'devoir' ? 'Devoir' : 'Examen') . '</td>
            <td>' . safe_html($grade['grade_number']) . '</td>
            <td><span class="badge bg-primary">' . safe_html($grade['grade']) . '/20</span></td>
            <td>Semestre ' . safe_html($grade['semester']) . '</td>
            <td>' . safe_html($grade['formatted_date']) . '</td>
            <td>' . safe_html($grade['teacher_name']) . '</td>
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
</div>
</div>';

// Utiliser le template enseignant
include('templates/layout.php');
?>

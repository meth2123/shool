<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];
$course_id = isset($_GET['id']) && !empty($_GET['id']) ? $_GET['id'] : null;

if (!$course_id) {
    header("Location: course.php?error=" . urlencode("ID du cours non spécifié"));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_name = $_POST['name'] ?? '';
    $teacher_id = $_POST['teacher_id'] ?? '';
    $class_id = $_POST['class_id'] ?? '';

    // Validate that this course belongs to the admin and get current created_by value
    $check_sql = "SELECT id, created_by FROM course WHERE id = ? AND created_by = ?";
    $check_stmt = $link->prepare($check_sql);
    $check_stmt->bind_param("ss", $course_id, $admin_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $current_course = $check_result->fetch_assoc();

    if (!$current_course) {
        header("Location: course.php?error=" . urlencode("Accès non autorisé"));
        exit;
    }

    // Validate that the selected teacher was created by this admin
    if (!empty($teacher_id)) {
        $check_teacher_sql = "SELECT id FROM teachers WHERE id = ? AND created_by = ?";
        $check_teacher_stmt = $link->prepare($check_teacher_sql);
        $check_teacher_stmt->bind_param("ss", $teacher_id, $admin_id);
        $check_teacher_stmt->execute();
        if ($check_teacher_stmt->get_result()->num_rows === 0) {
            header("Location: course.php?error=" . urlencode("Professeur non autorisé"));
            exit;
        }
    }

    // Update course while preserving the created_by value
    $update_sql = "UPDATE course SET name = ?, teacherid = ?, classid = ? WHERE id = ? AND created_by = ?";
    $update_stmt = $link->prepare($update_sql);
    $update_stmt->bind_param("sssss", $course_name, $teacher_id, $class_id, $course_id, $admin_id);
    
    if ($update_stmt->execute()) {
        header("Location: viewCourse.php?id=" . urlencode($course_id) . "&success=1");
        exit;
    } else {
        $error = "Erreur lors de la mise à jour du cours";
    }
}

// Get current course data
$sql = "SELECT c.*, t.name as teacher_name, cl.name as class_name 
        FROM course c 
        LEFT JOIN teachers t ON c.teacherid = t.id 
        LEFT JOIN class cl ON c.classid = cl.id 
        WHERE c.id = ? AND c.created_by = ?";

$stmt = $link->prepare($sql);
$stmt->bind_param("ss", $course_id, $admin_id);
$stmt->execute();
$course_result = $stmt->get_result();
$course = $course_result->fetch_assoc();

if (!$course) {
    header("Location: course.php?error=" . urlencode("Cours non trouvé ou accès non autorisé"));
    exit;
}

// Get only teachers created by this admin
$teachers_sql = "SELECT id, name FROM teachers WHERE created_by = ? ORDER BY name";
$teachers_stmt = $link->prepare($teachers_sql);
$teachers_stmt->bind_param("s", $admin_id);
$teachers_stmt->execute();
$teachers_result = $teachers_stmt->get_result();

// Get classes list
$classes_sql = "SELECT id, name FROM class ORDER BY name";
$classes_result = $link->query($classes_sql);

$content = '
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Modifier le Cours</h2>
                <a href="viewCourse.php?id=' . htmlspecialchars($course_id) . '" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Retour aux détails
                </a>
            </div>

            ' . (isset($error) ? '<div class="alert alert-danger mb-4">' . htmlspecialchars($error) . '</div>' : '') . '

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Informations du cours</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom du cours</label>
                                    <input type="text" name="name" id="name" required
                                           value="' . htmlspecialchars($course['name']) . '"
                                           class="form-control">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="teacher_id" class="form-label">Enseignant</label>
                                    <select name="teacher_id" id="teacher_id" class="form-select">
                                        <option value="">Sélectionner un enseignant</option>';

                            while ($teacher = $teachers_result->fetch_assoc()) {
                                $selected = ($teacher['id'] == $course['teacherid']) ? 'selected' : '';
                                $content .= '<option value="' . htmlspecialchars($teacher['id']) . '" ' . $selected . '>'
                                        . htmlspecialchars($teacher['name']) . '</option>';
                            }

$content .= '
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="class_id" class="form-label">Classe</label>
                                    <select name="class_id" id="class_id" class="form-select">
                                        <option value="">Sélectionner une classe</option>';

                            while ($class = $classes_result->fetch_assoc()) {
                                $selected = ($class['id'] == $course['classid']) ? 'selected' : '';
                                $content .= '<option value="' . htmlspecialchars($class['id']) . '" ' . $selected . '>'
                                        . htmlspecialchars($class['name']) . '</option>';
                            }

$content .= '
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>';

include('templates/layout.php');
?>
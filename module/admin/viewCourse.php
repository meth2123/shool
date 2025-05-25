<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];
$course_id = $_GET['id'] ?? '';

// Récupérer les informations du cours
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

$content = '
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Détails du Cours</h2>
                <a href="course.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                </a>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Informations du cours</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">ID du cours</label>
                                <p class="mb-0">' . htmlspecialchars($course['id']) . '</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Nom du cours</label>
                                <p class="mb-0">' . htmlspecialchars($course['name']) . '</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Enseignant</label>
                                <p class="mb-0">' . htmlspecialchars($course['teacher_name'] ?? 'Non assigné') . '</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Classe</label>
                                <p class="mb-0">' . htmlspecialchars($course['class_name'] ?? 'Non assignée') . '</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-end gap-2">
                    <a href="updateCourse.php?id=' . htmlspecialchars($course_id) . '" 
                       class="btn btn-primary">
                       <i class="fas fa-edit me-2"></i>Modifier le cours
                    </a>
                    <button onclick="confirmDelete(\'' . htmlspecialchars($course_id) . '\')" 
                            class="btn btn-danger">
                        <i class="fas fa-trash-alt me-2"></i>Supprimer le cours
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(courseId) {
    if (confirm("Êtes-vous sûr de vouloir supprimer ce cours ?")) {
        window.location.href = "deleteCourse.php?id=" + courseId;
    }
}
</script>';

include('templates/layout.php');
?>

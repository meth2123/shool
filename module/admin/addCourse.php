<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];

// Récupérer les informations de l'admin
$admin_sql = "SELECT name FROM admin WHERE id = ?";
$admin_stmt = $link->prepare($admin_sql);
$admin_stmt->bind_param("s", $admin_id);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$admin_row = $admin_result->fetch_assoc();
$admin_name = $admin_row['name'] ?? 'Admin';

// Récupérer les classes
$class_sql = "SELECT id, name FROM class WHERE created_by = ? ORDER BY name";
$class_stmt = $link->prepare($class_sql);
$class_stmt->bind_param("s", $admin_id);
$class_stmt->execute();
$class_result = $class_stmt->get_result();

// Récupérer les enseignants
$teacher_sql = "SELECT id, name FROM teachers WHERE created_by = ? ORDER BY name";
$teacher_stmt = $link->prepare($teacher_sql);
$teacher_stmt->bind_param("s", $admin_id);
$teacher_stmt->execute();
$teacher_result = $teacher_stmt->get_result();

$content = '
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="mb-4">
                <h2>Ajouter un Cours</h2>
                <p class="text-muted">Créez un nouveau cours en remplissant les informations ci-dessous.</p>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Informations du cours</h5>
                </div>
                <div class="card-body">
                    <form action="includes/process_course.php" method="POST">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="created_by" value="' . htmlspecialchars($admin_id) . '">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom du cours</label>
                            <input type="text" name="name" id="name" required class="form-control">
                        </div>

                        <div class="mb-3">
                            <label for="classid" class="form-label">Classe</label>
                            <select name="classid" id="classid" required class="form-select">';
                    
                    while ($class = $class_result->fetch_assoc()) {
                        $content .= '<option value="' . htmlspecialchars($class['id']) . '">' . 
                            htmlspecialchars($class['name']) . '</option>';
                    }

$content .= '
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="teacherid" class="form-label">Enseignant</label>
                            <select name="teacherid" id="teacherid" required class="form-select">';
                    
                    while ($teacher = $teacher_result->fetch_assoc()) {
                        $content .= '<option value="' . htmlspecialchars($teacher['id']) . '">' . 
                            htmlspecialchars($teacher['name']) . '</option>';
                    }

$content .= '
                            </select>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="course.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>Ajouter le cours
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

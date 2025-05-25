<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];

// Gestion des messages de succès/erreur
$success_message = isset($_GET['success']) ? '<div class="alert alert-success mb-4">' . htmlspecialchars($_GET['success']) . '</div>' : '';
$error_message = isset($_GET['error']) ? '<div class="alert alert-danger mb-4">' . htmlspecialchars($_GET['error']) . '</div>' : '';

// Récupération des cours créés par cet admin
$sql = "SELECT c.*, t.name as teacher_name, cl.name as class_name 
        FROM course c 
        LEFT JOIN teachers t ON c.teacherid = t.id 
        LEFT JOIN class cl ON c.classid = cl.id 
        WHERE c.created_by = ?
        ORDER BY c.name";

$stmt = $link->prepare($sql);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

$content = '
<div class="container py-4">
    ' . $success_message . $error_message . '
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Gestion des Cours</h4>
                    <a href="addCourse.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Ajouter un Cours
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nom du cours</th>
                                    <th>Enseignant</th>
                                    <th>Classe</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>';

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $content .= '
                        <tr>
                            <td>' . htmlspecialchars($row['id']) . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['teacher_name'] ?? 'Non assigné') . '</td>
                            <td>' . htmlspecialchars($row['class_name'] ?? 'Non assignée') . '</td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="updateCourse.php?id=' . htmlspecialchars($row['id']) . '" 
                                       class="btn btn-outline-primary" title="Modifier">
                                       <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="viewCourse.php?id=' . htmlspecialchars($row['id']) . '" 
                                       class="btn btn-outline-success" title="Voir">
                                       <i class="fas fa-eye"></i>
                                    </a>
                                    <button onclick="confirmDelete(\'' . htmlspecialchars($row['id']) . '\')" 
                                            class="btn btn-outline-danger" title="Supprimer">
                                            <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>';
                    }
                } else {
                    $content .= '
                    <tr>
                        <td colspan="5" class="text-center p-4">
                            <div class="py-5">
                                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                                <p>Aucun cours trouvé.</p>
                                <a href="addCourse.php" class="btn btn-primary mt-2">
                                    <i class="fas fa-plus-circle me-2"></i>Ajouter un cours
                                </a>
                            </div>
                        </td>
                    </tr>';
                }

$content .= '
                            </tbody>
                        </table>
                    </div>
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

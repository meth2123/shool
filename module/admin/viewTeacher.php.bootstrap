<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$admin_id = $_SESSION['login_id'];
$teacher_id = $_GET['id'] ?? '';
$error_message = '';
$teacher_data = null;

// Vérifier si l'enseignant existe et appartient à cet admin
if ($teacher_id) {
    $sql = "SELECT t.*, u.userid as creator_id 
            FROM teachers t 
            LEFT JOIN users u ON t.created_by = u.userid 
            WHERE t.id = ? AND t.created_by = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("ss", $teacher_id, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher_data = $result->fetch_assoc();

    if (!$teacher_data) {
        header("Location: manageTeacher.php?error=" . urlencode("Enseignant non trouvé ou accès non autorisé"));
        exit;
    }
}

// Récupérer les cours de l'enseignant
$courses_sql = "SELECT c.id as courseid, c.name as coursename, cl.name as classname
                FROM course c 
                INNER JOIN takencoursebyteacher tc ON c.id = tc.courseid 
                LEFT JOIN class cl ON c.classid = cl.id
                WHERE tc.teacherid = ?";
$courses_stmt = $link->prepare($courses_sql);
$courses_stmt->bind_param("s", $teacher_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();

$content = '
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                <h2 class="h3 mb-3 mb-md-0">Détails de l\'Enseignant</h2>
                <a href="manageTeacher.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                </a>
            </div>

            <!-- Carte d\'information principale -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 pb-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="fas fa-user text-primary fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 mb-1">' . htmlspecialchars($teacher_data['name']) . '</h3>
                                <p class="text-muted small mb-0">ID: ' . htmlspecialchars($teacher_data['id']) . '</p>
                            </div>
                        </div>
                        <div class="mt-2 mt-md-0">
                            ' . ($teacher_data['sex'] == 'female' ? 
                                '<span class="badge bg-danger bg-opacity-10 text-danger">Femme</span>' : 
                                '<span class="badge bg-primary bg-opacity-10 text-primary">Homme</span>') . '
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4 mb-md-0">
                            <h4 class="h6 text-muted mb-3">Informations de contact</h4>
                            <ul class="list-unstyled">
                                <li class="d-flex mb-3">
                                    <div class="flex-shrink-0 text-muted me-2">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 fw-medium">' . htmlspecialchars($teacher_data['email']) . '</p>
                                        <p class="text-muted small mb-0">Email</p>
                                    </div>
                                </li>
                                <li class="d-flex mb-3">
                                    <div class="flex-shrink-0 text-muted me-2">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 fw-medium">' . htmlspecialchars($teacher_data['phone']) . '</p>
                                        <p class="text-muted small mb-0">Téléphone</p>
                                    </div>
                                </li>
                                <li class="d-flex">
                                    <div class="flex-shrink-0 text-muted me-2">
                                        <i class="fas fa-home"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 fw-medium">' . htmlspecialchars($teacher_data['address'] ?: 'Non renseignée') . '</p>
                                        <p class="text-muted small mb-0">Adresse</p>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div class="col-md-6">
                            <h4 class="h6 text-muted mb-3">Informations professionnelles</h4>
                            <ul class="list-unstyled">
                                <li class="d-flex mb-3">
                                    <div class="flex-shrink-0 text-muted me-2">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 fw-medium">' . htmlspecialchars($teacher_data['dob']) . '</p>
                                        <p class="text-muted small mb-0">Date de naissance</p>
                                    </div>
                                </li>
                                <li class="d-flex mb-3">
                                    <div class="flex-shrink-0 text-muted me-2">
                                        <i class="fas fa-briefcase"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 fw-medium">' . htmlspecialchars($teacher_data['hiredate']) . '</p>
                                        <p class="text-muted small mb-0">Date d\'embauche</p>
                                    </div>
                                </li>
                                <li class="d-flex">
                                    <div class="flex-shrink-0 text-muted me-2">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 fw-medium">' . htmlspecialchars($teacher_data['salary']) . ' €</p>
                                        <p class="text-muted small mb-0">Salaire</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cours enseignés -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h3 class="h5 mb-0">Cours Enseignés</h3>
                    <p class="text-muted small mb-0">Liste des cours assignés à cet enseignant</p>
                </div>
                
                <!-- Vue mobile (cartes) -->
                <div class="d-block d-md-none">';
                
                if ($courses_result->num_rows > 0) {
                    $content .= '<div class="list-group list-group-flush">';
                    while ($course = $courses_result->fetch_assoc()) {
                        $content .= '
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h5 class="mb-0 h6">' . htmlspecialchars($course['coursename']) . '</h5>
                                    <p class="text-muted small mb-0">ID: ' . htmlspecialchars($course['courseid']) . '</p>
                                </div>
                                <span class="badge bg-primary bg-opacity-10 text-primary">' . 
                                    htmlspecialchars($course['classname'] ?: 'Non assigné') . 
                                '</span>
                            </div>
                            <div class="mt-2 text-end">
                                <a href="viewCourse.php?id=' . htmlspecialchars($course['courseid']) . '" 
                                   class="btn btn-sm btn-outline-primary">
                                   <i class="fas fa-eye me-1"></i>Voir le cours
                                </a>
                            </div>
                        </div>';
                    }
                    $content .= '</div>';
                    
                    // Reset the result pointer for desktop view
                    $courses_stmt->execute();
                    $courses_result = $courses_stmt->get_result();
                } else {
                    $content .= '
                    <div class="text-center p-4">
                        <div class="mx-auto d-flex align-items-center justify-content-center bg-light rounded-circle" style="width: 60px; height: 60px;">
                            <i class="fas fa-book-open text-muted fs-4"></i>
                        </div>
                        <h5 class="mt-3 h6">Aucun cours</h5>
                        <p class="text-muted small">Aucun cours n\'est assigné à cet enseignant pour le moment.</p>
                    </div>';
                }
                
                $content .= '
                </div>
                
                <!-- Vue desktop (tableau) -->
                <div class="d-none d-md-block">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nom du cours</th>
                                    <th>Classe</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>';
                
                if ($courses_result->num_rows > 0) {
                    while ($course = $courses_result->fetch_assoc()) {
                        $content .= '
                        <tr>
                            <td>' . htmlspecialchars($course['courseid']) . '</td>
                            <td>' . htmlspecialchars($course['coursename']) . '</td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary">' . 
                                    htmlspecialchars($course['classname'] ?: 'Non assigné') . 
                                '</span>
                            </td>
                            <td>
                                <a href="viewCourse.php?id=' . htmlspecialchars($course['courseid']) . '" 
                                   class="btn btn-sm btn-outline-primary">
                                   <i class="fas fa-eye me-1"></i>Voir
                                </a>
                            </td>
                        </tr>';
                    }
                } else {
                    $content .= '
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <div class="d-flex flex-column align-items-center">
                                <i class="fas fa-book-open text-muted fs-4 mb-2"></i>
                                <p class="text-muted mb-0">Aucun cours assigné à cet enseignant</p>
                            </div>
                        </td>
                    </tr>';
                }
                
                $content .= '
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card-footer bg-white">
                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                        <a href="updateTeacher.php?id=' . htmlspecialchars($teacher_id) . '" 
                           class="btn btn-primary">
                           <i class="fas fa-edit me-2"></i>Modifier
                        </a>
                        <button onclick="confirmDelete(\'' . htmlspecialchars($teacher_id) . '\')" 
                                class="btn btn-danger">
                            <i class="fas fa-trash-alt me-2"></i>Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(teacherId) {
    if (confirm("Êtes-vous sûr de vouloir supprimer cet enseignant ?")) {
        window.location.href = "deleteTeacher.php?id=" + teacherId;
    }
}
</script>';

include('templates/layout.php');
?>

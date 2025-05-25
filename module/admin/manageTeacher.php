<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];

// Gestion des messages de succès/erreur
$success_message = isset($_GET['success']) ? '<div class="alert alert-success mb-4">' . htmlspecialchars($_GET['success']) . '</div>' : '';
$error_message = isset($_GET['error']) ? '<div class="alert alert-danger mb-4">' . htmlspecialchars($_GET['error']) . '</div>' : '';

// Par défaut, on filtre par l'admin connecté
$where_clause = ' WHERE t.created_by = ?';
$params = [$admin_id];
$param_types = 's';

// Récupération des enseignants
$sql = "SELECT t.*, u.userid as creator_id 
        FROM teachers t 
        LEFT JOIN users u ON t.created_by = u.userid" . $where_clause . 
        " ORDER BY t.name";

$stmt = $link->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$content = '
<div class="container py-4">
    ' . $success_message . $error_message . '
    
    <div class="mb-4">
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-4 bg-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h4 class="card-title mb-0 fs-4">Gestion des Enseignants</h4>
                <a href="addTeacher.php" class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
                    <i class="fas fa-plus-circle"></i><span>Ajouter un Enseignant</span>
                </a>
            </div>
            
            <!-- Vue mobile (cartes) -->
            <div class="d-md-none">
                <div class="list-group list-group-flush">';

            if ($result->num_rows > 0) {
                // Pour la vue mobile (cartes)
                $mobile_content = '';
                // Pour la vue desktop (tableau)
                $desktop_content = '';
                
                while ($row = $result->fetch_assoc()) {
                    $gender_badge = $row['sex'] == 'female' ? 
                        '<span class="badge bg-pink rounded-pill">F</span>' :
                        '<span class="badge bg-primary rounded-pill">M</span>';
                    
                    // Vue mobile (carte)
                    $mobile_content .= '
                    <div class="list-group-item p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0 fw-semibold">' . htmlspecialchars($row['name']) . '</h5>
                            ' . $gender_badge . '
                        </div>
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-1">
                                <i class="fas fa-id-card text-muted me-2"></i>
                                <span class="text-muted small">ID:</span>
                                <span class="ms-2">' . htmlspecialchars($row['id']) . '</span>
                            </div>
                            <div class="d-flex align-items-center mb-1">
                                <i class="fas fa-envelope text-muted me-2"></i>
                                <span>' . htmlspecialchars($row['email']) . '</span>
                            </div>
                            <div class="d-flex align-items-center mb-1">
                                <i class="fas fa-phone text-muted me-2"></i>
                                <span>' . htmlspecialchars($row['phone']) . '</span>
                            </div>
                            <div class="d-flex align-items-center mb-1">
                                <i class="fas fa-calendar-alt text-muted me-2"></i>
                                <span class="text-muted small">Embauché le:</span>
                                <span class="ms-2">' . htmlspecialchars($row['hiredate'] ?? 'N/A') . '</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-money-bill-wave text-muted me-2"></i>
                                <span class="text-muted small">Salaire:</span>
                                <span class="ms-2 fw-semibold">' . htmlspecialchars($row['salary'] ?? 'N/A') . ' €</span>
                            </div>
                        </div>
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="viewTeacher.php?id=' . htmlspecialchars($row['id']) . '" 
                               class="btn btn-sm btn-outline-success" title="Voir">
                               <i class="fas fa-eye"></i>
                            </a>
                            <a href="updateTeacher.php?id=' . htmlspecialchars($row['id']) . '" 
                               class="btn btn-sm btn-outline-primary" title="Modifier">
                               <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="confirmDelete(\'' . htmlspecialchars($row['id']) . '\')" 
                                    class="btn btn-sm btn-outline-danger" title="Supprimer">
                                    <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>';
                    
                    // Vue desktop (tableau)
                    $desktop_content .= '
                    <tr>
                        <td>' . htmlspecialchars($row['id']) . '</td>
                        <td>' . htmlspecialchars($row['name']) . '</td>
                        <td>' . htmlspecialchars($row['email']) . '</td>
                        <td>' . htmlspecialchars($row['phone']) . '</td>
                        <td>' . $gender_badge . '</td>
                        <td>' . htmlspecialchars($row['hiredate'] ?? 'N/A') . '</td>
                        <td>' . htmlspecialchars($row['salary'] ?? 'N/A') . ' €</td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="updateTeacher.php?id=' . htmlspecialchars($row['id']) . '" 
                                   class="btn btn-outline-primary" title="Modifier">
                                   <i class="fas fa-edit"></i>
                                </a>
                                <a href="viewTeacher.php?id=' . htmlspecialchars($row['id']) . '" 
                                   class="btn btn-outline-success" title="Voir">
                                   <i class="fas fa-eye"></i>
                                </a>
                                <button onclick="confirmDelete(\'' . htmlspecialchars($row['id']) . '\')" 
                                        class="btn btn-sm btn-outline-danger" title="Supprimer">
                                        <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>';
                    }
                    
                    $content .= $mobile_content;
                } else {
                    $content .= '
                    <div class="list-group-item p-4 text-center">
                        <div class="py-5">
                            <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                            <p>Aucun enseignant trouvé.</p>
                            <a href="addTeacher.php" class="btn btn-primary mt-2">
                                <i class="fas fa-plus-circle me-2"></i>Ajouter un enseignant
                            </a>
                        </div>
                    </div>';
                }
                
$content .= '
                </div>
            </div>
            
            <!-- Vue desktop (tableau) -->
            <div class="d-none d-md-block">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Genre</th>
                                <th>Date d\'embauche</th>
                                <th>Salaire</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>';
                        
                        if ($result->num_rows > 0) {
                            $content .= $desktop_content;
                        } else {
                            $content .= '
                            <tr>
                                <td colspan="8" class="text-center p-4">
                                    <div class="py-5">
                                        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                                        <p>Aucun enseignant trouvé.</p>
                                        <a href="addTeacher.php" class="btn btn-primary mt-2">
                                            <i class="fas fa-plus-circle me-2"></i>Ajouter un enseignant
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

<script>
function confirmDelete(teacherId) {
    if (confirm("Êtes-vous sûr de vouloir supprimer cet enseignant ?")) {
        window.location.href = "deleteTeacher.php?id=" + teacherId;
    }
}
</script>';

include('templates/layout.php');
?>

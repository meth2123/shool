<?php
include_once('main.php');
include_once('includes/auth_check.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

// L'ID de l'administrateur est déjà défini dans auth_check.php
// $admin_id = $_SESSION['login_id'];

// Get staff list created by this admin
$sql = "SELECT s.*, u.userid 
        FROM staff s 
        LEFT JOIN users u ON s.id = u.userid 
        WHERE s.created_by = ? 
        ORDER BY s.name";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

$content = '
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Gestion du Personnel</h4>
                    <a href="addStaff.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Ajouter un membre du personnel
                    </a>
                </div>
                <div class="card-body">
                    ' . (isset($_GET['success']) ? '<div class="alert alert-success mb-4">' . htmlspecialchars($_GET['success']) . '</div>' : '') . '
                    ' . (isset($_GET['error']) ? '<div class="alert alert-danger mb-4">' . htmlspecialchars($_GET['error']) . '</div>' : '') . '
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th>Adresse</th>
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
            <td>' . htmlspecialchars($row['email']) . '</td>
            <td>' . htmlspecialchars($row['phone']) . '</td>
            <td>' . htmlspecialchars($row['address']) . '</td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="viewStaff.php?id=' . htmlspecialchars($row['id']) . '" 
                       class="btn btn-outline-primary" title="Voir">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="updateStaff.php?id=' . htmlspecialchars($row['id']) . '" 
                       class="btn btn-outline-success" title="Modifier">
                        <i class="fas fa-edit"></i>
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
            <td colspan="6" class="text-center p-4">
                <div class="py-5">
                    <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                    <p>Aucun membre du personnel trouvé</p>
                    <a href="addStaff.php" class="btn btn-primary mt-2">
                        <i class="fas fa-plus-circle me-2"></i>Ajouter un membre du personnel
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
function confirmDelete(staffId) {
    if (confirm("Êtes-vous sûr de vouloir supprimer ce membre du personnel ?")) {
        window.location.href = "deleteStaff.php?id=" + staffId;
    }
}
</script>';

include('templates/layout.php');
?>

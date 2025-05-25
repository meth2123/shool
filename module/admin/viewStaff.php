<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];
$staff_id = $_GET['id'] ?? '';

if (empty($staff_id)) {
    header("Location: manageStaff.php?error=" . urlencode("ID du personnel non spécifié"));
    exit;
}

// Get staff details
$sql = "SELECT s.*, u.userid, u.password 
        FROM staff s 
        LEFT JOIN users u ON s.id = u.userid 
        WHERE s.id = ? AND s.created_by = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("ss", $staff_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();

if (!$staff) {
    header("Location: manageStaff.php?error=" . urlencode("Personnel non trouvé ou accès non autorisé"));
    exit;
}

$content = '
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Détails du Personnel</h2>
                <a href="manageStaff.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                </a>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Informations Personnelles</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">ID</label>
                                <p class="mb-0">' . htmlspecialchars($staff['id']) . '</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Nom</label>
                                <p class="mb-0">' . htmlspecialchars($staff['name']) . '</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Email</label>
                                <p class="mb-0">' . htmlspecialchars($staff['email']) . '</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Téléphone</label>
                                <p class="mb-0">' . htmlspecialchars($staff['phone']) . '</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Adresse</label>
                                <p class="mb-0">' . htmlspecialchars($staff['address']) . '</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Genre</label>
                                <p class="mb-0">' . ($staff['sex'] == 'female' ? 
                                    '<span class="badge bg-pink rounded-pill">Femme</span>' : 
                                    '<span class="badge bg-primary rounded-pill">Homme</span>') . '</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Date de naissance</label>
                                <p class="mb-0">' . htmlspecialchars($staff['dob']) . '</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Date d\'embauche</label>
                                <p class="mb-0">' . htmlspecialchars($staff['hiredate']) . '</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Salaire</label>
                                <p class="mb-0">' . htmlspecialchars($staff['salary']) . ' €</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white d-flex justify-content-end gap-2">
                    <a href="updateStaff.php?id=' . htmlspecialchars($staff_id) . '" 
                       class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Modifier
                    </a>
                    <button onclick="confirmDelete(\'' . htmlspecialchars($staff_id) . '\')" 
                            class="btn btn-danger">
                        <i class="fas fa-trash-alt me-2"></i>Supprimer
                    </button>
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

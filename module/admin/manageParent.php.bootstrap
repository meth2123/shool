<?php
include_once('main.php');
require_once('../../db/config.php');

// Get admin ID for filtering
$admin_id = $_SESSION['login_id'];

// Initialize database connection
$conn = getDbConnection();

// Get admin name
$sql = "SELECT name FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$login_session = $loged_user_name = $admin['name'];

if(!isset($login_session)){
    header("Location:../../");
    exit;
}

// Close database connection
$stmt->close();
$conn->close();

// Contenu de la page
$content = '
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h4 mb-4">
                <i class="fas fa-users me-2 text-primary"></i>
                Gestion des Parents
            </h2>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <a href="addParent.php" class="card h-100 text-decoration-none border-0 shadow-sm hover-card">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-user-plus fa-3x text-primary"></i>
                            </div>
                            <h3 class="h5 card-title">Ajouter un Parent</h3>
                            <p class="card-text text-muted">Créer un nouveau compte parent</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-lg-3">
                    <a href="viewParent.php" class="card h-100 text-decoration-none border-0 shadow-sm hover-card">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-list fa-3x text-success"></i>
                            </div>
                            <h3 class="h5 card-title">Liste des Parents</h3>
                            <p class="card-text text-muted">Voir tous les parents</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-lg-3">
                    <a href="updateParent.php" class="card h-100 text-decoration-none border-0 shadow-sm hover-card">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-edit fa-3x text-warning"></i>
                            </div>
                            <h3 class="h5 card-title">Modifier un Parent</h3>
                            <p class="card-text text-muted">Mettre à jour les informations</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-lg-3">
                    <a href="deleteParent.php" class="card h-100 text-decoration-none border-0 shadow-sm hover-card">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-trash fa-3x text-danger"></i>
                            </div>
                            <h3 class="h5 card-title">Supprimer un Parent</h3>
                            <p class="card-text text-muted">Supprimer un compte parent</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Raccourcis supplémentaires -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h3 class="h5 mb-0">Actions rapides</h3>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <a href="assignStudents.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-user-graduate me-2"></i>Assigner des Étudiants
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="manageStudent.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-users me-2"></i>Gestion des Étudiants
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="index.php" class="btn btn-outline-dark w-100">
                                <i class="fas fa-home me-2"></i>Tableau de bord
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .hover-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
</style>
';

// Inclure le template layout
include('templates/layout.php');
?>

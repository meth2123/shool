<?php
include_once('main.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];
$result = getDataByAdmin($link, 'students', $admin_id);
$student_count = countDataByAdmin($link, 'students', $admin_id);

$content = <<<HTML
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Gestion des Étudiants</h1>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Carte Ajouter un étudiant -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="fas fa-user-plus text-primary fa-2x"></i>
                        </div>
                    </div>
                    <h5 class="card-title">Ajouter un étudiant</h5>
                    <p class="card-text text-muted mb-4">Créer un nouveau profil étudiant</p>
                    <a href="addStudent.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Ajouter
                    </a>
                </div>
            </div>
        </div>

        <!-- Carte Voir les étudiants -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="fas fa-users text-success fa-2x"></i>
                        </div>
                    </div>
                    <h5 class="card-title">Voir les étudiants</h5>
                    <p class="card-text text-muted mb-4">Liste complète des étudiants</p>
                    <a href="viewStudent.php" class="btn btn-success">
                        <i class="fas fa-eye me-2"></i>Voir
                    </a>
                </div>
            </div>
        </div>

        <!-- Carte Modifier un étudiant -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="fas fa-user-edit text-warning fa-2x"></i>
                        </div>
                    </div>
                    <h5 class="card-title">Modifier un étudiant</h5>
                    <p class="card-text text-muted mb-4">Mettre à jour les informations</p>
                    <a href="updateStudent.php" class="btn btn-warning text-white">
                        <i class="fas fa-edit me-2"></i>Modifier
                    </a>
                </div>
            </div>
        </div>

        <!-- Carte Supprimer un étudiant -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                            <i class="fas fa-user-minus text-danger fa-2x"></i>
                        </div>
                    </div>
                    <h5 class="card-title">Supprimer un étudiant</h5>
                    <p class="card-text text-muted mb-4">Retirer un étudiant du système</p>
                    <a href="deleteStudent.php" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-2"></i>Supprimer
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Statistiques des étudiants</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-primary bg-opacity-10 border-0">
                                <div class="card-body">
                                    <h6 class="text-primary">Total des étudiants</h6>
                                    <h2 class="fw-bold text-primary">{$student_count}</h2>
                                </div>
                            </div>
                        </div>
                        <!-- Ajoutez d'autres statistiques si nécessaire -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-shadow:hover {
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    transition: box-shadow 0.3s ease-in-out;
}
</style>
HTML;

include('templates/layout.php');
?>

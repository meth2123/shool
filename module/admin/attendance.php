<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

$check = $_SESSION['login_id'];
$stmt = $link->prepare("SELECT name FROM admin WHERE id = ?");
$stmt->bind_param("s", $check);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$login_session = $loged_user_name = $row['name'] ?? '';

if(!isset($login_session)){
    header("Location:../../");
    exit;
}

$content = '
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="mb-4">
                <h2>Gestion des Présences</h2>
            </div>

            <div class="row g-4 mb-4">
                <!-- Présence des Enseignants -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Enseignants</h5>
                            <a href="teacherAttendance.php" class="btn btn-primary d-block">
                                <i class="fas fa-user-check me-2"></i>Gérer les présences
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Présence du Personnel -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Personnel</h5>
                            <a href="staffAttendance.php" class="btn btn-success d-block">
                                <i class="fas fa-clipboard-check me-2"></i>Gérer les présences
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Voir les Présences -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Historique des Présences</h5>
                    <a href="viewAttendance.php" class="btn btn-secondary d-block">
                        <i class="fas fa-history me-2"></i>Consulter l\'historique
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>';

include('templates/layout.php');
?>

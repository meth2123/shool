<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Initialiser le contenu pour le template
ob_start();

// Récupérer l'ID de l'admin connecté
$admin_id = $_SESSION['login_id'];

// Debug
error_log("Admin ID pour les statistiques : " . $admin_id);

// Calculer les statistiques
try {
    // 1. Examens à venir
    $stmt = $link->prepare("SELECT COUNT(*) as count 
                           FROM examschedule 
                           WHERE examdate > CURDATE() 
                           AND created_by = ?");
    $stmt->bind_param("s", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $upcoming_exams = $result->fetch_assoc()['count'];
    error_log("Examens à venir : " . $upcoming_exams);

    // 2. Examens en cours (aujourd'hui)
    $stmt = $link->prepare("SELECT COUNT(*) as count 
                           FROM examschedule 
                           WHERE DATE(examdate) = CURDATE() 
                           AND created_by = ?");
    $stmt->bind_param("s", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_exams = $result->fetch_assoc()['count'];
    error_log("Examens en cours : " . $current_exams);

} catch (Exception $e) {
    error_log("Erreur lors du calcul des statistiques : " . $e->getMessage());
    // En cas d'erreur, initialiser les variables à 0
    $upcoming_exams = 0;
    $current_exams = 0;
}

?>

<div class="container py-4">
    <!-- En-tête de la page -->
    <div class="mb-4">
        <h2 class="mb-2">Gestion des Examens</h2>
        <p class="text-muted">Gérez les plannings et les horaires des examens</p>
    </div>

    <!-- Grille des actions principales -->
    <div class="row g-4 mb-4">
        <!-- Carte Créer -->
        <div class="col-md-4">
            <a href="createExamSchedule.php" class="text-decoration-none">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-primary mb-3">
                            <i class="fas fa-plus-circle fa-3x"></i>
                        </div>
                        <h5 class="card-title">Créer un Planning</h5>
                        <p class="card-text text-muted">Planifiez de nouveaux examens et définissez leurs horaires</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Carte Consulter -->
        <div class="col-md-4">
            <a href="viewExamSchedule.php" class="text-decoration-none">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-success mb-3">
                            <i class="fas fa-calendar-alt fa-3x"></i>
                        </div>
                        <h5 class="card-title">Consulter les Plannings</h5>
                        <p class="card-text text-muted">Visualisez tous les examens planifiés</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Carte Modifier -->
        <div class="col-md-4">
            <a href="updateExamSchedule.php" class="text-decoration-none">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-warning mb-3">
                            <i class="fas fa-edit fa-3x"></i>
                        </div>
                        <h5 class="card-title">Modifier un Planning</h5>
                        <p class="card-text text-muted">Mettez à jour les informations des examens existants</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Section des statistiques -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Aperçu des Examens</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <div class="d-flex align-items-center">
                            <div class="text-primary me-3">
                                <i class="fas fa-calendar-check fa-2x"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-0 small">Examens à venir</p>
                                <h4 class="mb-0">
                                    <?php echo $upcoming_exams; ?>
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <div class="d-flex align-items-center">
                            <div class="text-success me-3">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-0 small">En cours</p>
                                <h4 class="mb-0">
                                    <?php echo $current_exams; ?>
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include('templates/layout.php');
?>

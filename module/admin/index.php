<?php
include_once('main.php');
include_once('includes/dashboard_stats.php');

$admin_id = $_SESSION['login_id'];
$stats = getDashboardStats($link, $admin_id);

// Récupérer les statistiques du personnel (staff)
$staff_count = 0;
try {
    $stmt = $link->prepare("SELECT COUNT(*) as count FROM staff WHERE created_by = ?");
    if ($stmt) {
        $stmt->bind_param("s", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $staff_count = $row['count'];
        }
    }
} catch (Exception $e) {
    // Ignorer les erreurs silencieusement
}

// Récupérer les statistiques des parents
$parents_count = 0;
try {
    $stmt = $link->prepare("SELECT COUNT(*) as count FROM parents WHERE created_by = ?");
    if ($stmt) {
        $stmt->bind_param("s", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $parents_count = $row['count'];
        }
    }
} catch (Exception $e) {
    // Ignorer les erreurs silencieusement
}

// Récupérer les notifications récentes
$notifications_count = 0;
$recent_notifications = [];

try {
    // Vérifier si la table notifications existe
    $check_table = $link->query("SHOW TABLES LIKE 'notifications'");
    if ($check_table && $check_table->num_rows > 0) {
        // Compter les notifications non lues
        $stmt = $link->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE created_by = ? AND is_read = 0
        ");
        $stmt->bind_param("s", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $notifications_count = $row['count'];
        }
        
        // Récupérer les 5 dernières notifications
        $stmt = $link->prepare("
            SELECT n.*, 
                   CASE 
                       WHEN n.user_type = 'admin' THEN a.name
                       WHEN n.user_type = 'teacher' THEN t.name
                       WHEN n.user_type = 'student' THEN s.name
                   END as user_name
            FROM notifications n
            LEFT JOIN admin a ON n.user_type = 'admin' AND n.user_id = a.id
            LEFT JOIN teachers t ON n.user_type = 'teacher' AND n.user_id = t.id
            LEFT JOIN students s ON n.user_type = 'student' AND n.user_id = s.id
            WHERE n.created_by = ?
            ORDER BY n.created_at DESC
            LIMIT 5
        ");
        $stmt->bind_param("s", $admin_id);
        $stmt->execute();
        $recent_notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // Ignorer les erreurs silencieusement
}

$content = <<<HTML
<div class="row g-4 mb-4">
    <!-- Statistiques des étudiants -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Étudiants</h5>
                    <span class="badge bg-primary rounded-pill">Total</span>
                </div>
                <p class="display-5 fw-bold mb-3">{$stats['students']}</p>
                <a href="./manageStudent.php" class="btn btn-link text-primary p-0">Gérer les étudiants →</a>
            </div>
        </div>
    </div>

    <!-- Statistiques des enseignants -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Enseignants</h5>
                    <span class="badge bg-success rounded-pill">Total</span>
                </div>
                <p class="display-5 fw-bold mb-3">{$stats['teachers']}</p>
                <a href="./manageTeacher.php" class="btn btn-link text-primary p-0">Gérer les enseignants →</a>
            </div>
        </div>
    </div>

    <!-- Statistiques des parents -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Parents</h5>
                    <span class="badge bg-info rounded-pill">Total</span>
                </div>
                <p class="display-5 fw-bold mb-3">{$parents_count}</p>
                <a href="./manageParent.php" class="btn btn-link text-primary p-0">Gérer les parents →</a>
            </div>
        </div>
    </div>

    <!-- Statistiques du personnel -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Personnel</h5>
                    <span class="badge bg-warning rounded-pill">Total</span>
                </div>
                <p class="display-5 fw-bold mb-3">{$staff_count}</p>
                <a href="./manageStaff.php" class="btn btn-link text-primary p-0">Gérer le personnel →</a>
            </div>
        </div>
    </div>
</div>

<!-- Deuxième rangée de statistiques -->
<div class="row g-4 mb-4">
    <!-- Statistiques des cours -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Cours</h5>
                    <span class="badge bg-info rounded-pill">Total</span>
                </div>
                <p class="display-5 fw-bold mb-3">{$stats['courses']}</p>
                <a href="./course.php" class="btn btn-link text-primary p-0">Gérer les cours →</a>
            </div>
        </div>
    </div>

    <!-- Statistiques des classes -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Classes</h5>
                    <span class="badge bg-warning rounded-pill">Total</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="display-5 fw-bold mb-0">{$stats['classes']}</p>
                        <small class="text-muted">Total des classes</small>
                    </div>
                    <div class="text-end">
                        <p class="h3 fw-bold text-primary mb-0">{$stats['my_classes']}</p>
                        <small class="text-muted">Mes classes</small>
                    </div>
                </div>
                <a href="./manageClass.php" class="btn btn-link text-primary p-0 mt-3">Gérer les classes →</a>
            </div>
        </div>
    </div>

    <!-- Statistiques des notifications -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Notifications</h5>
                    <span class="badge bg-primary rounded-pill">Non lues</span>
                </div>
                <p class="display-5 fw-bold mb-3">{$notifications_count}</p>
                <a href="./manage_notifications.php" class="btn btn-link text-primary p-0">Gérer les notifications →</a>
            </div>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="mb-5">
    <h4 class="fw-bold mb-4">Actions rapides</h4>
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <a href="./addStudent.php" class="card h-100 border-0 shadow-sm text-decoration-none text-dark">
                <div class="card-body">
                    <h5 class="card-title">Nouvel étudiant</h5>
                    <p class="card-text text-muted small">Ajouter un étudiant</p>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="./addTeacher.php" class="card h-100 border-0 shadow-sm text-decoration-none text-dark">
                <div class="card-body">
                    <h5 class="card-title">Nouvel enseignant</h5>
                    <p class="card-text text-muted small">Ajouter un enseignant</p>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="./addParent.php" class="card h-100 border-0 shadow-sm text-decoration-none text-dark">
                <div class="card-body">
                    <h5 class="card-title">Nouveau parent</h5>
                    <p class="card-text text-muted small">Ajouter un parent</p>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="./addStaff.php" class="card h-100 border-0 shadow-sm text-decoration-none text-dark">
                <div class="card-body">
                    <h5 class="card-title">Nouveau personnel</h5>
                    <p class="card-text text-muted small">Ajouter un membre du staff</p>
                </div>
            </a>
        </div>
    </div>
    
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <a href="./addClass.php" class="card h-100 border-0 shadow-sm text-decoration-none text-dark">
                <div class="card-body">
                    <h5 class="card-title">Nouvelle classe</h5>
                    <p class="card-text text-muted small">Ajouter une classe</p>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="./addCourse.php" class="card h-100 border-0 shadow-sm text-decoration-none text-dark">
                <div class="card-body">
                    <h5 class="card-title">Nouveau cours</h5>
                    <p class="card-text text-muted small">Ajouter un cours</p>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="./addCourse.php" class="card h-100 border-0 shadow-sm text-decoration-none text-dark">
                <div class="card-body">
                    <h5 class="card-title">Nouvelle matière</h5>
                    <p class="card-text text-muted small">Ajouter une matière</p>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="manage_notifications.php?action=new" class="card h-100 border-0 shadow-sm text-decoration-none text-dark">
                <div class="card-body">
                    <h5 class="card-title">Notification</h5>
                    <p class="card-text text-muted small">Envoyer une notification</p>
                </div>
            </a>
        </div>
    </div>
    
    <!-- Gestion des notes et bulletins -->
    <div class="row g-3">
        <div class="col-6 col-md-3">
            <a href="./assignStudents.php" class="card h-100 border-0 shadow-sm text-decoration-none text-dark border-start border-5 border-primary">
                <div class="card-body">
                    <h5 class="card-title">Assigner des élèves</h5>
                    <p class="card-text text-muted small">Gérer les élèves par cours</p>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="./assignClassTeacher.php" class="card h-100 border-0 shadow-sm text-decoration-none text-dark border-start border-5 border-info">
                <div class="card-body">
                    <h5 class="card-title">Assigner une classe</h5>
                    <p class="card-text text-muted small">Assigner une classe à un prof</p>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="./manageGrades.php" class="card h-100 border-0 shadow-sm text-decoration-none text-dark border-start border-5 border-success">
                <div class="card-body">
                    <h5 class="card-title">Gestion des notes</h5>
                    <p class="card-text text-muted small">Valider les notes soumises</p>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="./manageBulletins.php" class="card h-100 border-0 shadow-sm text-decoration-none text-dark border-start border-5 border-warning">
                <div class="card-body">
                    <h5 class="card-title">Bulletins</h5>
                    <p class="card-text text-muted small">Télécharger les bulletins</p>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- Gestion principale -->
<div class="mb-4">
    <h4 class="fw-bold mb-4">Gestion principale</h4>
    <div class="row g-4">
        <!-- Gestion des étudiants -->
        <div class="col-12 col-md-6 col-lg-4">
            <a href="./manageStudent.php" class="card border-0 shadow-sm text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-graduate fs-1 text-primary"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="card-title">Gestion des Étudiants</h5>
                            <p class="card-text text-muted small">Gérer tous les étudiants</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Gestion des enseignants -->
        <div class="col-12 col-md-6 col-lg-4">
            <a href="./manageTeacher.php" class="card border-0 shadow-sm text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chalkboard-teacher fs-1 text-success"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="card-title">Gestion des Enseignants</h5>
                            <p class="card-text text-muted small">Gérer tous les enseignants</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Gestion du personnel -->
        <div class="col-12 col-md-6 col-lg-4">
            <a href="./manageStaff.php" class="card border-0 shadow-sm text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-tie fs-1 text-warning"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="card-title">Gestion du Personnel</h5>
                            <p class="card-text text-muted small">Gérer tout le personnel</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Gestion des parents -->
        <div class="col-12 col-md-6 col-lg-4">
            <a href="./manageParent.php" class="card border-0 shadow-sm text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-friends fs-1 text-info"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="card-title">Gestion des Parents</h5>
                            <p class="card-text text-muted small">Gérer tous les parents</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Gestion des classes -->
        <div class="col-12 col-md-6 col-lg-4">
            <a href="./manageClass.php" class="card border-0 shadow-sm text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chalkboard fs-1 text-primary"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="card-title">Gestion des Classes</h5>
                            <p class="card-text text-muted small">Gérer toutes les classes</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Gestion des cours -->
        <div class="col-12 col-md-6 col-lg-4">
            <a href="./course.php" class="card border-0 shadow-sm text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-book fs-1 text-danger"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="card-title">Gestion des Cours</h5>
                            <p class="card-text text-muted small">Gérer tous les cours</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- Rapports et outils -->
<div class="mb-4">
    <h4 class="fw-bold mb-4">Rapports et Outils</h4>
    <div class="row g-4">
        <!-- Rapports de performance -->
        <div class="col-12 col-md-6 col-lg-4">
            <a href="./report.php" class="card border-0 shadow-sm text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chart-bar fs-1 text-primary"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="card-title">Rapports de Performance</h5>
                            <p class="card-text text-muted small">Analyser les performances des élèves</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Gestion des bulletins -->
        <div class="col-12 col-md-6 col-lg-4">
            <a href="./manageBulletins.php" class="card border-0 shadow-sm text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-file-pdf fs-1 text-danger"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="card-title">Bulletins Scolaires</h5>
                            <p class="card-text text-muted small">Générer et télécharger les bulletins</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Gestion des notifications -->
        <div class="col-12 col-md-6 col-lg-4">
            <a href="./manage_notifications.php" class="card border-0 shadow-sm text-decoration-none text-dark h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-bell fs-1 text-warning"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="card-title">Notifications</h5>
                            <p class="card-text text-muted small">Gérer les notifications système</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
HTML;

include('templates/layout.php');
?>

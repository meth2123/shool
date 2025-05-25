<?php
// Récupérer les informations de l'enseignant
$teacher_info = db_fetch_row(
    "SELECT * FROM teachers WHERE id = ?",
    [$check],
    's'
);

if (!$teacher_info) {
    header("Location: ../../?error=teacher_not_found");
    exit();
}

// Initialiser le composant de notification
require_once __DIR__ . '/../../../components/NotificationBell.php';
$notificationBell = new NotificationBell($link, $check, 'teacher');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Enseignant - Système de Gestion Scolaire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .nav-link.active {
            background-color: rgba(13, 110, 253, 0.1);
            border-radius: 0.25rem;
        }
        .btn-menu {
            margin: 5px;
            min-width: 120px;
        }
        .notification-bell {
            position: relative;
            cursor: pointer;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.7rem;
        }
        .sidebar {
            min-height: calc(100vh - 56px - 72px);
        }
        @media (max-width: 991.98px) {
            .sidebar {
                min-height: auto;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="../../source/logo.jpg" class="rounded-circle me-2" width="40" height="40" alt="Logo"/>
                <span class="d-none d-md-inline">Système de Gestion Scolaire</span>
                <span class="d-inline d-md-none">SGS</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-3">
                        <span class="text-light">Bonjour, <?php echo htmlspecialchars($teacher_info['name'] ?? ''); ?></span>
                    </li>
                    <li class="nav-item me-2 dropdown">
                        <a href="#" class="btn btn-primary btn-sm dropdown-toggle" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell me-1"></i>
                            Notifications
                            <?php 
                            $unreadCount = $notificationBell->getUnreadCount();
                            if ($unreadCount > 0): 
                            ?>
                            <span class="badge bg-danger ms-1"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end p-0" style="width: 350px; max-height: 400px; overflow-y: auto;" aria-labelledby="notificationDropdown">
                            <div class="p-2 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Notifications</h6>
                                <?php if ($unreadCount > 0): ?>
                                <a href="notifications.php?mark_all_read=1" class="btn btn-sm btn-link text-decoration-none">Tout marquer comme lu</a>
                                <?php endif; ?>
                            </div>
                            <?php 
                            $notifications = $notificationBell->getNotifications(5);
                            if (empty($notifications)): 
                            ?>
                            <div class="p-3 text-center text-muted">
                                <p class="mb-0">Aucune notification</p>
                            </div>
                            <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item list-group-item-action p-2 <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($notification['title'] ?? ''); ?></h6>
                                        <small class="text-muted">
                                            <?php
                                            $timestamp = strtotime($notification['created_at']);
                                            $now = time();
                                            $diff = $now - $timestamp;
                                            
                                            if ($diff < 60) {
                                                echo 'À l\'instant';
                                            } elseif ($diff < 3600) {
                                                $minutes = floor($diff / 60);
                                                echo 'Il y a ' . $minutes . ' min';
                                            } elseif ($diff < 86400) {
                                                $hours = floor($diff / 3600);
                                                echo 'Il y a ' . $hours . ' h';
                                            } else {
                                                echo date('d/m/Y', $timestamp);
                                            }
                                            ?>
                                        </small>
                                    </div>
                                    <p class="mb-1 small"><?php echo htmlspecialchars($notification['message'] ?? ''); ?></p>
                                    <?php if (!$notification['is_read']): ?>
                                    <div class="d-flex justify-content-end">
                                        <form method="POST" action="notifications.php" class="d-inline">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-link p-0 text-decoration-none">Marquer comme lu</button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <!-- Le lien "Voir toutes les notifications" a été supprimé -->
                            <?php endif; ?>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="btn btn-danger btn-sm">
                            <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-3">
        <div class="row">
            <!-- Menu de navigation (sidebar) -->
            <div class="col-lg-3 mb-4">
                <div class="sidebar bg-white shadow-sm rounded p-3">
                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                        <img src="../../source/teacher.png" class="rounded-circle me-2" width="50" height="50" alt="Teacher">
                        <div>
                            <h5 class="mb-0"><?php echo htmlspecialchars($teacher_info['name']); ?></h5>
                            <small class="text-muted">Enseignant</small>
                        </div>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="index.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home me-2"></i>Tableau de bord
                        </a>
                        <a href="updateTeacher.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'updateTeacher.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-edit me-2"></i>Modifier profil
                        </a>
                        <a href="viewProfile.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'viewProfile.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user me-2"></i>Voir profil
                        </a>
                        <a href="courses.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'courses.php' ? 'active' : ''; ?>">
                            <i class="fas fa-book me-2"></i>Cours
                        </a>
                        <a href="course.php?course_id=5" class="list-group-item list-group-item-action <?php echo (basename($_SERVER['PHP_SELF']) === 'course.php' && isset($_GET['course_id']) && $_GET['course_id'] == '5') ? 'active' : ''; ?>">
                            <i class="fas fa-graduation-cap me-2"></i>Notes
                        </a>
                        <a href="attendance.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'attendance.php' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-check me-2"></i>Présences
                        </a>
                        <a href="exam.php" class="list-group-item list-group-item-action <?php echo (basename($_SERVER['PHP_SELF']) === 'exam.php' || basename($_SERVER['PHP_SELF']) === 'view_exam.php') ? 'active' : ''; ?>">
                            <i class="fas fa-clock me-2"></i>Examens
                        </a>
                        <a href="salary.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'salary.php' ? 'active' : ''; ?>">
                            <i class="fas fa-money-bill-wave me-2"></i>Salaire
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="col-lg-9">
                <div class="content bg-white shadow-sm rounded p-4">
                    <?php if (isset($content)) { echo $content; } ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white shadow-sm mt-4 py-3">
        <div class="container text-center">
            <p class="text-muted mb-0">
                &copy; <?php echo date('Y'); ?> Système de Gestion Scolaire. Tous droits réservés.
            </p>
        </div>
    </footer>

    <script src="../../JS/jquery-1.12.3.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
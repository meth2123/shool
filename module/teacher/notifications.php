<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../service/NotificationService.php';
require_once __DIR__ . '/../../service/AuthService.php';
require_once __DIR__ . '/../../service/mysqlcon.php';

// Vérifier si l'utilisateur est connecté et est un enseignant
$authService = new AuthService($db);
if (!isset($_SESSION['login_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: /gestion/login.php');
    exit;
}

// Récupérer l'ID de l'enseignant
$check = $_SESSION['login_id'];

// Forcer le type à teacher
$user_type = 'teacher';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Récupérer les notifications
$notificationService = new NotificationService($link, $check, $user_type);
$notifications = $notificationService->getAll($per_page, $offset);
$total_notifications = $notificationService->countAll();
$total_pages = ceil($total_notifications / $per_page);

// Marquer toutes les notifications comme lues si demandé
if (isset($_GET['mark_all_read']) && $_GET['mark_all_read'] === '1') {
    if ($notificationService->markAllAsRead()) {
        $_SESSION['success'] = "Toutes les notifications ont été marquées comme lues";
    } else {
        $_SESSION['error'] = "Erreur lors du marquage des notifications";
    }
}

// Marquer une notification spécifique comme lue si demandé
if (isset($_POST['notification_id'])) {
    $notification_id = $_POST['notification_id'];
    if ($notificationService->markAsRead($notification_id)) {
        $_SESSION['success'] = "La notification a été marquée comme lue.";
    } else {
        $_SESSION['error'] = "Une erreur est survenue lors du marquage de la notification.";
    }
}

// Inclure le template de layout
$page_title = "Mes Notifications";
$content = '';
?>

<?php ob_start(); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Mes Notifications</h1>
        <?php if ($total_notifications > 0): ?>
        <a href="?mark_all_read=1" class="btn btn-primary btn-sm">
            <i class="fas fa-check-double me-1"></i>Tout marquer comme lu
        </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (empty($notifications)): ?>
    <div class="card mb-4">
        <div class="card-body text-center py-5">
            <p class="text-muted mb-0">Aucune notification</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card mb-4">
        <div class="list-group list-group-flush">
            <?php foreach ($notifications as $notification): ?>
            <div class="list-group-item <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="d-flex align-items-center">
                            <h5 class="mb-1"><?php echo htmlspecialchars($notification['title'] ?? ''); ?></h5>
                            <?php if (!$notification['is_read']): ?>
                            <span class="badge bg-primary ms-2">Nouveau</span>
                            <?php endif; ?>
                        </div>
                        <p class="mb-1"><?php echo htmlspecialchars($notification['message'] ?? ''); ?></p>
                        <div class="d-flex align-items-center text-muted small">
                            <span>
                                <?php
                                $timestamp = strtotime($notification['created_at']);
                                $now = time();
                                $diff = $now - $timestamp;
                                
                                if ($diff < 60) {
                                    echo 'À l\'instant';
                                } elseif ($diff < 3600) {
                                    $minutes = floor($diff / 60);
                                    echo 'Il y a ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
                                } elseif ($diff < 86400) {
                                    $hours = floor($diff / 3600);
                                    echo 'Il y a ' . $hours . ' heure' . ($hours > 1 ? 's' : '');
                                } elseif ($diff < 604800) {
                                    $days = floor($diff / 86400);
                                    echo 'Il y a ' . $days . ' jour' . ($days > 1 ? 's' : '');
                                } else {
                                    echo date('d/m/Y H:i', $timestamp);
                                }
                                ?>
                            </span>
                            <?php if (!empty($notification['type'])): ?>
                            <span class="ms-2 badge 
                                <?php
                                switch ($notification['type']) {
                                    case 'success':
                                        echo 'bg-success';
                                        break;
                                    case 'warning':
                                        echo 'bg-warning';
                                        break;
                                    case 'error':
                                        echo 'bg-danger';
                                        break;
                                    default:
                                        echo 'bg-info';
                                }
                                ?>">
                                <?php echo ucfirst($notification['type']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($notification['link'])): ?>
                        <div class="mt-2">
                            <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="btn btn-sm btn-link p-0">
                                Voir plus <i class="fas fa-chevron-right ms-1"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!$notification['is_read']): ?>
                    <div>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($total_pages > 1): ?>
    <nav aria-label="Pagination des notifications">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Précédent">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
            <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">&laquo;</span>
            </li>
            <?php endif; ?>

            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);

            if ($start_page > 1) {
                echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                if ($start_page > 2) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            for ($i = $start_page; $i <= $end_page; $i++) {
                $active_class = $i === $page ? 'active' : '';
                echo '<li class="page-item ' . $active_class . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
            }

            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
            }
            ?>

            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Suivant">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
            <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">&raquo;</span>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/templates/layout.php'; 
?>
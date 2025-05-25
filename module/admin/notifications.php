<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../service/NotificationService.php';
require_once __DIR__ . '/../../service/AuthService.php';

// Vérifier si l'utilisateur est connecté
$authService = new AuthService($db);
if (!$authService->isLoggedIn()) {
    header('Location: /gestion/login.php');
    exit;
}

// Récupérer le type d'utilisateur
$user_type = $_GET['type'] ?? null;
if (!in_array($user_type, ['admin', 'teacher', 'student'])) {
    header('Location: /gestion/dashboard.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Récupérer les notifications
$notificationService = new NotificationService($db, $_SESSION['user_id'], $user_type);
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
    header('Location: notifications.php?type=' . $user_type);
    exit;
}

// Inclure l'en-tête
$page_title = "Notifications";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
        <?php if ($total_notifications > 0): ?>
        <a href="?type=<?php echo htmlspecialchars($user_type); ?>&mark_all_read=1" 
           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
            Tout marquer comme lu
        </a>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
    <div class="bg-white rounded-lg shadow p-6 text-center">
        <p class="text-gray-500">Aucune notification</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <ul class="divide-y divide-gray-200">
            <?php foreach ($notifications as $notification): ?>
            <li class="hover:bg-gray-50 <?php echo $notification['is_read'] ? '' : 'bg-blue-50'; ?>">
                <div class="px-4 py-4 sm:px-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center">
                                <p class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($notification['title']); ?>
                                </p>
                                <?php if (!$notification['is_read']): ?>
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    Nouveau
                                </span>
                                <?php endif; ?>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                <?php echo htmlspecialchars($notification['message']); ?>
                            </p>
                            <div class="mt-2 flex items-center text-xs text-gray-500">
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
                                <?php if ($notification['type']): ?>
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php
                                    switch ($notification['type']) {
                                        case 'success':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'warning':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'error':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                        default:
                                            echo 'bg-blue-100 text-blue-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($notification['type']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($notification['link']): ?>
                            <div class="mt-2">
                                <a href="<?php echo htmlspecialchars($notification['link']); ?>" 
                                   class="text-sm text-blue-600 hover:text-blue-800">
                                    Voir plus <i class="fas fa-chevron-right ml-1"></i>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!$notification['is_read']): ?>
                        <div class="ml-4 flex-shrink-0">
                            <form action="mark_read.php" method="POST" class="inline">
                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                <input type="hidden" name="user_type" value="<?php echo htmlspecialchars($user_type); ?>">
                                <button type="submit" class="text-gray-400 hover:text-gray-500">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="mt-6 flex justify-center">
        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <?php if ($page > 1): ?>
            <a href="?type=<?php echo htmlspecialchars($user_type); ?>&page=<?php echo $page - 1; ?>" 
               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                <span class="sr-only">Précédent</span>
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>

            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);

            if ($start_page > 1) {
                echo '<a href="?type=' . htmlspecialchars($user_type) . '&page=1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                if ($start_page > 2) {
                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                }
            }

            for ($i = $start_page; $i <= $end_page; $i++) {
                $active_class = $i === $page ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white text-gray-700 hover:bg-gray-50';
                echo '<a href="?type=' . htmlspecialchars($user_type) . '&page=' . $i . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium ' . $active_class . '">' . $i . '</a>';
            }

            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                }
                echo '<a href="?type=' . htmlspecialchars($user_type) . '&page=' . $total_pages . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
            }
            ?>

            <?php if ($page < $total_pages): ?>
            <a href="?type=<?php echo htmlspecialchars($user_type); ?>&page=<?php echo $page + 1; ?>" 
               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                <span class="sr-only">Suivant</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 
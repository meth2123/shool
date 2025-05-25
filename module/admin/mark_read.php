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

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Récupérer les données
$notification_id = $_POST['notification_id'] ?? null;
$user_type = $_POST['user_type'] ?? null;

if (!$notification_id || !$user_type) {
    $_SESSION['error'] = "Données manquantes";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Vérifier que l'utilisateur a le droit de marquer cette notification
$notificationService = new NotificationService($db, $_SESSION['user_id'], $user_type);
$notification = $notificationService->getById($notification_id);

if (!$notification || $notification['user_id'] != $_SESSION['user_id']) {
    $_SESSION['error'] = "Vous n'avez pas le droit de modifier cette notification";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Marquer la notification comme lue
if ($notificationService->markAsRead($notification_id)) {
    $_SESSION['success'] = "Notification marquée comme lue";
} else {
    $_SESSION['error'] = "Erreur lors du marquage de la notification";
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit; 
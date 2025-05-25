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

// Récupérer le type d'utilisateur
$user_type = $_POST['user_type'] ?? null;

if (!$user_type) {
    $_SESSION['error'] = "Type d'utilisateur manquant";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Marquer toutes les notifications comme lues
$notificationService = new NotificationService($db, $_SESSION['user_id'], $user_type);
if ($notificationService->markAllAsRead()) {
    $_SESSION['success'] = "Toutes les notifications ont été marquées comme lues";
} else {
    $_SESSION['error'] = "Erreur lors du marquage des notifications";
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit; 
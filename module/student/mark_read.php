<?php
session_start();
require_once __DIR__ . '/../../service/mysqlcon.php';
require_once __DIR__ . '/../../service/NotificationService.php';

// Vérifier si l'utilisateur est connecté et est un étudiant
if (!isset($_SESSION['login_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: /gestion/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
    $notificationService = new NotificationService($link, $_SESSION['login_id'], 'student');
    
    if ($notificationService->markAsRead($_POST['notification_id'])) {
        $_SESSION['success'] = "Notification marquée comme lue";
    } else {
        $_SESSION['error'] = "Erreur lors du marquage de la notification";
    }
}

// Rediriger vers la page précédente
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
?> 
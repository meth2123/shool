<?php
session_start();
require_once __DIR__ . '/../../service/mysqlcon.php';
require_once __DIR__ . '/../../service/NotificationService.php';

// Vérifier si l'utilisateur est connecté et est un étudiant
if (!isset($_SESSION['login_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: /gestion/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notificationService = new NotificationService($link, $_SESSION['login_id'], 'student');
    
    if ($notificationService->markAllAsRead()) {
        $_SESSION['success'] = "Toutes les notifications ont été marquées comme lues";
    } else {
        $_SESSION['error'] = "Erreur lors du marquage des notifications";
    }
}

// Rediriger vers la page précédente
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
?> 
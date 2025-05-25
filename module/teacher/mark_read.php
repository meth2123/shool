<?php
session_start();
require_once __DIR__ . '/../../service/mysqlcon.php';
require_once __DIR__ . '/../../service/NotificationService.php';

// Vérifier si l'utilisateur est connecté et est un enseignant
if (!isset($_SESSION['login_id']) || $_SESSION['user_type'] !== 'teacher') {
    header("Location: ../../?error=unauthorized");
    exit();
}

// Vérifier si la requête est en POST et si l'ID de la notification est fourni
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
    try {
        // Initialiser le service de notification
        $notificationService = new NotificationService($link, $_SESSION['login_id'], 'teacher');
        
        // Marquer la notification comme lue
        if ($notificationService->markAsRead($_POST['notification_id'])) {
            $_SESSION['success'] = "La notification a été marquée comme lue.";
        } else {
            $_SESSION['error'] = "Une erreur est survenue lors du marquage de la notification.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Une erreur est survenue : " . $e->getMessage();
    }
}

// Rediriger vers la page précédente
header("Location: " . $_SERVER['HTTP_REFERER']);
exit(); 
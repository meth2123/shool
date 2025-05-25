<?php
session_start();
require_once __DIR__ . '/../../service/mysqlcon.php';
require_once __DIR__ . '/../../service/NotificationService.php';

// Vérifier si l'utilisateur est connecté et est un enseignant
if (!isset($_SESSION['login_id']) || $_SESSION['user_type'] !== 'teacher') {
    header("Location: ../../?error=unauthorized");
    exit();
}

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Initialiser le service de notification
        $notificationService = new NotificationService($link, $_SESSION['login_id'], 'teacher');
        
        // Marquer toutes les notifications comme lues
        if ($notificationService->markAllAsRead()) {
            $_SESSION['success'] = "Toutes les notifications ont été marquées comme lues.";
        } else {
            $_SESSION['error'] = "Une erreur est survenue lors du marquage des notifications.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Une erreur est survenue : " . $e->getMessage();
    }
}

// Rediriger vers la page précédente
header("Location: " . $_SERVER['HTTP_REFERER']);
exit(); 
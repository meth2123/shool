<?php
require_once __DIR__ . '/../service/mysqlcon.php';
require_once __DIR__ . '/../service/SubscriptionService.php';

try {
    // Initialiser le service d'abonnement
    $subscriptionService = new SubscriptionService($link);
    
    // Vérifier les abonnements qui expirent bientôt
    $subscriptionService->checkExpiringSubscriptions();
    
    // Gérer les abonnements expirés
    $subscriptionService->handleExpiredSubscriptions();
    
    error_log("Gestion des abonnements terminée avec succès");
    exit(0);
} catch (Exception $e) {
    error_log("Erreur lors de la gestion des abonnements : " . $e->getMessage());
    exit(1);
} 
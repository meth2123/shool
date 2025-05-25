<?php
/**
 * Fichier de vérification d'authentification centralisé
 * À inclure dans tous les fichiers qui nécessitent une vérification d'authentification
 */

// Vérifier si une session est déjà active avant de la démarrer
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['login_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // Si la requête est AJAX, renvoyer un message d'erreur JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Accès non autorisé']);
        exit;
    }
    
    // Sinon, afficher un message d'erreur HTML
    echo '<div class="alert alert-danger" role="alert">
            <strong>Erreur!</strong> Accès non autorisé.
          </div>';
    exit;
}

// Définir les variables couramment utilisées
$admin_id = $_SESSION['login_id'];
$login_session = '';

// Récupérer le nom de l'administrateur si nécessaire
if (!isset($login_session)) {
    require_once(__DIR__ . '/../../../../service/mysqlcon.php');
    $stmt = $link->prepare("SELECT name FROM admin WHERE id = ?");
    $stmt->bind_param("s", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $login_session = $row['name'] ?? 'Administrateur';
}

// Définir la variable $check pour la compatibilité avec les anciens scripts
$check = true;
?>

<?php
// Vérifier si une session est déjà active avant de la démarrer
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Détection automatique de l'environnement (WAMP ou Docker)
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/gestion/service/mysqlcon.php')) {
    // Environnement WAMP
    $root_path = $_SERVER['DOCUMENT_ROOT'] . '/gestion/';
} else {
    // Environnement Docker ou Render
    $root_path = $_SERVER['DOCUMENT_ROOT'] . '/';
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['login_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$admin_id = $_SESSION['login_id'];

// Inclure les fichiers nécessaires
require_once($root_path . 'service/mysqlcon.php');

// Récupérer les informations de l'administrateur
$stmt = $link->prepare("SELECT name FROM admin WHERE id = ?");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$login_session = $row['name'] ?? 'Administrateur';

// Vérifier si l'utilisateur a cliqué sur le bouton de déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../../login.php');
    exit;
}
?>

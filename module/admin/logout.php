<?php
include_once('../../service/mysqlcon.php');

// Vérifier si la session existe avant de la détruire
if (session_status() === PHP_SESSION_ACTIVE) {
    // Détruire toutes les données de session
    $_SESSION = array();
    
    // Détruire le cookie de session si il existe
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    // Détruire la session
    session_destroy();
}

// Fermer la connexion MySQLi si elle existe
if (isset($link) && $link instanceof mysqli) {
    $link->close();
}

// Rediriger vers la page de connexion
header("Location: ../../");
exit(); // Assurer que le script s'arrête après la redirection
?>

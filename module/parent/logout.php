<?php
// La session est déjà démarrée dans main.php, pas besoin de la redémarrer
include_once('main.php');

// Destruction de la session
$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

session_destroy();

// Redirection vers la page de connexion
header("Location: ../../?success=logout");
exit();
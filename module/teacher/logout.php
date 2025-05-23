<?php
// La session est déjà démarrée dans main.php
include_once('main.php');

// Destruction de la session
$_SESSION = array();

// Destruction du cookie de session si présent
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destruction de la session
session_destroy();

// Redirection vers la page d'accueil
header("Location: ../../?success=logout");
exit();
<?php
// Démarrer la session seulement si elle n'existe pas déjà
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$username = "root";
$password = "";
$db_name = "gestion";

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Créer la connexion avec mysqli
$link = new mysqli($host, $username, $password, $db_name);

// Vérifier la connexion
if ($link->connect_error) {
    error_log("Erreur de connexion à la base de données: " . $link->connect_error);
    die("La connexion a échoué: " . $link->connect_error);
}

// Définir le jeu de caractères
if (!$link->set_charset("utf8")) {
    error_log("Erreur lors de la définition du jeu de caractères utf8: " . $link->error);
}

// Désactiver le mode strict SQL
$link->query("SET sql_mode = ''");

error_log("Connexion à la base de données réussie");
?>

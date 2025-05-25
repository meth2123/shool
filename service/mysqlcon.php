<?php
// Démarrer la session seulement si elle n'existe pas déjà
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Charger la configuration de la base de données
require_once __DIR__ . '/db_config.php';

$host = $db_host;
$username = $db_user;
$password = $db_password;
$database_name = $db_name;

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fonction pour attendre que la base de données soit prête
function wait_for_db($host, $username, $password, $db_name, $max_attempts = 10) {
    $attempts = 0;
    $connected = false;
    
    while (!$connected && $attempts < $max_attempts) {
        error_log("Tentative de connexion à la base de données ($host) - tentative " . ($attempts + 1));
        try {
            $temp_link = @new mysqli($host, $username, $password, $db_name);
            if (!$temp_link->connect_error) {
                error_log("Connexion réussie à la base de données après $attempts tentatives");
                $temp_link->close();
                $connected = true;
            } else {
                error_log("Échec de la connexion: " . $temp_link->connect_error);
                $attempts++;
                sleep(3); // Attendre 3 secondes avant de réessayer
            }
        } catch (Exception $e) {
            error_log("Exception lors de la tentative de connexion: " . $e->getMessage());
            $attempts++;
            sleep(3);
        }
    }
    
    return $connected;
}

// Dans l'environnement Docker, attendre que la base de données soit prête
if (file_exists('/.dockerenv') || getenv('DB_HOST')) {
    wait_for_db($host, $username, $password, $database_name);
}

// Créer la connexion avec mysqli
if (!empty($database_name)) {
    $link = new mysqli($host, $username, $password, $database_name);
} else {
    // Se connecter sans spécifier de base de données
    $link = new mysqli($host, $username, $password);
}

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

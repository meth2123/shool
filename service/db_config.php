<?php
/**
 * Configuration de la base de données
 */

// Vérifier si nous sommes dans un environnement Docker/production
if (file_exists('/.dockerenv') || getenv('DB_HOST')) {
    // Paramètres pour l'environnement de production
    $db_host = getenv('DB_HOST') ? getenv('DB_HOST') : 'db'; // 'db' est souvent le nom du service dans Docker
    $db_user = getenv('DB_USER') ? getenv('DB_USER') : 'root';
    $db_password = getenv('DB_PASSWORD') ? getenv('DB_PASSWORD') : 'root_password';
    $db_name = getenv('DB_NAME') ? getenv('DB_NAME') : 'gestion';
} else {
    // Paramètres pour l'environnement local (WAMP)
    $db_host = 'localhost';
    $db_user = 'root';      // Utilisateur par défaut pour WAMP
    $db_password = '';      // Mot de passe par défaut pour WAMP
    $db_name = 'gestion';   // Nom de la base de données de l'application
}

// Charset et collation
$db_charset = 'utf8mb4';
$db_collation = 'utf8mb4_unicode_ci';
?>

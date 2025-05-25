<?php
/**
 * Configuration de la base de données
 */

// Vérifier si nous sommes dans un environnement de production (Docker, Render.com, etc.)
if (file_exists('/.dockerenv') || getenv('DB_HOST') || getenv('RENDER')) {
    // Paramètres pour l'environnement de production
    // Priorité aux variables d'environnement spécifiques à Render.com
    $db_host = getenv('RENDER_DATABASE_HOST') ?: getenv('DB_HOST') ?: 'db';
    $db_user = getenv('RENDER_DATABASE_USER') ?: getenv('DB_USER') ?: 'root';
    $db_password = getenv('RENDER_DATABASE_PASSWORD') ?: getenv('DB_PASSWORD') ?: 'root_password';
    $db_name = getenv('RENDER_DATABASE_NAME') ?: getenv('DB_NAME') ?: 'gestion';
    
    // Pour les bases de données externes comme Render PostgreSQL ou MySQL
    $db_port = getenv('RENDER_DATABASE_PORT') ?: getenv('DB_PORT') ?: '3306';
    $db_socket = getenv('RENDER_DATABASE_SOCKET') ?: getenv('DB_SOCKET') ?: '';
    
    // Journaliser les informations de connexion (sans le mot de passe)
    error_log("Environnement de production détecté. Connexion à la base de données: $db_host:$db_port, utilisateur: $db_user, base: $db_name");
} else {
    // Paramètres pour l'environnement local (WAMP)
    $db_host = 'localhost';
    $db_user = 'root';      // Utilisateur par défaut pour WAMP
    $db_password = '';      // Mot de passe par défaut pour WAMP
    $db_name = 'gestion';   // Nom de la base de données de l'application
    $db_port = '3306';      // Port par défaut pour MySQL
    $db_socket = '';        // Socket par défaut (vide pour TCP/IP)
}

// Charset et collation
$db_charset = 'utf8mb4';
$db_collation = 'utf8mb4_unicode_ci';
?>

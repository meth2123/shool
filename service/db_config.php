<?php
/**
 * Configuration de la base de données
 */

// Détection spécifique de Render.com
$is_render = (getenv('RENDER') == 'true' || getenv('IS_RENDER') == 'true' || strpos(getenv('RENDER_SERVICE_ID') ?: '', 'srv-') === 0);

// Journaliser la détection de l'environnement
error_log("Détection d'environnement - RENDER: " . ($is_render ? 'true' : 'false'));
error_log("Variables d'environnement disponibles: " . implode(', ', array_keys($_ENV)));

// Vérifier si nous sommes dans un environnement de production (Docker, Render.com, etc.)
if (file_exists('/.dockerenv') || getenv('DB_HOST') || $is_render) {
    // Définir des valeurs par défaut pour Render.com si détecté
    if ($is_render) {
        // Utiliser les paramètres de connexion externes pour Render.com
        // Ces valeurs doivent être configurées dans le dashboard Render.com
        $db_host = getenv('EXTERNAL_DATABASE_HOST') ?: 'mysql.external-service.com';
        $db_user = getenv('EXTERNAL_DATABASE_USER') ?: 'render_user';
        $db_password = getenv('EXTERNAL_DATABASE_PASSWORD') ?: 'render_password';
        $db_name = getenv('EXTERNAL_DATABASE_NAME') ?: 'render_db';
        $db_port = getenv('EXTERNAL_DATABASE_PORT') ?: '3306';
        $db_socket = '';
        
        error_log("Environnement Render.com détecté. Utilisation de la configuration externe.");
    } else {
        // Configuration standard pour Docker ou autre environnement de production
        $db_host = getenv('DB_HOST') ?: 'db';
        $db_user = getenv('DB_USER') ?: 'root';
        $db_password = getenv('DB_PASSWORD') ?: 'root_password';
        $db_name = getenv('DB_NAME') ?: 'gestion';
        $db_port = getenv('DB_PORT') ?: '3306';
        $db_socket = getenv('DB_SOCKET') ?: '';
    }
    
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

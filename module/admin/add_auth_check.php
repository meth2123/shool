<?php
// Script pour ajouter l'inclusion du fichier auth_check.php à tous les fichiers PHP
// qui incluent déjà main.php dans le module administrateur

// Vérifier si le dossier includes existe, sinon le créer
$includes_dir = __DIR__ . '/includes';
if (!file_exists($includes_dir)) {
    mkdir($includes_dir, 0755, true);
}

// Vérifier si le fichier auth_check.php existe, sinon afficher un message d'erreur
$auth_check_file = $includes_dir . '/auth_check.php';
if (!file_exists($auth_check_file)) {
    die("Le fichier auth_check.php n'existe pas dans le dossier includes. Veuillez le créer d'abord.");
}

// Récupérer tous les fichiers PHP du répertoire courant
$files = glob(__DIR__ . '/*.php');
$modified_count = 0;

foreach ($files as $file) {
    // Ignorer ce script lui-même
    if (basename($file) === 'add_auth_check.php') {
        continue;
    }
    
    // Lire le contenu du fichier
    $content = file_get_contents($file);
    
    // Vérifier si le fichier inclut déjà main.php mais pas auth_check.php
    if (preg_match('/include.*main\.php/i', $content) && !preg_match('/include.*auth_check\.php/i', $content)) {
        // Remplacer l'inclusion de main.php par l'inclusion de main.php suivie de auth_check.php
        $new_content = preg_replace(
            '/(include.*main\.php.*?;)/i',
            '$1' . PHP_EOL . 'include_once(\'includes/auth_check.php\');',
            $content
        );
        
        // Écrire le nouveau contenu dans le fichier
        file_put_contents($file, $new_content);
        $modified_count++;
        
        echo "Fichier modifié : " . basename($file) . PHP_EOL;
    }
}

echo "Terminé ! $modified_count fichiers ont été modifiés.";
?>

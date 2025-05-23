<?php
include_once('main.php');
include_once('../../service/db_utils.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password'] ?? '');
    
    // Validation du mot de passe
    if (strlen($new_password) < 6) {
        header("Location: modify.php?error=password_too_short");
        exit();
    }
    
    // Mise à jour du mot de passe dans la base de données
    $result = db_execute(
        "UPDATE students SET password = ? WHERE id = ?",
        [$new_password, $check],
        'ss'
    );
    
    if ($result) {
        // Mettre à jour aussi la table users si elle existe
        db_execute(
            "UPDATE users SET password = ? WHERE userid = ?",
            [$new_password, $check],
            'ss'
        );
        header("Location: modify.php?success=password_updated");
    } else {
        header("Location: modify.php?error=update_failed");
    }
    exit();
}

// Si on arrive ici, c'est une requête GET
header("Location: modify.php");
exit();
?>

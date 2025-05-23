<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Récupération et validation des données
$current_password = trim($_POST['current_password'] ?? '');
$new_password = trim($_POST['new_password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');

// Validation des données
$errors = [];

if (empty($current_password)) {
    $errors[] = "Le mot de passe actuel est requis.";
}

if (empty($new_password)) {
    $errors[] = "Le nouveau mot de passe est requis.";
} elseif (strlen($new_password) < 6) {
    $errors[] = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
}

if ($new_password !== $confirm_password) {
    $errors[] = "Les mots de passe ne correspondent pas.";
}

// Vérification du mot de passe actuel
$parent = db_fetch_row(
    "SELECT password FROM parents WHERE id = ?",
    [$check],
    'i'
);

if (!$parent) {
    $errors[] = "Parent non trouvé.";
} elseif ($parent['password'] !== $current_password) {
    $errors[] = "Le mot de passe actuel est incorrect.";
}

// S'il y a des erreurs, rediriger avec les messages d'erreur
if (!empty($errors)) {
    $error_string = implode("\n", $errors);
    header("Location: modify.php?error=" . urlencode($error_string));
    exit();
}

// Mise à jour du mot de passe
$success = db_query(
    "UPDATE parents, users 
     SET parents.password = ?, 
         users.password = ? 
     WHERE parents.id = users.userid 
     AND parents.id = ?",
    [$new_password, $new_password, $check],
    'ssi'
);

if ($success) {
    header("Location: modify.php?success=1");
} else {
    header("Location: modify.php?error=Une erreur est survenue lors de la mise à jour du mot de passe");
}
exit();
?>

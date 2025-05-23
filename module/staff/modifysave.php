<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Récupération et validation des données
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$address = trim($_POST['address'] ?? '');

// Validation des données
$errors = [];

if (empty($phone)) {
    $errors[] = "Le numéro de téléphone est requis.";
} elseif (!preg_match("/^[0-9+\s-]{8,15}$/", $phone)) {
    $errors[] = "Le format du numéro de téléphone est invalide.";
}

if (empty($email)) {
    $errors[] = "L'email est requis.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Le format de l'email est invalide.";
}

if (empty($password)) {
    $errors[] = "Le mot de passe est requis.";
} elseif (strlen($password) < 6) {
    $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
}

if (empty($address)) {
    $errors[] = "L'adresse est requise.";
}

// S'il y a des erreurs, rediriger avec les messages d'erreur
if (!empty($errors)) {
    $error_string = implode("\n", $errors);
    header("Location: modify.php?error=" . urlencode($error_string));
    exit();
}

// Mise à jour des données avec des requêtes préparées
$success = db_query(
    "UPDATE staff, users 
     SET staff.phone = ?, 
         staff.email = ?,
         staff.password = ?,
         staff.address = ?,
         users.password = ? 
     WHERE staff.id = users.userid 
     AND staff.id = ?",
    [$phone, $email, $password, $address, $password, $check],
    'sssssi'
);

if ($success) {
    header("Location: modify.php?success=1");
} else {
    header("Location: modify.php?error=Une erreur est survenue lors de la mise à jour");
}
exit();
?>

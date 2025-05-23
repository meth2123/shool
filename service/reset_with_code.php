<?php
include_once('mysqlcon.php');

// Récupération des données du formulaire
$reset_code = isset($_POST['reset_code']) ? trim($_POST['reset_code']) : '';
$new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

// Validation des données
if (empty($reset_code) || empty($new_password) || empty($confirm_password)) {
    header("Location: ../?error=" . urlencode("Tous les champs sont obligatoires"));
    exit();
}

if ($new_password !== $confirm_password) {
    header("Location: ../?error=" . urlencode("Les mots de passe ne correspondent pas"));
    exit();
}

if (strlen($new_password) < 8) {
    header("Location: ../?error=" . urlencode("Le mot de passe doit contenir au moins 8 caractères"));
    exit();
}

// Vérification du code de réinitialisation
$sql = "SELECT user_id FROM password_resets WHERE reset_code = ? AND expiry > NOW() AND used = 0";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $reset_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: ../?error=" . urlencode("Code de réinitialisation invalide ou expiré"));
    exit();
}

$reset = $result->fetch_assoc();
$user_id = $reset['user_id'];

// Mise à jour du mot de passe
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
$update_sql = "UPDATE users SET password = ? WHERE userid = ?";
$update_stmt = $link->prepare($update_sql);
$update_stmt->bind_param("ss", $hashed_password, $user_id);

if (!$update_stmt->execute()) {
    header("Location: ../?error=" . urlencode("Erreur lors de la mise à jour du mot de passe"));
    exit();
}

// Marquer le code comme utilisé
$mark_used_sql = "UPDATE password_resets SET used = 1 WHERE reset_code = ?";
$mark_used_stmt = $link->prepare($mark_used_sql);
$mark_used_stmt->bind_param("s", $reset_code);
$mark_used_stmt->execute();

// Redirection avec message de succès
header("Location: ../?reset=success");
$link->close();
exit(); 
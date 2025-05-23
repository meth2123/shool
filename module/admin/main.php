<?php
session_start();

// Correction des chemins d'inclusion avec des chemins absolus
$root_path = $_SERVER['DOCUMENT_ROOT'] . '/gestion/';
require_once($root_path . 'service/mysqlcon.php');
require_once(__DIR__ . '/includes/admin_utils.php');

// Ensure created_by column exists
addCreatedByColumnIfNotExists($link, 'students');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$check = $_SESSION['login_id'];

// Using prepared statement
$sql = "SELECT name FROM admin WHERE id = ?";
$stmt = $link->prepare($sql);
if (!$stmt) {
    error_log("Erreur de préparation de la requête : " . $link->error);
    header("Location: ../../index.php");
    exit();
}

$stmt->bind_param("s", $check);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    error_log("Aucun admin trouvé avec l'ID : " . $check);
    header("Location: ../../index.php");
    exit();
}

$login_session = $loged_user_name = $row['name'];
?>

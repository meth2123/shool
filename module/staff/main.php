<?php
include_once('../../service/mysqlcon.php');
$check = $_SESSION['login_id'];

// Utilisation de requête préparée pour plus de sécurité
$sql = "SELECT name FROM staff WHERE id = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $check);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$login_session = $loged_user_name = $row['name'] ?? null;

if(!isset($login_session)) {
    header("Location:../../");
    exit();
}
?>

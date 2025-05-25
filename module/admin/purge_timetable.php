<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Vérifier si l'utilisateur est connecté et est administrateur
if (!isset($_SESSION['login_id']) || $_SESSION['login_type'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

// Récupérer l'ID de l'administrateur connecté
$admin_id = $_SESSION['login_id'];

// Récupérer les paramètres de l'URL
$class_id = $_GET['class_id'] ?? '';
$teacher_id = $_GET['teacher_id'] ?? '';
$subject_id = $_GET['subject_id'] ?? '';
$day = $_GET['day'] ?? '';
$slot_id = $_GET['slot_id'] ?? '';

// Préparer la requête de base
$query = "DELETE FROM class_schedule WHERE 1=1";
$types = "";
$params = [];

// Ajouter les conditions en fonction des paramètres fournis
if (!empty($class_id)) {
    $query .= " AND class_id = ?";
    $types .= "s";
    $params[] = $class_id;
}

if (!empty($teacher_id)) {
    $query .= " AND teacher_id = ?";
    $types .= "s";
    $params[] = $teacher_id;
}

if (!empty($subject_id)) {
    $query .= " AND subject_id = ?";
    $types .= "s";
    $params[] = $subject_id;
}

if (!empty($day)) {
    $query .= " AND day_of_week = ?";
    $types .= "s";
    $params[] = $day;
}

if (!empty($slot_id)) {
    $query .= " AND slot_id = ?";
    $types .= "i";
    $params[] = $slot_id;
}

// Si aucun paramètre n'est fourni, ne pas exécuter la requête
if (empty($params)) {
    $_SESSION['error_message'] = "Veuillez spécifier au moins un critère pour la purge des emplois du temps";
    header("Location: timeTable.php");
    exit;
}

// Exécuter la requête de suppression
$stmt = $link->prepare($query);

if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}

if ($stmt->execute()) {
    $deleted_rows = $stmt->affected_rows;
    $_SESSION['success_message'] = "$deleted_rows emplois du temps ont été supprimés avec succès";
} else {
    $_SESSION['error_message'] = "Erreur lors de la suppression des emplois du temps: " . $link->error;
}

// Rediriger vers la page d'emploi du temps avec les mêmes paramètres
$redirect_url = "timeTable.php";
$params_url = [];

if (!empty($class_id)) {
    $params_url[] = "class_id=" . urlencode($class_id);
}

if (!empty($teacher_id)) {
    $params_url[] = "teacher_id=" . urlencode($teacher_id);
}

if (!empty($params_url)) {
    $redirect_url .= "?" . implode("&", $params_url);
}

header("Location: $redirect_url");
exit;
?>

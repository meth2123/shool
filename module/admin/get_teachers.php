<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['login_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Récupérer les IDs des enseignants
$teacher_ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];

if (empty($teacher_ids)) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Préparer la requête avec des paramètres
$placeholders = implode(',', array_fill(0, count($teacher_ids), '?'));
$query = "SELECT id, name FROM teachers WHERE id IN ($placeholders) ORDER BY name";

$stmt = $link->prepare($query);

// Bind les paramètres
$types = str_repeat('s', count($teacher_ids));
$stmt->bind_param($types, ...$teacher_ids);

$stmt->execute();
$result = $stmt->get_result();

$teachers = [];
while ($row = $result->fetch_assoc()) {
    $teachers[] = $row;
}

header('Content-Type: application/json');
echo json_encode($teachers);
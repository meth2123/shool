<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Vérifier que l'utilisateur est connecté et est un admin
if (!isset($_SESSION['login_id'])) {
    die("Accès non autorisé");
}

// Lire le contenu du fichier SQL
$sql = file_get_contents('sql/create_teacher_absences.sql');

// Exécuter le script SQL
if ($link->multi_query($sql)) {
    echo "Table teacher_absences créée avec succès";
} else {
    echo "Erreur lors de la création de la table: " . $link->error;
}

$link->close();
?> 
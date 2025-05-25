<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Vérifier si l'utilisateur est connecté et est administrateur
if (!isset($_SESSION['login_id']) || $_SESSION['login_type'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

// Supprimer les emplois du temps en double
$query = "
    DELETE cs1 FROM class_schedule cs1
    INNER JOIN (
        SELECT MAX(id) as max_id, class_id, subject_id, slot_id, day_of_week, semester, academic_year
        FROM class_schedule
        GROUP BY class_id, subject_id, slot_id, day_of_week, semester, academic_year
        HAVING COUNT(*) > 1
    ) cs2
    ON cs1.class_id = cs2.class_id 
    AND cs1.subject_id = cs2.subject_id 
    AND cs1.slot_id = cs2.slot_id 
    AND cs1.day_of_week = cs2.day_of_week 
    AND cs1.semester = cs2.semester 
    AND cs1.academic_year = cs2.academic_year
    AND cs1.id < cs2.max_id
";

$result = $link->query($query);

if ($result) {
    $deleted_rows = $link->affected_rows;
    echo "Nettoyage terminé. $deleted_rows emplois du temps en double ont été supprimés.";
} else {
    echo "Erreur lors du nettoyage : " . $link->error;
}

// Rediriger vers la page principale après 3 secondes
header("refresh:3;url=timeTable.php");
?>

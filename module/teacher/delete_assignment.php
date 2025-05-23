<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Récupérer l'ID du devoir
$assignment_id = $_GET['id'] ?? null;
$teacher_id = $_SESSION['login_id'];

// Log pour déboguer
error_log("Tentative de suppression - Assignment ID: $assignment_id, Teacher ID: $teacher_id");

if (!$assignment_id) {
    error_log("Erreur: ID du devoir manquant");
    header("Location: exam.php?error=missing_id");
    exit();
}

// Vérifier que le devoir existe et appartient au professeur
$check_query = "SELECT e.id 
                FROM examschedule e
                JOIN course c ON e.courseid = c.id
                WHERE e.id = ? 
                AND e.created_by = ?
                AND c.teacherid = ?";

$check_stmt = $link->prepare($check_query);
$check_stmt->bind_param("sss", $assignment_id, $teacher_id, $teacher_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    error_log("Erreur: Le devoir n'existe pas ou n'appartient pas au professeur");
    header("Location: exam.php?error=unauthorized");
    exit();
}

// Supprimer le devoir
$delete_query = "DELETE FROM examschedule WHERE id = ? AND created_by = ?";
$delete_stmt = $link->prepare($delete_query);
$delete_stmt->bind_param("ss", $assignment_id, $teacher_id);

if ($delete_stmt->execute()) {
    error_log("Devoir supprimé avec succès - ID: $assignment_id");
    header("Location: exam.php?success=deleted");
} else {
    error_log("Erreur lors de la suppression - " . $delete_stmt->error);
    header("Location: exam.php?error=delete_failed");
}
exit(); 
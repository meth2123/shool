<?php
// Inclure le fichier principal qui gère déjà la session et les vérifications
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Initialiser les variables
$admin_id = $_SESSION['login_id'];
$duplicates_removed = 0;
$error = null;

// Étape 1: Identifier les doublons (même classe, matière, créneau, jour, semestre et année académique)
$find_duplicates = "
    SELECT MIN(id) as keep_id, class_id, subject_id, slot_id, day_of_week, semester, academic_year, COUNT(*) as count
    FROM class_schedule
    GROUP BY class_id, subject_id, slot_id, day_of_week, semester, academic_year
    HAVING COUNT(*) > 1
";

$duplicates_result = $link->query($find_duplicates);

if ($duplicates_result) {
    // Étape 2: Supprimer les doublons en conservant l'entrée avec l'ID le plus petit
    while ($row = $duplicates_result->fetch_assoc()) {
        $keep_id = $row['keep_id'];
        $class_id = $row['class_id'];
        $subject_id = $row['subject_id'];
        $slot_id = $row['slot_id'];
        $day_of_week = $row['day_of_week'];
        $semester = $row['semester'];
        $academic_year = $row['academic_year'];
        
        $delete_query = "
            DELETE FROM class_schedule 
            WHERE 
                id != ? AND
                class_id = ? AND 
                subject_id = ? AND 
                slot_id = ? AND 
                day_of_week = ? AND 
                semester = ? AND 
                academic_year = ?
        ";
        
        $stmt = $link->prepare($delete_query);
        $stmt->bind_param("sssssss", $keep_id, $class_id, $subject_id, $slot_id, $day_of_week, $semester, $academic_year);
        $stmt->execute();
        
        $duplicates_removed += $stmt->affected_rows;
    }
    
    // Message de succès
    if (!$error) {
        $_SESSION['success_message'] = "$duplicates_removed emplois du temps en double ont été supprimés avec succès.";
    } else {
        $_SESSION['error_message'] = $error;
    }
} else {
    $_SESSION['error_message'] = "Erreur lors de la recherche des doublons: " . $link->error;
}

// Redirection automatique vers la page d'emploi du temps
header('Location: timeTable.php');
exit;
?>

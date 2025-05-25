<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Vérifier si l'utilisateur est connecté
$check = $_SESSION['login_id'] ?? null;
if(!isset($check)) {
    header("Location:../../");
    exit();
}

// Récupérer l'ID de l'emploi du temps à supprimer
$schedule_id = $_GET['schedule_id'] ?? null;

if(!$schedule_id) {
    $_SESSION['error_message'] = "ID de l'emploi du temps non spécifié";
    header("Location: timeTable.php");
    exit();
}

// Vérifier si l'emploi du temps appartient à l'administrateur connecté
$admin_id = $_SESSION['login_id'];
$stmt = $link->prepare("
    SELECT id, teacher_id FROM class_schedule 
    WHERE id = ? AND created_by = ? COLLATE utf8mb4_0900_ai_ci
");
$stmt->bind_param("is", $schedule_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$schedule = $result->fetch_assoc();

if(!$schedule) {
    $_SESSION['error_message'] = "Emploi du temps non trouvé ou vous n'avez pas les droits d'accès";
    header("Location: timeTable.php");
    exit();
}

// Supprimer l'emploi du temps
$stmt = $link->prepare("DELETE FROM class_schedule WHERE id = ? AND created_by = ? COLLATE utf8mb4_0900_ai_ci");
$stmt->bind_param("is", $schedule_id, $admin_id);

if($stmt->execute()) {
    $_SESSION['success_message'] = "Emploi du temps supprimé avec succès";
    
    // Rediriger vers la page de l'enseignant si on vient de là
    if(isset($_GET['from']) && $_GET['from'] === 'teacher') {
        header("Location: viewTeacherSchedules.php?teacher_id=" . urlencode($schedule['teacher_id']));
    } else {
        header("Location: timeTable.php");
    }
} else {
    $_SESSION['error_message'] = "Erreur lors de la suppression de l'emploi du temps: " . $link->error;
    header("Location: timeTable.php");
}
exit();
?>

<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];

// Vérification de l'ID de l'enseignant
$teacher_id = $_GET['id'] ?? '';
if (empty($teacher_id)) {
    header("Location: manageTeacher.php?error=" . urlencode("ID de l'enseignant manquant"));
    exit;
}

// Vérifier que l'enseignant existe et appartient à cet admin
$sql = "SELECT * FROM teachers WHERE id = ? AND created_by = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("ss", $teacher_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manageTeacher.php?error=" . urlencode("Enseignant non trouvé ou accès non autorisé"));
    exit;
}

// Supprimer l'enseignant et l'utilisateur associé dans une transaction
$link->begin_transaction();

try {
    // Supprimer d'abord les associations avec les cours
    $delete_courses_sql = "DELETE FROM takencoursebyteacher WHERE teacherid = ?";
    $delete_courses_stmt = $link->prepare($delete_courses_sql);
    $delete_courses_stmt->bind_param("s", $teacher_id);
    $delete_courses_stmt->execute();
    
    // Supprimer les enregistrements de présence
    $delete_attendance_sql = "DELETE FROM attendance WHERE attendedid = ?";
    $delete_attendance_stmt = $link->prepare($delete_attendance_sql);
    $delete_attendance_stmt->bind_param("s", $teacher_id);
    $delete_attendance_stmt->execute();
    
    // Supprimer l'enseignant
    $delete_sql = "DELETE FROM teachers WHERE id = ? AND created_by = ?";
    $delete_stmt = $link->prepare($delete_sql);
    $delete_stmt->bind_param("ss", $teacher_id, $admin_id);
    
    if (!$delete_stmt->execute() || $delete_stmt->affected_rows === 0) {
        throw new Exception("Erreur lors de la suppression de l'enseignant");
    }
    
    // Supprimer l'utilisateur associé
    $delete_user_sql = "DELETE FROM users WHERE userid = ? AND usertype = 'teacher'";
    $delete_user_stmt = $link->prepare($delete_user_sql);
    $delete_user_stmt->bind_param("s", $teacher_id);
    $delete_user_stmt->execute();
    
    // Valider la transaction
    $link->commit();
    
    // Rediriger avec un message de succès
    header("Location: manageTeacher.php?success=" . urlencode("Enseignant supprimé avec succès"));
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $link->rollback();
    
    // Rediriger avec un message d'erreur
    header("Location: manageTeacher.php?error=" . urlencode("Erreur lors de la suppression de l'enseignant: " . $e->getMessage()));
}

exit;
?>

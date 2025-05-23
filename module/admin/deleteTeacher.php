<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

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

// Supprimer l'enseignant
$delete_sql = "DELETE FROM teachers WHERE id = ? AND created_by = ?";
$delete_stmt = $link->prepare($delete_sql);
$delete_stmt->bind_param("ss", $teacher_id, $admin_id);

if ($delete_stmt->execute() && $delete_stmt->affected_rows > 0) {
    // Supprimer également l'utilisateur associé
    $delete_user_sql = "DELETE FROM users WHERE userid = ?";
    $delete_user_stmt = $link->prepare($delete_user_sql);
    $delete_user_stmt->bind_param("s", $teacher_id);
    $delete_user_stmt->execute();

    header("Location: manageTeacher.php?success=" . urlencode("Enseignant supprimé avec succès"));
} else {
    header("Location: manageTeacher.php?error=" . urlencode("Erreur lors de la suppression de l'enseignant"));
}
exit;
?>

<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];
$course_id = $_GET['id'] ?? '';

if (empty($course_id)) {
    header("Location: course.php?error=" . urlencode("ID du cours non spécifié"));
    exit;
}

// Verify that the course belongs to this admin
$check_sql = "SELECT id FROM course WHERE id = ? AND created_by = ?";
$check_stmt = $link->prepare($check_sql);
$check_stmt->bind_param("ss", $course_id, $admin_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: course.php?error=" . urlencode("Cours non trouvé ou accès non autorisé"));
    exit;
}

// Delete the course
$delete_sql = "DELETE FROM course WHERE id = ? AND created_by = ?";
$delete_stmt = $link->prepare($delete_sql);
$delete_stmt->bind_param("ss", $course_id, $admin_id);

if ($delete_stmt->execute()) {
    header("Location: course.php?success=" . urlencode("Cours supprimé avec succès"));
} else {
    header("Location: course.php?error=" . urlencode("Erreur lors de la suppression du cours"));
}
exit;
?>

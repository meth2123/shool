<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];
$staff_id = $_GET['id'] ?? '';

if (empty($staff_id)) {
    header("Location: manageStaff.php?error=" . urlencode("ID du personnel non spécifié"));
    exit;
}

// Start transaction
$link->begin_transaction();

try {
    // First verify that the staff member belongs to this admin
    $check_sql = "SELECT id FROM staff WHERE id = ? AND created_by = ?";
    $check_stmt = $link->prepare($check_sql);
    $check_stmt->bind_param("ss", $staff_id, $admin_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        throw new Exception("Personnel non trouvé ou accès non autorisé");
    }

    // Delete from users table first (foreign key constraint)
    $delete_user_sql = "DELETE FROM users WHERE userid = ? AND usertype = 'staff'";
    $delete_user_stmt = $link->prepare($delete_user_sql);
    $delete_user_stmt->bind_param("s", $staff_id);
    
    if (!$delete_user_stmt->execute()) {
        throw new Exception("Erreur lors de la suppression du compte utilisateur");
    }

    // Then delete from staff table
    $delete_staff_sql = "DELETE FROM staff WHERE id = ? AND created_by = ?";
    $delete_staff_stmt = $link->prepare($delete_staff_sql);
    $delete_staff_stmt->bind_param("ss", $staff_id, $admin_id);
    
    if (!$delete_staff_stmt->execute()) {
        throw new Exception("Erreur lors de la suppression du membre du personnel");
    }

    $link->commit();
    header("Location: manageStaff.php?success=" . urlencode("Membre du personnel supprimé avec succès"));
    exit;
} catch (Exception $e) {
    $link->rollback();
    header("Location: manageStaff.php?error=" . urlencode($e->getMessage()));
    exit;
}
?>

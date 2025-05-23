<?php
include_once('main.php');
require_once('../../db/config.php');

// Get admin ID for filtering
$admin_id = $_SESSION['login_id'];

// Initialize database connection
$conn = getDbConnection();

if(isset($_POST['submit'])){
    try {
        $id = trim($_POST['id']);
        
        // Verify that this parent was created by the current admin
        $check_sql = "SELECT id FROM parents WHERE id = ? AND created_by = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $id, $admin_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows === 0) {
            throw new Exception("Vous n'êtes pas autorisé à supprimer ce parent");
        }

        // Start transaction
        $conn->begin_transaction();

        // Delete from parents table
        $sql = "DELETE FROM parents WHERE id = ? AND created_by = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $id, $admin_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la suppression du parent");
        }

        // Delete from users table
        $sql_user = "DELETE FROM users WHERE userid = ?";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("s", $id);
        
        if (!$stmt_user->execute()) {
            throw new Exception("Erreur lors de la suppression du compte utilisateur");
        }

        // Commit transaction
        $conn->commit();
        
        $_SESSION['success_message'] = "Parent supprimé avec succès";
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn)) {
            $conn->rollback();
        }
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Close database connection
if (isset($stmt)) $stmt->close();
if (isset($stmt_user)) $stmt_user->close();
if (isset($check_stmt)) $check_stmt->close();
if (isset($conn)) $conn->close();

// Redirect back to deleteParent.php
header("Location: deleteParent.php");
exit;
?>

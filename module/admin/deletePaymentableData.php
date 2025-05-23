<?php
include_once('main.php');
require_once('../../db/config.php');

// Get admin ID for filtering
$admin_id = $_SESSION['login_id'];

// Initialize database connection
$conn = getDbConnection();

if (isset($_POST['id'])) {
    $payment_id = intval($_POST['id']);

    try {
        // Verify that the payment belongs to a student created by this admin
        $check_sql = "SELECT p.id 
                     FROM payment p 
                     INNER JOIN students s ON p.studentid = s.id 
                     WHERE p.id = ? AND s.created_by = ?";
        
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $payment_id, $admin_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows === 0) {
            throw new Exception("Paiement non trouvé ou non autorisé");
        }

        // Delete the payment
        $delete_sql = "DELETE FROM payment WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $payment_id);
        
        if (!$delete_stmt->execute()) {
            throw new Exception("Erreur lors de la suppression du paiement");
        }

        // Redirect with success message
        header("Location: deletePayment.php?success=1");
        exit;
    } catch (Exception $e) {
        // Redirect with error message
        header("Location: deletePayment.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    // Redirect with error if no ID provided
    header("Location: deletePayment.php?error=ID de paiement non spécifié");
    exit;
}

// Close database connection
if (isset($check_stmt)) $check_stmt->close();
if (isset($delete_stmt)) $delete_stmt->close();
if (isset($conn)) $conn->close();
?>

<?php
session_start();
include_once('mysqlcon.php');
$myid = $_POST['myid'];
$mypassword = $_POST['mypassword'];
$myid = stripslashes($myid);
$mypassword = stripslashes($mypassword);

// Récupérer le mot de passe stocké et le type d'utilisateur
$sql = "SELECT usertype, password, userid FROM users WHERE userid=?";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $myid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $stored_password = $row['password'];
    $control = $row['usertype'];
    $user_id = $row['userid'];
    
    // Vérifier l'abonnement pour tous les types d'utilisateurs
    $school_id = null;
    $admin_email = null;
    
    // Récupérer l'ID de l'école ou l'email de l'administrateur selon le type d'utilisateur
    switch ($control) {
        case 'admin':
            // Pour les administrateurs, vérifier directement avec leur email
            $sql = "SELECT email FROM admin WHERE id = ?";
            $stmt = $link->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $admin_data = $result->fetch_assoc();
                    $admin_email = $admin_data['email'];
                }
            }
            break;
            
        case 'teacher':
            // Pour les enseignants, récupérer l'ID de l'école
            $sql = "SELECT created_by FROM teachers WHERE id = ?";
            $stmt = $link->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $teacher_data = $result->fetch_assoc();
                    $admin_id = $teacher_data['created_by'];
                    
                    // Récupérer l'email de l'administrateur
                    $sql = "SELECT email FROM admin WHERE id = ?";
                    $stmt = $link->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("s", $admin_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result && $result->num_rows > 0) {
                            $admin_data = $result->fetch_assoc();
                            $admin_email = $admin_data['email'];
                        }
                    }
                }
            }
            break;
            
        case 'student':
        case 'parent':
        case 'staff':
            // Pour les élèves, parents et staff, récupérer l'ID de l'école
            $table = $control . 's'; // students, parents, staff
            if ($control === 'staff') $table = 'staff'; // Exception pour staff qui est déjà au pluriel
            
            $sql = "SELECT created_by FROM $table WHERE id = ?";
            $stmt = $link->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $user_data = $result->fetch_assoc();
                    $admin_id = $user_data['created_by'];
                    
                    // Récupérer l'email de l'administrateur
                    $sql = "SELECT email FROM admin WHERE id = ?";
                    $stmt = $link->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("s", $admin_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result && $result->num_rows > 0) {
                            $admin_data = $result->fetch_assoc();
                            $admin_email = $admin_data['email'];
                        }
                    }
                }
            }
            break;
    }
    
    // Vérifier l'abonnement si nous avons récupéré l'email de l'administrateur
    if ($admin_email) {
        $sql_subscription = "SELECT payment_status FROM subscriptions 
                            WHERE admin_email COLLATE utf8mb4_unicode_ci = ? ";
        $stmt_subscription = $link->prepare($sql_subscription);
        
        if ($stmt_subscription) {
            $stmt_subscription->bind_param("s", $admin_email);
            $stmt_subscription->execute();
            $result_subscription = $stmt_subscription->get_result();
            
            if ($result_subscription && $result_subscription->num_rows > 0) {
                $subscription = $result_subscription->fetch_assoc();
                
                // Empêcher la connexion si l'abonnement est expiré, en attente ou échoué
                if (in_array($subscription['payment_status'], ['expired', 'pending', 'failed'])) {
                    header("Location:../login.php?error=account_inactive&status=" . $subscription['payment_status']);
                    exit;
                }
            }
        }
    }
    
    // Vérifier si c'est un mot de passe hashé
    if (password_verify($mypassword, $stored_password)) {
        $password_correct = true;
    } else {
        // Vérification de l'ancien format (non hashé)
        $password_correct = ($mypassword === $stored_password);
    }
    
    if ($password_correct) {
        // Définir les variables de session importantes
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_type'] = $control;
        $_SESSION['login_id'] = $myid;
        
        switch ($control) {
            case "admin":
                header("Location:../module/admin");
                break;
            case "teacher":
                header("Location:../module/teacher");
                break;
            case "student":
                header("Location:../module/student");
                break;
            case "staff":
                header("Location:../module/staff");
                break;
            case "parent":
                header("Location:../module/parent");
                break;
            default:
                header("Location:../index.php?login=false");
        }
        exit;
    }
}

// Si on arrive ici, la connexion a échoué
header("Location:../login.php?login=false");
exit;
?>

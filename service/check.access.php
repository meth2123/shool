<?php
session_start();
include_once('mysqlcon.php');
$myid = $_POST['myid'];
$mypassword = $_POST['mypassword'];
$myid = stripslashes($myid);
$mypassword = stripslashes($mypassword);

// Récupérer le mot de passe stocké et le type d'utilisateur
$sql = "SELECT usertype, password FROM users WHERE userid=?";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $myid);
$stmt->execute();
$result = $stmt->get_result();
$_SESSION['login_id'] = $myid;

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $stored_password = $row['password'];
    $control = $row['usertype'];
    
    // Vérifier si c'est un mot de passe hashé
    if (password_verify($mypassword, $stored_password)) {
        $password_correct = true;
    } else {
        // Vérification de l'ancien format (non hashé)
        $password_correct = ($mypassword === $stored_password);
    }
    
    if ($password_correct) {
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
header("Location:../index.php?login=false");
exit;
?>

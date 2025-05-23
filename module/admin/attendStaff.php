<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

if ($_POST['submit']) {
    $id = $_POST['id'];
    $admin_id = $_SESSION['login_id'];
    $cdate = date("Y-m-d");

    // Vérifier si l'entrée n'existe pas déjà
    $check_sql = "SELECT id FROM attendance WHERE date = ? AND attendedid = ?";
    $check_stmt = $link->prepare($check_sql);
    $check_stmt->bind_param("ss", $cdate, $id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        header("Location: staffAttendance.php?error=" . urlencode("La présence a déjà été enregistrée pour aujourd'hui"));
        exit;
    }

    // Insérer la nouvelle présence
    $sql = "INSERT INTO attendance (date, attendedid, created_by) VALUES (?, ?, ?)";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("sss", $cdate, $id, $admin_id);
    
    if ($stmt->execute()) {
        header("Location: staffAttendance.php?success=" . urlencode("Présence enregistrée avec succès"));
    } else {
        header("Location: staffAttendance.php?error=" . urlencode("Erreur lors de l'enregistrement de la présence"));
    }
    exit;
}

// Si on arrive ici sans POST, rediriger vers la page de présence
header("Location: staffAttendance.php");
exit;
?>

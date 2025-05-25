<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Traitement en mode AJAX pour éviter les redirections
header('Content-Type: application/json');

// Vérifier si la requête est en AJAX
if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['id']) && isset($_POST['status'])) {
        $id = $_POST['id'];
        $admin_id = $_SESSION['login_id'];
        $cdate = date("Y-m-d");
        $status = $_POST['status']; // "present" ou "absent"

        // Vérifier si l'entrée n'existe pas déjà dans l'une des tables
        $check_presence_sql = "SELECT id FROM attendance WHERE date = ? AND attendedid = ?";
        $check_presence_stmt = $link->prepare($check_presence_sql);
        $check_presence_stmt->bind_param("ss", $cdate, $id);
        $check_presence_stmt->execute();
        $presence_result = $check_presence_stmt->get_result();
        $has_presence = $presence_result->num_rows > 0;
        $presence_result->free();
        $check_presence_stmt->close();
        
        if (!$has_presence) {
            $check_absence_sql = "SELECT id FROM teacher_absences WHERE date = ? AND teacher_id = ?";
            $check_absence_stmt = $link->prepare($check_absence_sql);
            $check_absence_stmt->bind_param("ss", $cdate, $id);
            $check_absence_stmt->execute();
            $absence_result = $check_absence_stmt->get_result();
            $has_absence = $absence_result->num_rows > 0;
            $absence_result->free();
            $check_absence_stmt->close();
            
            if ($has_absence) {
                $response['message'] = "La présence/absence a déjà été enregistrée pour aujourd'hui";
                echo json_encode($response);
                exit;
            }
        } else {
            $response['message'] = "La présence/absence a déjà été enregistrée pour aujourd'hui";
            echo json_encode($response);
            exit;
        }

        // Vérifier que l'enseignant appartient à cet admin
        $verify_sql = "SELECT id FROM teachers WHERE id = ? AND CAST(created_by AS CHAR) = CAST(? AS CHAR)";
        $verify_stmt = $link->prepare($verify_sql);
        $verify_stmt->bind_param("ss", $id, $admin_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        $is_authorized = $verify_result->num_rows > 0;
        $verify_result->free();
        $verify_stmt->close();
        
        if (!$is_authorized) {
            $response['message'] = "Accès non autorisé";
            echo json_encode($response);
            exit;
        }

        if ($status === "present") {
            // Insérer la nouvelle présence
            $sql = "INSERT INTO attendance (date, attendedid, created_by) VALUES (?, ?, ?)";
            $stmt = $link->prepare($sql);
            $stmt->bind_param("sss", $cdate, $id, $admin_id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Présence enregistrée avec succès";
            } else {
                $response['message'] = "Erreur lors de l'enregistrement de la présence";
            }
            $stmt->close();
        } else if ($status === "absent") {
            // Insérer la nouvelle absence
            $sql = "INSERT INTO teacher_absences (date, teacher_id, created_by) VALUES (?, ?, ?)";
            $stmt = $link->prepare($sql);
            $stmt->bind_param("sss", $cdate, $id, $admin_id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Absence enregistrée avec succès";
            } else {
                $response['message'] = "Erreur lors de l'enregistrement de l'absence";
            }
            $stmt->close();
        } else {
            $response['message'] = "Statut non valide";
        }
    } else {
        $response['message'] = "Paramètres manquants";
    }
    
    echo json_encode($response);
    exit;
}

// Pour les requêtes non-AJAX (compatibilité avec l'ancien code)
if (isset($_POST['submit'])) {
    $id = $_POST['id'];
    $admin_id = $_SESSION['login_id'];
    $cdate = date("Y-m-d");
    $status = $_POST['submit']; // "present" ou "absent"

    // Vérifier si l'entrée n'existe pas déjà dans l'une des tables
    $check_presence_sql = "SELECT id FROM attendance WHERE date = ? AND attendedid = ?";
    $check_presence_stmt = $link->prepare($check_presence_sql);
    $check_presence_stmt->bind_param("ss", $cdate, $id);
    $check_presence_stmt->execute();
    $presence_result = $check_presence_stmt->get_result();
    $has_presence = $presence_result->num_rows > 0;
    $presence_result->free();
    $check_presence_stmt->close();
    
    if (!$has_presence) {
        $check_absence_sql = "SELECT id FROM teacher_absences WHERE date = ? AND teacher_id = ?";
        $check_absence_stmt = $link->prepare($check_absence_sql);
        $check_absence_stmt->bind_param("ss", $cdate, $id);
        $check_absence_stmt->execute();
        $absence_result = $check_absence_stmt->get_result();
        $has_absence = $absence_result->num_rows > 0;
        $absence_result->free();
        $check_absence_stmt->close();
        
        if ($has_absence) {
            header("Location: teacherAttendance.php?error=" . urlencode("La présence/absence a déjà été enregistrée pour aujourd'hui"));
            exit;
        }
    } else {
        header("Location: teacherAttendance.php?error=" . urlencode("La présence/absence a déjà été enregistrée pour aujourd'hui"));
        exit;
    }

    // Vérifier que l'enseignant appartient à cet admin
    $verify_sql = "SELECT id FROM teachers WHERE id = ? AND CAST(created_by AS CHAR) = CAST(? AS CHAR)";
    $verify_stmt = $link->prepare($verify_sql);
    $verify_stmt->bind_param("ss", $id, $admin_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    $is_authorized = $verify_result->num_rows > 0;
    $verify_result->free();
    $verify_stmt->close();
    
    if (!$is_authorized) {
        header("Location: teacherAttendance.php?error=" . urlencode("Accès non autorisé"));
        exit;
    }

    if ($status === "present") {
        // Insérer la nouvelle présence
        $sql = "INSERT INTO attendance (date, attendedid, created_by) VALUES (?, ?, ?)";
        $stmt = $link->prepare($sql);
        $stmt->bind_param("sss", $cdate, $id, $admin_id);
        
        if ($stmt->execute()) {
            header("Location: teacherAttendance.php?success=" . urlencode("Présence enregistrée avec succès"));
        } else {
            header("Location: teacherAttendance.php?error=" . urlencode("Erreur lors de l'enregistrement de la présence"));
        }
        $stmt->close();
    } else if ($status === "absent") {
        // Insérer la nouvelle absence
        $sql = "INSERT INTO teacher_absences (date, teacher_id, created_by) VALUES (?, ?, ?)";
        $stmt = $link->prepare($sql);
        $stmt->bind_param("sss", $cdate, $id, $admin_id);
        
        if ($stmt->execute()) {
            header("Location: teacherAttendance.php?success=" . urlencode("Absence enregistrée avec succès"));
        } else {
            header("Location: teacherAttendance.php?error=" . urlencode("Erreur lors de l'enregistrement de l'absence"));
        }
        $stmt->close();
    }
    exit;
}

// Si on arrive ici sans POST, rediriger vers la page de présence
header("Location: teacherAttendance.php");
exit;
?>

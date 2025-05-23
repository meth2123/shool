<?php
include_once('../main.php');

if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit;
}

$admin_id = $_SESSION['login_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Fonction de vérification des droits
function verifyAccess($link, $studentid, $courseid, $admin_id) {
    // Vérifier que l'étudiant existe et appartient à cet admin
    $student_check = $link->prepare("SELECT id FROM students WHERE id = ? AND created_by = ?");
    $student_check->bind_param("ss", $studentid, $admin_id);
    $student_check->execute();
    if ($student_check->get_result()->num_rows === 0) {
        return "Étudiant invalide";
    }

    // Vérifier que le cours existe et appartient à cet admin
    $course_check = $link->prepare("SELECT id FROM course WHERE id = ? AND created_by = ?");
    $course_check->bind_param("ss", $courseid, $admin_id);
    $course_check->execute();
    if ($course_check->get_result()->num_rows === 0) {
        return "Cours invalide";
    }

    return true;
}

if ($action === 'enroll') {
    // Validation des données
    $studentid = trim($_POST['studentid'] ?? '');
    $courseid = trim($_POST['courseid'] ?? '');
    $created_by = trim($_POST['created_by'] ?? '');

    if (empty($studentid) || empty($courseid) || empty($created_by)) {
        header("Location: ../course.php?error=" . urlencode("Tous les champs sont obligatoires"));
        exit;
    }

    // Vérifier que l'admin est bien le propriétaire
    if ($created_by !== $admin_id) {
        header("Location: ../course.php?error=" . urlencode("Action non autorisée"));
        exit;
    }

    $access_check = verifyAccess($link, $studentid, $courseid, $admin_id);
    if ($access_check !== true) {
        header("Location: ../course.php?error=" . urlencode($access_check));
        exit;
    }

    // Vérifier si l'étudiant n'est pas déjà inscrit à ce cours
    $enrollment_check = $link->prepare("SELECT id FROM student_courses WHERE studentid = ? AND courseid = ? AND status = 'active'");
    $enrollment_check->bind_param("ss", $studentid, $courseid);
    $enrollment_check->execute();
    if ($enrollment_check->get_result()->num_rows > 0) {
        header("Location: ../course.php?error=" . urlencode("L'étudiant est déjà inscrit à ce cours"));
        exit;
    }

    // Inscrire l'étudiant au cours
    $stmt = $link->prepare("INSERT INTO student_courses (studentid, courseid, created_by) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $studentid, $courseid, $created_by);

    if ($stmt->execute()) {
        header("Location: ../viewCourse.php?id=" . $courseid . "&success=" . urlencode("Étudiant inscrit avec succès"));
    } else {
        header("Location: ../viewCourse.php?id=" . $courseid . "&error=" . urlencode("Erreur lors de l'inscription: " . $stmt->error));
    }
    exit;
}

// Mise à jour du statut d'inscription
else if ($action === 'update_status') {
    $studentid = trim($_GET['student_id'] ?? '');
    $courseid = trim($_GET['course_id'] ?? '');
    $status = trim($_GET['status'] ?? '');

    if (empty($studentid) || empty($courseid) || empty($status)) {
        header("Location: ../viewCourse.php?id=" . $courseid . "&error=" . urlencode("Paramètres manquants"));
        exit;
    }

    if (!in_array($status, ['active', 'completed', 'dropped'])) {
        header("Location: ../viewCourse.php?id=" . $courseid . "&error=" . urlencode("Statut invalide"));
        exit;
    }

    $access_check = verifyAccess($link, $studentid, $courseid, $admin_id);
    if ($access_check !== true) {
        header("Location: ../viewCourse.php?id=" . $courseid . "&error=" . urlencode($access_check));
        exit;
    }

    $stmt = $link->prepare("UPDATE student_courses SET status = ? WHERE studentid = ? AND courseid = ?");
    $stmt->bind_param("sss", $status, $studentid, $courseid);

    if ($stmt->execute()) {
        header("Location: ../viewCourse.php?id=" . $courseid . "&success=" . urlencode("Statut mis à jour avec succès"));
    } else {
        header("Location: ../viewCourse.php?id=" . $courseid . "&error=" . urlencode("Erreur lors de la mise à jour: " . $stmt->error));
    }
    exit;
}

// Retirer un étudiant du cours
else if ($action === 'remove') {
    $studentid = trim($_GET['student_id'] ?? '');
    $courseid = trim($_GET['course_id'] ?? '');

    if (empty($studentid) || empty($courseid)) {
        header("Location: ../viewCourse.php?id=" . $courseid . "&error=" . urlencode("Paramètres manquants"));
        exit;
    }

    $access_check = verifyAccess($link, $studentid, $courseid, $admin_id);
    if ($access_check !== true) {
        header("Location: ../viewCourse.php?id=" . $courseid . "&error=" . urlencode($access_check));
        exit;
    }

    $stmt = $link->prepare("DELETE FROM student_courses WHERE studentid = ? AND courseid = ?");
    $stmt->bind_param("ss", $studentid, $courseid);

    if ($stmt->execute()) {
        header("Location: ../viewCourse.php?id=" . $courseid . "&success=" . urlencode("Étudiant retiré avec succès"));
    } else {
        header("Location: ../viewCourse.php?id=" . $courseid . "&error=" . urlencode("Erreur lors de la suppression: " . $stmt->error));
    }
    exit;
}

// Si on arrive ici, c'est qu'aucune action valide n'a été spécifiée
header("Location: ../course.php?error=" . urlencode("Action invalide"));
exit;
?> 
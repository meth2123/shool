<?php
include_once('../main.php');

if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit;
}

$admin_id = $_SESSION['login_id'];
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    // Validation des données
    $name = trim($_POST['name'] ?? '');
    $classid = trim($_POST['classid'] ?? '');
    $teacherid = trim($_POST['teacherid'] ?? '');
    $created_by = trim($_POST['created_by'] ?? '');

    if (empty($name) || empty($classid) || empty($teacherid) || empty($created_by)) {
        header("Location: ../course.php?error=" . urlencode("Tous les champs sont obligatoires"));
        exit;
    }

    // Vérifier que l'admin est bien le propriétaire
    if ($created_by !== $admin_id) {
        header("Location: ../course.php?error=" . urlencode("Action non autorisée"));
        exit;
    }

    // Vérifier que la classe existe et appartient à cet admin
    $class_check = $link->prepare("SELECT id FROM class WHERE id = ? AND created_by = ?");
    $class_check->bind_param("ss", $classid, $admin_id);
    $class_check->execute();
    if ($class_check->get_result()->num_rows === 0) {
        header("Location: ../course.php?error=" . urlencode("Classe invalide"));
        exit;
    }

    // Vérifier que l'enseignant existe et appartient à cet admin
    $teacher_check = $link->prepare("SELECT id FROM teachers WHERE id = ? AND created_by = ?");
    $teacher_check->bind_param("ss", $teacherid, $admin_id);
    $teacher_check->execute();
    if ($teacher_check->get_result()->num_rows === 0) {
        header("Location: ../course.php?error=" . urlencode("Enseignant invalide"));
        exit;
    }

    // Insérer le cours
    $stmt = $link->prepare("INSERT INTO course (name, classid, teacherid, created_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $classid, $teacherid, $created_by);

    if ($stmt->execute()) {
        header("Location: ../course.php?success=" . urlencode("Cours ajouté avec succès"));
    } else {
        header("Location: ../course.php?error=" . urlencode("Erreur lors de l'ajout du cours: " . $stmt->error));
    }
    exit;
}

// Si on arrive ici, c'est qu'aucune action valide n'a été spécifiée
header("Location: ../course.php?error=" . urlencode("Action invalide"));
exit;
?> 
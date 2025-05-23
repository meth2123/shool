<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$class_id = $_GET['id'] ?? '';

if (empty($class_id)) {
    header("Location: manageClass.php?error=" . urlencode("ID de classe manquant"));
    exit;
}

try {
    // Vérifier si la classe existe
    $sql = "SELECT * FROM class WHERE id = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("s", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Classe non trouvée");
    }

    // Vérifier si la classe est utilisée dans d'autres tables
    $tables_to_check = [
        'course' => 'classid',
        'students' => 'classid',
        'availablecourse' => 'classid'
    ];

    foreach ($tables_to_check as $table => $field) {
        $check_sql = "SELECT COUNT(*) as count FROM $table WHERE $field = ?";
        $check_stmt = $link->prepare($check_sql);
        $check_stmt->bind_param("s", $class_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result()->fetch_assoc();
        
        if ($check_result['count'] > 0) {
            throw new Exception("Impossible de supprimer la classe car elle est utilisée dans $table");
        }
    }

    // Supprimer la classe
    $sql = "DELETE FROM class WHERE id = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("s", $class_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Erreur lors de la suppression : " . $stmt->error);
    }

    header("Location: manageClass.php?success=" . urlencode("Classe supprimée avec succès"));
    exit;

} catch (Exception $e) {
    header("Location: manageClass.php?error=" . urlencode($e->getMessage()));
    exit;
}
?> 
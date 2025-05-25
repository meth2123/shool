<?php
// Inclure le fichier principal qui gère déjà la session et les vérifications
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Initialiser les variables
$admin_id = $_SESSION['login_id'];
$duplicates_removed = 0;
$error = null;

// Traitement de la requête
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['auto'])) {
    // Étape 1: Identifier les doublons
    $find_duplicates = "
        SELECT MIN(id) as keep_id, class_id, subject_id, teacher_id, slot_id, day_of_week, semester, academic_year, COUNT(*) as count
        FROM class_schedule
        GROUP BY class_id, subject_id, teacher_id, slot_id, day_of_week, semester, academic_year
        HAVING COUNT(*) > 1
    ";
    
    $duplicates_result = $link->query($find_duplicates);
    
    if ($duplicates_result) {
        // Étape 2: Supprimer les doublons en conservant l'entrée avec l'ID le plus petit
        while ($row = $duplicates_result->fetch_assoc()) {
            $keep_id = $row['keep_id'];
            $class_id = $row['class_id'];
            $subject_id = $row['subject_id'];
            $teacher_id = $row['teacher_id'];
            $slot_id = $row['slot_id'];
            $day_of_week = $row['day_of_week'];
            $semester = $row['semester'];
            $academic_year = $row['academic_year'];
            
            $delete_query = "
                DELETE FROM class_schedule 
                WHERE 
                    id != ? AND
                    class_id = ? AND 
                    subject_id = ? AND 
                    teacher_id = ? AND 
                    slot_id = ? AND 
                    day_of_week = ? AND 
                    semester = ? AND 
                    academic_year = ?
            ";
            
            $stmt = $link->prepare($delete_query);
            $stmt->bind_param("ssssssss", $keep_id, $class_id, $subject_id, $teacher_id, $slot_id, $day_of_week, $semester, $academic_year);
            $stmt->execute();
            
            $duplicates_removed += $stmt->affected_rows;
        }
        
        // Étape 3: Vérifier si l'index unique existe et le créer s'il n'existe pas
        $index_check = $link->query("SHOW INDEX FROM class_schedule WHERE Key_name = 'unique_schedule'");
        
        if ($index_check->num_rows == 0) {
            // L'index n'existe pas, essayons de le créer
            $create_index = "
                ALTER TABLE class_schedule 
                ADD CONSTRAINT unique_schedule 
                UNIQUE (class_id, subject_id, teacher_id, slot_id, day_of_week, semester, academic_year)
            ";
            
            if (!$link->query($create_index)) {
                $error = "Impossible de créer l'index unique: " . $link->error;
            }
        }
        
        // Message de succès
        if (!$error) {
            $_SESSION['success_message'] = "$duplicates_removed emplois du temps en double ont été supprimés avec succès.";
        } else {
            $_SESSION['error_message'] = $error;
        }
    } else {
        $_SESSION['error_message'] = "Erreur lors de la recherche des doublons: " . $link->error;
    }
    
    // Redirection automatique vers la page d'emploi du temps
    header('Location: timeTable.php');
    exit;
}

// Si le script arrive ici, c'est qu'il n'y a pas eu de soumission de formulaire
// Donc on redirige automatiquement vers la page d'emploi du temps
header('Location: timeTable.php');
exit;
?>

<?php
// Fin du contenu
$content = ob_get_clean();
include('templates/layout.php');
?>

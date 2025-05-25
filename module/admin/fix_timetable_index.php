<?php
// Inclure le fichier principal qui gère déjà la session et les vérifications
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Initialiser le contenu pour le template
ob_start();

// Fonction pour afficher un message
function showMessage($message, $isError = false) {
    echo '<div style="margin: 20px; padding: 15px; border-radius: 5px; ' . 
         'background-color: ' . ($isError ? '#f8d7da' : '#d4edda') . '; ' .
         'color: ' . ($isError ? '#721c24' : '#155724') . ';">' . 
         $message . '</div>';
}

// Vérifier si la table class_schedule existe
$tableExists = $link->query("SHOW TABLES LIKE 'class_schedule'")->num_rows > 0;
if (!$tableExists) {
    showMessage("La table class_schedule n'existe pas.", true);
    exit;
}

// Vérifier si l'index unique existe déjà
$indexExists = false;
$result = $link->query("SHOW INDEX FROM class_schedule WHERE Key_name = 'unique_schedule'");
if ($result && $result->num_rows > 0) {
    $indexExists = true;
}

// Supprimer l'ancien index s'il existe
if ($indexExists) {
    $link->query("ALTER TABLE class_schedule DROP INDEX unique_schedule");
    showMessage("Ancien index supprimé.");
}

// Créer un nouvel index unique
$createIndexQuery = "ALTER TABLE class_schedule ADD CONSTRAINT unique_schedule UNIQUE (
    class_id, subject_id, teacher_id, slot_id, day_of_week, semester, academic_year
)";

if ($link->query($createIndexQuery)) {
    showMessage("Index unique créé avec succès.");
} else {
    showMessage("Erreur lors de la création de l'index unique: " . $link->error, true);
    
    // Si l'erreur est due à des doublons, proposer de les nettoyer
    if (strpos($link->error, 'Duplicate entry') !== false) {
        echo '<div style="margin: 20px;">';
        echo '<h3>Doublons détectés</h3>';
        echo '<p>Des doublons ont été détectés dans la table class_schedule. Voulez-vous les supprimer?</p>';
        echo '<form method="post">';
        echo '<input type="hidden" name="clean_duplicates" value="1">';
        echo '<button type="submit" style="padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Nettoyer les doublons</button>';
        echo '</form>';
        echo '</div>';
    }
}

// Nettoyer les doublons si demandé
if (isset($_POST['clean_duplicates'])) {
    // Trouver et supprimer les doublons
    $deleteDuplicatesQuery = "
        DELETE t1 FROM class_schedule t1
        INNER JOIN class_schedule t2 
        WHERE 
            t1.id > t2.id AND 
            t1.class_id = t2.class_id AND 
            t1.subject_id = t2.subject_id AND 
            t1.teacher_id = t2.teacher_id AND 
            t1.slot_id = t2.slot_id AND 
            t1.day_of_week = t2.day_of_week AND 
            t1.semester = t2.semester AND 
            t1.academic_year = t2.academic_year
    ";
    
    if ($link->query($deleteDuplicatesQuery)) {
        $affectedRows = $link->affected_rows;
        showMessage("$affectedRows doublons ont été supprimés avec succès.");
        
        // Essayer de créer l'index unique à nouveau
        if ($link->query($createIndexQuery)) {
            showMessage("Index unique créé avec succès après suppression des doublons.");
        } else {
            showMessage("Erreur lors de la création de l'index unique après suppression des doublons: " . $link->error, true);
        }
    } else {
        showMessage("Erreur lors de la suppression des doublons: " . $link->error, true);
    }
}

// Afficher le nombre d'emplois du temps actuels
$countQuery = "SELECT COUNT(*) as total FROM class_schedule";
$countResult = $link->query($countQuery);
$totalSchedules = $countResult->fetch_assoc()['total'];

// Afficher le nombre de doublons potentiels
$duplicatesQuery = "
    SELECT COUNT(*) as duplicates FROM (
        SELECT class_id, subject_id, teacher_id, slot_id, day_of_week, semester, academic_year, COUNT(*) as count
        FROM class_schedule
        GROUP BY class_id, subject_id, teacher_id, slot_id, day_of_week, semester, academic_year
        HAVING COUNT(*) > 1
    ) as duplicates
";
$duplicatesResult = $link->query($duplicatesQuery);
$totalDuplicates = $duplicatesResult->fetch_assoc()['duplicates'];

// Créer une interface utilisateur plus conviviale avec Bootstrap
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Correction de l'index de la table des emplois du temps</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Statistiques</h6>
                                    <p class="mb-1">Nombre total d'emplois du temps: <strong><?php echo $totalSchedules; ?></strong></p>
                                    <p class="mb-0">Groupes d'emplois du temps en double: <strong><?php echo $totalDuplicates; ?></strong></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Actions</h6>
                                    <?php if ($totalDuplicates > 0): ?>
                                    <form method="post">
                                        <input type="hidden" name="clean_duplicates" value="1">
                                        <button type="submit" class="btn btn-danger mb-2 w-100">
                                            <i class="fas fa-trash-alt me-2"></i>Nettoyer les doublons
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <a href="timeTable.php" class="btn btn-primary w-100">
                                        <i class="fas fa-arrow-left me-2"></i>Retour à l'emploi du temps
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Fin du contenu
$content = ob_get_clean();
include('templates/layout.php');
?>

<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification des droits d'administrateur
if (!isset($check) || !isset($login_session)) {
    echo '<div class="alert alert-danger" role="alert">
            <strong>Erreur!</strong> Accès non autorisé.
          </div>';
    exit();
}

$admin_id = $_SESSION['login_id'];
$loged_user_name = $check; // Pour le template layout.php

// Récupération des classes créées par cet admin
$classes = db_fetch_all(
    "SELECT id, name FROM class WHERE created_by = ? ORDER BY name",
    [$admin_id],
    's'
);

// Initialisation des variables
$selected_class = $_POST['class'] ?? '';
$selected_period = $_POST['period'] ?? '';
$current_year = date('Y');
$school_year = (date('n') >= 9) ? $current_year . '-' . ($current_year + 1) : ($current_year - 1) . '-' . $current_year;
$success_message = '';
$error_message = '';
$generated_count = 0;

// Traitement de la génération par lot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_bulletins'])) {
    // Validation des entrées
    if (empty($selected_class)) {
        $error_message = "Veuillez sélectionner une classe.";
    } elseif (empty($selected_period)) {
        $error_message = "Veuillez sélectionner une période.";
    } else {
        // Récupération des étudiants de la classe sélectionnée
        $students = db_fetch_all(
            "SELECT id, name FROM students WHERE classid = ? AND created_by = ? ORDER BY name",
            [$selected_class, $admin_id],
            'ss'
        );
        
        if (empty($students)) {
            $error_message = "Aucun étudiant trouvé dans cette classe.";
        } else {
            // Création du dossier de destination si nécessaire
            $output_dir = "../../bulletins/{$selected_class}/{$selected_period}";
            if (!is_dir($output_dir)) {
                mkdir($output_dir, 0777, true);
            }
            
            // Génération des bulletins pour chaque étudiant
            foreach ($students as $student) {
                // Ici, nous simulons la génération du PDF
                // Dans une implémentation réelle, vous appelleriez une fonction qui génère le PDF
                
                $filename = "bulletin_{$student['id']}_{$selected_period}.pdf";
                $filepath = "{$output_dir}/{$filename}";
                
                // Simulation de la création du fichier (à remplacer par la génération réelle)
                file_put_contents($filepath, "Bulletin de {$student['name']} - Période {$selected_period}");
                
                $generated_count++;
            }
            
            if ($generated_count > 0) {
                $success_message = "{$generated_count} bulletin(s) ont été générés avec succès.";
            } else {
                $error_message = "Aucun bulletin n'a pu être généré.";
            }
        }
    }
}

$content = '
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Génération par lot des Bulletins</h1>
        <div class="text-muted">
            Année scolaire : ' . htmlspecialchars($school_year) . '
        </div>
    </div>
    
    <!-- Messages -->
    ';
    if (!empty($success_message)) {
        $content .= '
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>' . htmlspecialchars($success_message) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
    
    if (!empty($error_message)) {
        $content .= '
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>' . htmlspecialchars($error_message) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
    
    $content .= '

    <!-- Formulaire de génération -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Paramètres de génération</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="class" class="form-label">Classe <span class="text-danger">*</span></label>
                        <select id="class" name="class" class="form-select" required>
                            <option value="">Sélectionner une classe</option>';
                            foreach ($classes as $class) {
                                $content .= '<option value="' . htmlspecialchars($class['id']) . '" ' . 
                                          ($selected_class === $class['id'] ? 'selected' : '') . '>' .
                                          htmlspecialchars($class['name']) . '</option>';
                            }
$content .= '
                        </select>
                        <div class="form-text">Sélectionnez la classe pour laquelle vous souhaitez générer les bulletins.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="period" class="form-label">Période <span class="text-danger">*</span></label>
                        <select id="period" name="period" class="form-select" required>
                            <option value="">Sélectionner une période</option>
                            <option value="1" ' . ($selected_period === '1' ? 'selected' : '') . '>1er Trimestre</option>
                            <option value="2" ' . ($selected_period === '2' ? 'selected' : '') . '>2ème Trimestre</option>
                            <option value="3" ' . ($selected_period === '3' ? 'selected' : '') . '>3ème Trimestre</option>
                        </select>
                        <div class="form-text">Sélectionnez la période pour laquelle vous souhaitez générer les bulletins.</div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" name="generate_bulletins" class="btn btn-primary">
                        <i class="fas fa-file-pdf me-2"></i>Générer les bulletins
                    </button>
                    <a href="manageBulletins.php" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Informations sur la génération -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Informations</h2>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                <h3 class="h6"><i class="fas fa-info-circle me-2"></i>À propos de la génération par lot</h3>
                <p class="mb-0">
                    Cette fonctionnalité vous permet de générer les bulletins pour tous les élèves d\'une classe en une seule opération.
                    Les bulletins générés seront disponibles au format PDF et stockés sur le serveur. Assurez-vous que toutes les notes
                    ont été saisies pour la période sélectionnée avant de lancer la génération.
                </p>
            </div>
        </div>
    </div>';

// Affichage des résultats de génération si des bulletins ont été générés
if ($generated_count > 0) {
    $content .= '
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Bulletins générés</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Classe</th>
                            <th>Période</th>
                            <th>Nombre de bulletins</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>';
                            
                            // Récupérer le nom de la classe
                            $class_name = '';
                            foreach ($classes as $class) {
                                if ($class['id'] === $selected_class) {
                                    $class_name = $class['name'];
                                    break;
                                }
                            }
                            
                            $content .= htmlspecialchars($class_name) . '</td>
                            <td>';
                            
                            // Afficher la période
                            $period_name = '';
                            switch ($selected_period) {
                                case '1': $period_name = '1er Trimestre'; break;
                                case '2': $period_name = '2ème Trimestre'; break;
                                case '3': $period_name = '3ème Trimestre'; break;
                            }
                            
                            $content .= htmlspecialchars($period_name) . '</td>
                            <td>' . $generated_count . '</td>
                            <td>
                                <a href="downloadBulletins.php?class=' . htmlspecialchars($selected_class) . '&period=' . htmlspecialchars($selected_period) . '" class="btn btn-sm btn-success">
                                    <i class="fas fa-download me-1"></i>Télécharger tous
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>';
}

$content .= '
</div>';

include('templates/layout.php');
?>

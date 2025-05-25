<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Vérifier que l'utilisateur est bien un administrateur
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../login.php?error=unauthorized");
    exit();
}

$admin_id = $_SESSION['login_id'];

// Fonction pour journaliser les actions
function log_action($message) {
    echo "<div class='alert alert-info'>" . htmlspecialchars($message) . "</div>";
}

// Fonction pour afficher les erreurs
function log_error($message) {
    echo "<div class='alert alert-danger'>" . htmlspecialchars($message) . "</div>";
}

// Fonction pour afficher les succès
function log_success($message) {
    echo "<div class='alert alert-success'>" . htmlspecialchars($message) . "</div>";
}

// Récupérer les doublons potentiels
function get_duplicates() {
    global $link;
    
    $sql = "SELECT 
                student_id, teacher_id, course_id, class_id, semester, grade_type, grade_number, 
                COUNT(*) as count, 
                GROUP_CONCAT(id ORDER BY id) as ids,
                GROUP_CONCAT(grade ORDER BY id) as grades
            FROM student_teacher_course 
            GROUP BY student_id, teacher_id, course_id, class_id, semester, grade_type, grade_number
            HAVING COUNT(*) > 1";
    
    $result = $link->query($sql);
    
    if (!$result) {
        log_error("Erreur lors de la recherche des doublons: " . $link->error);
        return [];
    }
    
    $duplicates = [];
    while ($row = $result->fetch_assoc()) {
        $duplicates[] = $row;
    }
    
    return $duplicates;
}

// Nettoyer les doublons
function clean_duplicates() {
    global $link;
    $duplicates = get_duplicates();
    $cleaned = 0;
    
    if (empty($duplicates)) {
        log_success("Aucun doublon trouvé dans la table student_teacher_course.");
        return 0;
    }
    
    log_action("Trouvé " . count($duplicates) . " groupes de doublons.");
    
    foreach ($duplicates as $duplicate) {
        $ids = explode(',', $duplicate['ids']);
        $grades = explode(',', $duplicate['grades']);
        
        // Garder l'ID le plus récent avec une note valide
        $keep_id = null;
        $valid_grade_found = false;
        
        // Parcourir les IDs et les notes correspondantes
        for ($i = count($ids) - 1; $i >= 0; $i--) {
            if ($grades[$i] !== 'NULL' && $grades[$i] !== 'N/A' && $grades[$i] !== '') {
                $keep_id = $ids[$i];
                $valid_grade_found = true;
                break;
            }
        }
        
        // Si aucune note valide n'a été trouvée, garder simplement l'ID le plus récent
        if (!$valid_grade_found) {
            $keep_id = $ids[count($ids) - 1];
        }
        
        // Supprimer tous les autres enregistrements
        foreach ($ids as $id) {
            if ($id != $keep_id) {
                $delete_sql = "DELETE FROM student_teacher_course WHERE id = ?";
                $stmt = $link->prepare($delete_sql);
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $cleaned++;
                } else {
                    log_error("Erreur lors de la suppression de l'enregistrement ID $id: " . $stmt->error);
                }
                
                $stmt->close();
            }
        }
    }
    
    return $cleaned;
}

// Ajouter une contrainte d'unicité
function add_unique_constraint() {
    global $link;
    
    // Vérifier si la contrainte existe déjà
    $check_sql = "SHOW INDEX FROM student_teacher_course WHERE Key_name = 'unique_student_course_teacher_class_semester_type_number'";
    $result = $link->query($check_sql);
    
    if ($result && $result->num_rows > 0) {
        log_action("La contrainte d'unicité existe déjà.");
        return true;
    }
    
    // Ajouter la contrainte
    $sql = "ALTER TABLE student_teacher_course 
            ADD CONSTRAINT unique_student_course_teacher_class_semester_type_number 
            UNIQUE (student_id, teacher_id, course_id, class_id, semester, grade_type, grade_number)";
    
    if ($link->query($sql)) {
        log_success("Contrainte d'unicité ajoutée avec succès.");
        return true;
    } else {
        log_error("Erreur lors de l'ajout de la contrainte d'unicité: " . $link->error);
        return false;
    }
}

// Traitement de la demande
$action = isset($_POST['action']) ? $_POST['action'] : '';
$cleaned = 0;
$constraint_added = false;

if ($action === 'clean') {
    $cleaned = clean_duplicates();
    
    if (isset($_POST['add_constraint']) && $_POST['add_constraint'] === 'yes') {
        $constraint_added = add_unique_constraint();
    }
}

// Récupérer à nouveau les doublons pour affichage
$duplicates = get_duplicates();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nettoyage des notes dupliquées</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h2">Nettoyage des notes dupliquées</h1>
                    <div>
                        <a href="report.php" class="btn btn-primary me-2">
                            <i class="fas fa-chart-bar me-2"></i>Rapports
                        </a>
                        <a href="index.php" class="btn btn-success">
                            <i class="fas fa-home me-2"></i>Tableau de bord
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($action === 'clean'): ?>
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Résultats du nettoyage</h5>
                </div>
                <div class="card-body">
                    <?php if ($cleaned > 0): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $cleaned; ?> enregistrements dupliqués ont été supprimés avec succès.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Aucun enregistrement n'a été supprimé.
                        </div>
                    <?php endif; ?>

                    <?php if ($constraint_added): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Une contrainte d'unicité a été ajoutée pour éviter les futures duplications.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Actions disponibles</h5>
            </div>
            <div class="card-body">
                <form method="post" class="mb-3">
                    <input type="hidden" name="action" value="clean">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="add_constraint" value="yes" id="add_constraint">
                        <label class="form-check-label" for="add_constraint">
                            Ajouter une contrainte d'unicité pour éviter les futures duplications
                        </label>
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-broom me-2"></i>Nettoyer les notes dupliquées
                    </button>
                </form>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Attention :</strong> Cette opération supprimera les enregistrements dupliqués en conservant uniquement la note la plus récente ou la note valide. Cette action est irréversible.
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Notes dupliquées détectées</h5>
                <span class="badge bg-<?php echo count($duplicates) > 0 ? 'danger' : 'success'; ?> rounded-pill">
                    <?php echo count($duplicates); ?> groupe(s) de doublons
                </span>
            </div>
            <div class="card-body p-0">
                <?php if (count($duplicates) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Étudiant</th>
                                    <th>Enseignant</th>
                                    <th>Cours</th>
                                    <th>Classe</th>
                                    <th>Semestre</th>
                                    <th>Type</th>
                                    <th>Numéro</th>
                                    <th>Nombre</th>
                                    <th>IDs</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($duplicates as $duplicate): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($duplicate['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($duplicate['teacher_id']); ?></td>
                                        <td><?php echo htmlspecialchars($duplicate['course_id']); ?></td>
                                        <td><?php echo htmlspecialchars($duplicate['class_id']); ?></td>
                                        <td><?php echo htmlspecialchars($duplicate['semester']); ?></td>
                                        <td><?php echo htmlspecialchars($duplicate['grade_type'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($duplicate['grade_number'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($duplicate['count']); ?></td>
                                        <td><code><?php echo htmlspecialchars($duplicate['ids']); ?></code></td>
                                        <td><code><?php echo htmlspecialchars($duplicate['grades']); ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success m-3">
                        <i class="fas fa-check-circle me-2"></i>
                        Aucun doublon détecté dans la table student_teacher_course.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

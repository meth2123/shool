<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Vérifier que l'utilisateur est bien un administrateur
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../login.php?error=unauthorized");
    exit();
}

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

// Récupérer les collations actuelles
function get_table_collations($link) {
    $tables = [];
    $sql = "SELECT TABLE_NAME, TABLE_COLLATION 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE()";
    
    $result = $link->query($sql);
    
    if (!$result) {
        log_error("Erreur lors de la récupération des collations: " . $link->error);
        return [];
    }
    
    while ($row = $result->fetch_assoc()) {
        $tables[$row['TABLE_NAME']] = $row['TABLE_COLLATION'];
    }
    
    return $tables;
}

// Récupérer les collations des colonnes
function get_column_collations($link, $table) {
    $columns = [];
    $sql = "SHOW FULL COLUMNS FROM `$table`";
    
    $result = $link->query($sql);
    
    if (!$result) {
        log_error("Erreur lors de la récupération des colonnes pour $table: " . $link->error);
        return [];
    }
    
    while ($row = $result->fetch_assoc()) {
        if ($row['Collation'] !== null) {
            $columns[$row['Field']] = $row['Collation'];
        }
    }
    
    return $columns;
}

// Modifier la collation d'une table
function fix_table_collation($link, $table, $collation) {
    $sql = "ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE $collation";
    
    if ($link->query($sql)) {
        log_success("Table $table convertie en $collation");
        return true;
    } else {
        log_error("Erreur lors de la conversion de $table: " . $link->error);
        return false;
    }
}

// Modifier la collation d'une colonne spécifique
function fix_column_collation($link, $table, $column, $collation) {
    // Récupérer le type de la colonne
    $sql = "SHOW COLUMNS FROM `$table` WHERE Field = '$column'";
    $result = $link->query($sql);
    
    if (!$result || $result->num_rows === 0) {
        log_error("Colonne $column non trouvée dans $table");
        return false;
    }
    
    $row = $result->fetch_assoc();
    $type = $row['Type'];
    
    // Modifier la collation de la colonne
    $sql = "ALTER TABLE `$table` MODIFY `$column` $type CHARACTER SET utf8mb4 COLLATE $collation";
    
    if ($link->query($sql)) {
        log_success("Colonne $table.$column convertie en $collation");
        return true;
    } else {
        log_error("Erreur lors de la conversion de $table.$column: " . $link->error);
        return false;
    }
}

// Traitement de la demande
$action = isset($_POST['action']) ? $_POST['action'] : '';
$target_collation = isset($_POST['collation']) ? $_POST['collation'] : 'utf8mb4_unicode_ci';
$tables_fixed = 0;
$columns_fixed = 0;

if ($action === 'fix_all') {
    // Récupérer toutes les tables
    $tables = get_table_collations($link);
    
    foreach ($tables as $table => $current_collation) {
        if ($current_collation !== $target_collation) {
            if (fix_table_collation($link, $table, $target_collation)) {
                $tables_fixed++;
            }
        } else {
            log_action("Table $table déjà en $target_collation");
        }
    }
} elseif ($action === 'fix_specific') {
    $table = isset($_POST['table']) ? $_POST['table'] : '';
    $column = isset($_POST['column']) ? $_POST['column'] : '';
    
    if (!empty($table) && !empty($column)) {
        fix_column_collation($link, $table, $column, $target_collation);
        $columns_fixed++;
    } elseif (!empty($table)) {
        if (fix_table_collation($link, $table, $target_collation)) {
            $tables_fixed++;
        }
    }
}

// Récupérer les collations actuelles pour affichage
$tables = get_table_collations($link);

// Récupérer les tables avec des collations différentes
$problematic_tables = [];
foreach ($tables as $table => $collation) {
    if ($collation !== $target_collation) {
        $problematic_tables[$table] = $collation;
    }
}

// Récupérer les colonnes avec des collations différentes
$problematic_columns = [];
foreach ($tables as $table => $collation) {
    $columns = get_column_collations($link, $table);
    
    foreach ($columns as $column => $col_collation) {
        if ($col_collation !== $target_collation) {
            if (!isset($problematic_columns[$table])) {
                $problematic_columns[$table] = [];
            }
            $problematic_columns[$table][$column] = $col_collation;
        }
    }
}

// Vérifier si le problème spécifique de student_teacher_course existe
$specific_issues = [];

// Vérifier la collation entre student_teacher_course.class_id et class.id
$check_sql = "SELECT c.TABLE_NAME as table1, c.COLUMN_NAME as column1, c.COLLATION_NAME as collation1,
                     c2.TABLE_NAME as table2, c2.COLUMN_NAME as column2, c2.COLLATION_NAME as collation2
              FROM information_schema.COLUMNS c
              JOIN information_schema.COLUMNS c2
              WHERE c.TABLE_SCHEMA = DATABASE() AND c2.TABLE_SCHEMA = DATABASE()
              AND c.TABLE_NAME = 'student_teacher_course' AND c.COLUMN_NAME = 'class_id'
              AND c2.TABLE_NAME = 'class' AND c2.COLUMN_NAME = 'id'";

$result = $link->query($check_sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['collation1'] !== $row['collation2']) {
        $specific_issues[] = [
            'table1' => $row['table1'],
            'column1' => $row['column1'],
            'collation1' => $row['collation1'],
            'table2' => $row['table2'],
            'column2' => $row['column2'],
            'collation2' => $row['collation2']
        ];
    }
}

// Vérifier la collation entre student_teacher_course.teacher_id et teachers.id
$check_sql = "SELECT c.TABLE_NAME as table1, c.COLUMN_NAME as column1, c.COLLATION_NAME as collation1,
                     c2.TABLE_NAME as table2, c2.COLUMN_NAME as column2, c2.COLLATION_NAME as collation2
              FROM information_schema.COLUMNS c
              JOIN information_schema.COLUMNS c2
              WHERE c.TABLE_SCHEMA = DATABASE() AND c2.TABLE_SCHEMA = DATABASE()
              AND c.TABLE_NAME = 'student_teacher_course' AND c.COLUMN_NAME = 'teacher_id'
              AND c2.TABLE_NAME = 'teachers' AND c2.COLUMN_NAME = 'id'";

$result = $link->query($check_sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['collation1'] !== $row['collation2']) {
        $specific_issues[] = [
            'table1' => $row['table1'],
            'column1' => $row['column1'],
            'collation1' => $row['collation1'],
            'table2' => $row['table2'],
            'column2' => $row['column2'],
            'collation2' => $row['collation2']
        ];
    }
}

// Vérifier la collation entre student_teacher_course.student_id et students.id
$check_sql = "SELECT c.TABLE_NAME as table1, c.COLUMN_NAME as column1, c.COLLATION_NAME as collation1,
                     c2.TABLE_NAME as table2, c2.COLUMN_NAME as column2, c2.COLLATION_NAME as collation2
              FROM information_schema.COLUMNS c
              JOIN information_schema.COLUMNS c2
              WHERE c.TABLE_SCHEMA = DATABASE() AND c2.TABLE_SCHEMA = DATABASE()
              AND c.TABLE_NAME = 'student_teacher_course' AND c.COLUMN_NAME = 'student_id'
              AND c2.TABLE_NAME = 'students' AND c2.COLUMN_NAME = 'id'";

$result = $link->query($check_sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['collation1'] !== $row['collation2']) {
        $specific_issues[] = [
            'table1' => $row['table1'],
            'column1' => $row['column1'],
            'collation1' => $row['collation1'],
            'table2' => $row['table2'],
            'column2' => $row['column2'],
            'collation2' => $row['collation2']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correction des Collations</title>
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
                    <h1 class="h2">Correction des Collations de la Base de Données</h1>
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

        <?php if ($action === 'fix_all' || $action === 'fix_specific'): ?>
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Résultats de la correction</h5>
                </div>
                <div class="card-body">
                    <?php if ($tables_fixed > 0 || $columns_fixed > 0): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php if ($tables_fixed > 0): ?>
                                <?php echo $tables_fixed; ?> table(s) ont été corrigées.
                            <?php endif; ?>
                            <?php if ($columns_fixed > 0): ?>
                                <?php echo $columns_fixed; ?> colonne(s) ont été corrigées.
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Aucune modification n'a été effectuée.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Problèmes spécifiques détectés -->
        <?php if (!empty($specific_issues)): ?>
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Problèmes spécifiques détectés</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Attention :</strong> Les problèmes suivants peuvent causer des erreurs de jointure dans les requêtes SQL.
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Table 1</th>
                                    <th>Colonne 1</th>
                                    <th>Collation 1</th>
                                    <th>Table 2</th>
                                    <th>Colonne 2</th>
                                    <th>Collation 2</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($specific_issues as $issue): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($issue['table1']); ?></td>
                                        <td><?php echo htmlspecialchars($issue['column1']); ?></td>
                                        <td><code><?php echo htmlspecialchars($issue['collation1']); ?></code></td>
                                        <td><?php echo htmlspecialchars($issue['table2']); ?></td>
                                        <td><?php echo htmlspecialchars($issue['column2']); ?></td>
                                        <td><code><?php echo htmlspecialchars($issue['collation2']); ?></code></td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="action" value="fix_specific">
                                                <input type="hidden" name="table" value="<?php echo htmlspecialchars($issue['table1']); ?>">
                                                <input type="hidden" name="column" value="<?php echo htmlspecialchars($issue['column1']); ?>">
                                                <input type="hidden" name="collation" value="<?php echo htmlspecialchars($issue['collation2']); ?>">
                                                <button type="submit" class="btn btn-sm btn-warning">
                                                    Corriger <?php echo htmlspecialchars($issue['table1']); ?>.<?php echo htmlspecialchars($issue['column1']); ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Actions disponibles</h5>
            </div>
            <div class="card-body">
                <form method="post" class="mb-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="collation" class="form-label">Collation cible</label>
                            <select class="form-select" name="collation" id="collation">
                                <option value="utf8mb4_unicode_ci" <?php echo $target_collation === 'utf8mb4_unicode_ci' ? 'selected' : ''; ?>>utf8mb4_unicode_ci</option>
                                <option value="utf8mb4_0900_ai_ci" <?php echo $target_collation === 'utf8mb4_0900_ai_ci' ? 'selected' : ''; ?>>utf8mb4_0900_ai_ci</option>
                                <option value="utf8mb4_general_ci" <?php echo $target_collation === 'utf8mb4_general_ci' ? 'selected' : ''; ?>>utf8mb4_general_ci</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <input type="hidden" name="action" value="fix_all">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-magic me-2"></i>Corriger toutes les tables
                            </button>
                        </div>
                    </div>
                </form>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Attention :</strong> Cette opération modifiera la collation de toutes les tables et colonnes. Assurez-vous d'avoir une sauvegarde de votre base de données avant de continuer.
                </div>
            </div>
        </div>

        <!-- Tables problématiques -->
        <?php if (!empty($problematic_tables)): ?>
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Tables avec des collations différentes</h5>
                    <span class="badge bg-warning rounded-pill">
                        <?php echo count($problematic_tables); ?> table(s)
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Table</th>
                                    <th>Collation actuelle</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($problematic_tables as $table => $collation): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($table); ?></td>
                                        <td><code><?php echo htmlspecialchars($collation); ?></code></td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="action" value="fix_specific">
                                                <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>">
                                                <input type="hidden" name="collation" value="<?php echo htmlspecialchars($target_collation); ?>">
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    Corriger cette table
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Colonnes problématiques -->
        <?php if (!empty($problematic_columns)): ?>
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Colonnes avec des collations différentes</h5>
                    <span class="badge bg-warning rounded-pill">
                        <?php 
                        $total_columns = 0;
                        foreach ($problematic_columns as $table => $columns) {
                            $total_columns += count($columns);
                        }
                        echo $total_columns; ?> colonne(s)
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Table</th>
                                    <th>Colonne</th>
                                    <th>Collation actuelle</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($problematic_columns as $table => $columns): ?>
                                    <?php foreach ($columns as $column => $collation): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($table); ?></td>
                                            <td><?php echo htmlspecialchars($column); ?></td>
                                            <td><code><?php echo htmlspecialchars($collation); ?></code></td>
                                            <td>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="fix_specific">
                                                    <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>">
                                                    <input type="hidden" name="column" value="<?php echo htmlspecialchars($column); ?>">
                                                    <input type="hidden" name="collation" value="<?php echo htmlspecialchars($target_collation); ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        Corriger cette colonne
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

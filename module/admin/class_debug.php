<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Vérifier que l'utilisateur est bien un administrateur
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../login.php?error=unauthorized");
    exit();
}

$admin_id = $_SESSION['login_id'];
$class_id = isset($_GET['class_id']) ? $_GET['class_id'] : 'CLS-CI-A-218';

// Récupérer les données brutes de student_teacher_course pour cette classe sans jointures
$sql = "SELECT * FROM student_teacher_course WHERE class_id = '$class_id'";
$result = $link->query($sql);

// Afficher les tables disponibles
$tables_sql = "SHOW TABLES";
$tables_result = $link->query($tables_sql);
$tables = [];
while ($table = $tables_result->fetch_row()) {
    $tables[] = $table[0];
}

// Afficher la structure de la table student_teacher_course
$structure_sql = "DESCRIBE student_teacher_course";
$structure_result = $link->query($structure_sql);
$structure = [];
while ($field = $structure_result->fetch_assoc()) {
    $structure[] = $field;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic de Classe - <?php echo htmlspecialchars($class_id); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sql-query {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: monospace;
        }
        .null-value {
            color: #6c757d;
            font-style: italic;
        }
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 20px;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <h1 class="h2 mb-3 mb-md-0">Diagnostic de la classe: <?php echo htmlspecialchars($class_id); ?></h1>
            <div class="d-flex gap-2">
                <a href="report.php" class="btn btn-primary"><i class="fas fa-chart-bar me-2"></i>Retour au rapport</a>
                <a href="index.php" class="btn btn-success"><i class="fas fa-home me-2"></i>Tableau de bord</a>
            </div>
        </div>

        <!-- Sélection de classe -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Sélectionner une classe</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <input type="text" class="form-control" name="class_id" value="<?php echo htmlspecialchars($class_id); ?>" placeholder="Entrez l'ID de la classe">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search me-2"></i>Afficher les données</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Requête SQL utilisée -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Requête SQL utilisée</h5>
            </div>
            <div class="card-body p-0">
                <div class="sql-query">
                    <code><?php echo htmlspecialchars($sql); ?></code>
                </div>
            </div>
        </div>

        <!-- Données brutes de student_teacher_course -->
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Données de la table student_teacher_course</h5>
                <span class="badge bg-primary rounded-pill">Nombre d'enregistrements: <?php echo $result->num_rows; ?></span>
            </div>
            <div class="card-body p-0">
                <?php if ($result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Élève (student_id)</th>
                                    <th>Enseignant (teacher_id)</th>
                                    <th>Cours (course_id)</th>
                                    <th>Classe (class_id)</th>
                                    <th>Note (grade)</th>
                                    <th>Créé par</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Réinitialiser le pointeur de résultat
                                $result->data_seek(0);
                                while ($row = $result->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['student_id'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['teacher_id'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['course_id'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['class_id'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if (isset($row['grade'])): ?>
                                                <?php if ($row['grade'] === null): ?>
                                                    <span class="null-value">NULL</span>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($row['grade']); ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="null-value">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['created_by'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger m-3">
                        <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Aucune donnée trouvée</h5>
                        <p class="mb-0">Aucune donnée trouvée pour cette classe dans la table student_teacher_course.</p>
                        <p class="mb-0">Vérifiez que l'ID de classe est correct et que des données existent pour cette classe.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Structure de la table -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Structure de la table student_teacher_course</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Champ</th>
                                <th>Type</th>
                                <th>Null</th>
                                <th>Clé</th>
                                <th>Défaut</th>
                                <th>Extra</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($structure as $field): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($field['Field']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($field['Type']); ?></code></td>
                                    <td><?php echo htmlspecialchars($field['Null']); ?></td>
                                    <td>
                                        <?php if ($field['Key'] === 'PRI'): ?>
                                            <span class="badge bg-danger">PRI</span>
                                        <?php elseif ($field['Key'] === 'MUL'): ?>
                                            <span class="badge bg-primary">MUL</span>
                                        <?php elseif ($field['Key'] === 'UNI'): ?>
                                            <span class="badge bg-info">UNI</span>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($field['Key']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($field['Default'] === null): ?>
                                            <span class="null-value">NULL</span>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($field['Default']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($field['Extra']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tables disponibles -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Tables disponibles dans la base de données</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($tables as $table): ?>
                        <div class="col-md-3 col-sm-4 col-6 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-table text-primary me-2"></i>
                                <span><?php echo htmlspecialchars($table); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

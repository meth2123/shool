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

// Fonction pour afficher les résultats d'une requête
function display_query_results($link, $sql, $params = [], $param_types = '') {
    echo "<div class='card mb-4'>";
    echo "<div class='card-header bg-white'>";
    echo "<h5 class='card-title mb-0'>Requête SQL</h5>";
    echo "</div>";
    echo "<div class='card-body'>";
    echo "<pre class='bg-light p-3 mb-3'>" . htmlspecialchars($sql) . "</pre>";
    
    if (!empty($params)) {
        echo "<h6>Paramètres:</h6>";
        echo "<pre class='bg-light p-3 mb-3'>" . htmlspecialchars(print_r($params, true)) . "</pre>";
    }
    
    if ($param_types && !empty($params)) {
        $stmt = $link->prepare($sql);
        
        // Créer un tableau de références pour bind_param
        $bind_params = array($param_types);
        foreach ($params as $key => $value) {
            $bind_params[] = &$params[$key];
        }
        
        call_user_func_array(array($stmt, 'bind_param'), $bind_params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $link->query($sql);
    }
    
    if (!$result) {
        echo "<div class='alert alert-danger'>Erreur SQL: " . htmlspecialchars($link->error) . "</div>";
        echo "</div></div>";
        return;
    }
    
    echo "<h6>Nombre de résultats: " . $result->num_rows . "</h6>";
    
    if ($result->num_rows > 0) {
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped table-hover'>";
        
        // En-têtes de colonnes
        echo "<thead><tr>";
        $fields = $result->fetch_fields();
        foreach ($fields as $field) {
            echo "<th>" . htmlspecialchars($field->name) . "</th>";
        }
        echo "</tr></thead>";
        
        // Données
        echo "<tbody>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>";
                if ($value === null) {
                    echo "<span class='text-muted fst-italic'>NULL</span>";
                } else {
                    echo htmlspecialchars($value);
                }
                echo "</td>";
            }
            echo "</tr>";
        }
        echo "</tbody>";
        
        echo "</table>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>Aucun résultat trouvé.</div>";
    }
    
    echo "</div></div>";
}

// Vérifier les tables et les relations
$tables = [
    'teachers' => "SELECT * FROM teachers WHERE created_by = '$admin_id' LIMIT 10",
    'availablecourse' => "SELECT * FROM availablecourse LIMIT 10",
    'takencoursebyteacher' => "SELECT * FROM takencoursebyteacher LIMIT 10",
    'student_teacher_course' => "SELECT * FROM student_teacher_course WHERE class_id = '$class_id' LIMIT 10",
    'class' => "SELECT * FROM class WHERE id = '$class_id'"
];

// Vérifier les jointures
$join_queries = [
    'teacher_course' => "SELECT t.id as teacher_id, t.name as teacher_name, ac.id as course_id, ac.name as course_name
                         FROM teachers t
                         JOIN takencoursebyteacher tc ON t.id = tc.teacherid
                         JOIN availablecourse ac ON ac.id = tc.courseid
                         WHERE t.created_by = '$admin_id'
                         LIMIT 10",
    'student_teacher_course_join' => "SELECT t.id as teacher_id, t.name as teacher_name, 
                                     ac.id as course_id, ac.name as course_name,
                                     c.id as class_id, c.name as class_name,
                                     stc.student_id, stc.grade
                                     FROM teachers t
                                     JOIN takencoursebyteacher tc ON t.id = tc.teacherid
                                     JOIN availablecourse ac ON ac.id = tc.courseid
                                     JOIN student_teacher_course stc ON t.id = stc.teacher_id AND ac.id = stc.course_id
                                     JOIN class c ON stc.class_id = c.id
                                     WHERE t.created_by = '$admin_id'
                                     AND stc.class_id = '$class_id'
                                     LIMIT 10"
];

// Requête du rapport
$report_sql = "SELECT 
    t.id as teacher_id,
    t.name as teacher,
    ac.id as course_id,
    ac.name as course,
    c.name as class_name,
    COUNT(DISTINCT stc.student_id) as no_of_std,
    MIN(CASE WHEN stc.grade IS NOT NULL THEN stc.grade ELSE NULL END) as min_grade,
    MAX(CASE WHEN stc.grade IS NOT NULL THEN stc.grade ELSE NULL END) as max_grade,
    AVG(CASE WHEN stc.grade IS NOT NULL THEN stc.grade ELSE NULL END) as avg_grade
FROM teachers t
JOIN takencoursebyteacher tc ON t.id = tc.teacherid
JOIN availablecourse ac ON ac.id = tc.courseid
JOIN student_teacher_course stc ON t.id = stc.teacher_id AND ac.id = stc.course_id
JOIN class c ON stc.class_id = c.id
WHERE t.created_by = '$admin_id'
AND EXISTS (SELECT 1 FROM teachers t2 WHERE t2.id = t.id AND t2.created_by = '$admin_id')";

if ($class_id) {
    $report_sql .= " AND stc.class_id = '$class_id'";
}

$report_sql .= " GROUP BY ac.id, t.id ORDER BY no_of_std DESC, avg_grade ASC";

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic du Rapport - <?php echo htmlspecialchars($class_id); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Diagnostic du Rapport pour la classe <?php echo htmlspecialchars($class_id); ?></h1>
            <div>
                <a href="report.php" class="btn btn-primary me-2">
                    <i class="fas fa-chart-bar me-2"></i>Rapports
                </a>
                <a href="index.php" class="btn btn-success">
                    <i class="fas fa-home me-2"></i>Tableau de bord
                </a>
            </div>
        </div>
        
        <!-- Sélection de classe -->
        <div class="card mb-4">
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
        
        <!-- Requête du rapport -->
        <?php display_query_results($link, $report_sql); ?>
        
        <!-- Vérification des jointures -->
        <h2 class="h4 mb-3 mt-5">Vérification des jointures</h2>
        <?php foreach ($join_queries as $name => $sql): ?>
            <h3 class="h5 mb-2"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $name))); ?></h3>
            <?php display_query_results($link, $sql); ?>
        <?php endforeach; ?>
        
        <!-- Vérification des tables individuelles -->
        <h2 class="h4 mb-3 mt-5">Vérification des tables individuelles</h2>
        <?php foreach ($tables as $name => $sql): ?>
            <h3 class="h5 mb-2">Table <?php echo htmlspecialchars($name); ?></h3>
            <?php display_query_results($link, $sql); ?>
        <?php endforeach; ?>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

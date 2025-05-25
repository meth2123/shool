<?php 
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification de la session
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Définir la variable check pour le template layout.php
$check = $_SESSION['login_id'];
$teacher_id = $check;

// Récupérer les classes du professeur
$classes_query = "SELECT DISTINCT cl.id, cl.name 
                 FROM class cl 
                 JOIN course c ON cl.id = c.classid 
                 WHERE c.teacherid = ?";
$classes = db_fetch_all($classes_query, [$teacher_id], 's');

// Récupérer les paramètres de filtrage
$selected_class = $_GET['class_id'] ?? '';
$sort_by = $_GET['sort'] ?? 'date';
$filter_status = $_GET['status'] ?? 'all';

// Requête pour les devoirs (créés par le professeur)
$assignments_query = "SELECT DISTINCT 
    e.id,
    c.name as course_name,
    c.id as course_id,
    e.examdate,
    e.time,
    cl.name as class_name,
    cl.id as class_id,
    e.title,
    e.description,
    e.created_by,
    CASE 
        WHEN e.examdate < CURDATE() THEN 'past'
        WHEN e.examdate = CURDATE() THEN 'today'
        ELSE 'upcoming'
    END as status,
    DATE_FORMAT(e.examdate, '%d/%m/%Y') as formatted_date,
    TIME_FORMAT(e.time, '%H:%i') as formatted_time
FROM course c
JOIN examschedule e ON c.id = e.courseid
JOIN class cl ON c.classid = cl.id
WHERE c.teacherid = ? AND e.created_by = ?";

// Requête pour les examens (créés par l'admin)
$exams_query = "SELECT DISTINCT 
    e.id,
    c.name as course_name,
    c.id as course_id,
    e.examdate,
    e.time,
    cl.name as class_name,
    cl.id as class_id,
    e.title,
    e.description,
    e.created_by,
    CASE 
        WHEN e.examdate < CURDATE() THEN 'past'
        WHEN e.examdate = CURDATE() THEN 'today'
        ELSE 'upcoming'
    END as status,
    DATE_FORMAT(e.examdate, '%d/%m/%Y') as formatted_date,
    TIME_FORMAT(e.time, '%H:%i') as formatted_time
FROM course c
JOIN examschedule e ON c.id = e.courseid
JOIN class cl ON c.classid = cl.id
WHERE c.teacherid = ? 
AND e.created_by IN ('admin', 'ad-123-1')";

// Exécuter les requêtes
$assignments = db_fetch_all($assignments_query, [$teacher_id, $teacher_id], 'ss');
$exams = db_fetch_all($exams_query, [$teacher_id], 's');

if ($assignments === false || $exams === false) {
    error_log("Erreur lors de la récupération des données");
    die("Erreur lors de la récupération des données");
}

// Compter les examens par statut
$stats = [
    'all' => count($exams),
    'upcoming' => 0,
    'today' => 0,
    'past' => 0
];

foreach ($exams as $exam) {
    $stats[$exam['status']]++;
}

// Récupérer les cours de la classe sélectionnée
$courses = [];
if ($selected_class) {
    $courses = db_fetch_all(
        "SELECT id, name FROM course WHERE classid = ? AND teacherid = ?",
        [$selected_class, $teacher_id],
        'ss'
    );
}

// Traitement du formulaire d'ajout de devoir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_assignment'])) {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $due_time = $_POST['due_time'];
    
    // Vérifier que le cours appartient bien au professeur
    $check_course_query = "SELECT id, teacherid, classid FROM course WHERE id = ? AND teacherid = ?";
    $check_course = db_fetch_row($check_course_query, [$course_id, $teacher_id], 'ss');
    
    if ($check_course) {
        // Générer un ID unique pour le devoir
        $assignment_id = 'ASS-' . uniqid();
        
        $insert_query = "INSERT INTO examschedule (id, courseid, title, description, examdate, time, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $assignment_id,
            $course_id,
            $title,
            $description,
            $due_date,
            $due_time,
            $teacher_id
        ];
        
        $result = db_query($insert_query, $params, 'sssssss');
        
        if (!$result) {
            error_log("Erreur SQL: " . db_error());
        }
        
        if ($result) {
            header("Location: exam.php?class_id=" . $selected_class . "&success=1");
        } else {
            header("Location: exam.php?class_id=" . $selected_class . "&error=insert_failed");
        }
        exit();
    } else {
        header("Location: exam.php?class_id=" . $selected_class . "&error=unauthorized");
        exit();
    }
}

// Préparation du contenu pour le template
$content = '';

// Messages d'alerte
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $content .= '<div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> Le devoir a été ajouté avec succès.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

if (isset($_GET['error'])) {
    $error_messages = [
        'insert_failed' => 'Une erreur est survenue lors de l\'ajout du devoir.',
        'unauthorized' => 'Vous n\'avez pas l\'autorisation d\'ajouter un devoir pour ce cours.',
    ];
    
    $error_message = $error_messages[$_GET['error']] ?? 'Une erreur est survenue.';
    
    $content .= '<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> ' . $error_message . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}
$content .= '<!-- Sélection de la classe -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Sélectionner une classe</h5>
    </div>
    <div class="card-body">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">';

if (empty($classes)) {
    $content .= '<div class="col-12">
        <div class="alert alert-info mb-0">
            <i class="fas fa-info-circle me-2"></i>Aucune classe assignée.
        </div>
    </div>';
} else {
    foreach ($classes as $class) {
        $isActive = $selected_class === $class["id"];
        $content .= '<div class="col">
            <a href="?class_id=' . htmlspecialchars($class["id"]) . '" class="text-decoration-none">
                <div class="card h-100 ' . ($isActive ? "border-primary" : "border-light") . ' hover-shadow">
                    <div class="card-body">
                        <h5 class="card-title">' . htmlspecialchars($class["name"]) . '</h5>
                    </div>
                    ' . ($isActive ? '<div class="card-footer bg-primary bg-opacity-10 border-top-0 text-primary"><small><i class="fas fa-check-circle me-1"></i>Sélectionnée</small></div>' : '') . '
                </div>
            </a>
        </div>';
    }
}

$content .= '</div>
    </div>
</div>';


if ($selected_class) {
    // Informations de la classe sélectionnée
    $class_info = db_fetch_row(
        "SELECT * FROM class WHERE id = ?",
        [$selected_class],
        's'
    );
    
    if ($class_info) {
        $content .= '<div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0">Classe: <span class="text-primary">' . htmlspecialchars($class_info['name']) . '</span></h2>
            <a href="exam.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Toutes les classes</a>
        </div>';
    }
    
    // Formulaire d'ajout de devoir
    $content .= '<div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Ajouter un devoir</h5>
            <span class="badge bg-primary">' . count($courses) . ' cours disponibles</span>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="add_assignment" value="1">
                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="course_id" class="form-label">Cours</label>
                        <select name="course_id" id="course_id" class="form-select" required>
                            <option value="">Sélectionner un cours</option>';
                            
    foreach ($courses as $course) {
        $content .= '<option value="' . htmlspecialchars($course['id']) . '">' . htmlspecialchars($course['name']) . '</option>';
    }
                            
    $content .= '</select>
                    </div>
                    <div class="col-md-6">
                        <label for="title" class="form-label">Titre du devoir</label>
                        <input type="text" name="title" id="title" class="form-control" required placeholder="Ex: Devoir sur les fonctions">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" rows="3" class="form-control" required placeholder="Description détaillée du devoir..."></textarea>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="due_date" class="form-label">Date limite</label>
                        <input type="date" name="due_date" id="due_date" class="form-control" required min="' . date('Y-m-d') . '">
                    </div>
                    <div class="col-md-6">
                        <label for="due_time" class="form-label">Heure limite</label>
                        <input type="time" name="due_time" id="due_time" class="form-control" required>
                    </div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Ajouter le devoir
                    </button>
                </div>
            </form>
        </div>
    </div>';
    
    // Section des devoirs
    $content .= '<div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Mes devoirs</h5>
            ' . (!empty($assignments) ? '<span class="badge bg-success">' . count($assignments) . ' devoirs</span>' : '') . '
        </div>
        <div class="card-body">';
    
    if (!empty($assignments)) {
        $content .= '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">';
        
        foreach ($assignments as $assignment) {
            $status_map = [
                'upcoming' => ['class' => 'bg-success', 'text' => 'À venir', 'icon' => 'fa-calendar-plus'],
                'today' => ['class' => 'bg-warning', 'text' => 'Aujourd\'hui', 'icon' => 'fa-calendar-day'],
                'past' => ['class' => 'bg-secondary', 'text' => 'Passé', 'icon' => 'fa-calendar-check']
            ];
            
            $status = $status_map[$assignment['status']];
            
            $content .= '<div class="col">
                <div class="card h-100 border-light hover-shadow">
                    <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title mb-1">' . htmlspecialchars($assignment['course_name']) . '</h5>
                            ' . (!empty($assignment['title']) ? '<h6 class="text-primary mb-0">' . htmlspecialchars($assignment['title']) . '</h6>' : '') . '
                        </div>
                        <span class="badge ' . $status['class'] . ' rounded-pill">
                            <i class="fas ' . $status['icon'] . ' me-1"></i>' . $status['text'] . '
                        </span>
                    </div>
                    <div class="card-body">
                        ' . (!empty($assignment['description']) ? '<p class="card-text small">' . nl2br(htmlspecialchars($assignment['description'])) . '</p>' : '') . '
                        <div class="d-flex justify-content-between mt-3 small text-muted">
                            <div><i class="fas fa-calendar me-1"></i> ' . $assignment['formatted_date'] . '</div>
                            <div><i class="fas fa-clock me-1"></i> ' . $assignment['formatted_time'] . '</div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top-0 d-flex ' . ($assignment['status'] === 'upcoming' && $assignment['created_by'] === $teacher_id ? 'justify-content-between' : 'justify-content-end') . '">
                        <a href="view_exam.php?course_id=' . htmlspecialchars($assignment['course_id']) . '" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye me-1"></i> Détails
                        </a>
                        ' . ($assignment['status'] === 'upcoming' && $assignment['created_by'] === $teacher_id ? '<button onclick="deleteAssignment(\'' . $assignment['id'] . '\')" class="btn btn-sm btn-danger"><i class="fas fa-trash me-1"></i> Supprimer</button>' : '') . '
                    </div>
                </div>
            </div>';
        }
        
        $content .= '</div>';
    } else {
        $content .= '<div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>Aucun devoir programmé pour cette classe.
        </div>';
    }
    
    $content .= '</div>
    </div>';
    
    // Section des examens
    $content .= '<div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Examens programmés</h5>
            ' . (!empty($exams) ? '<span class="badge bg-info">' . count($exams) . ' examens</span>' : '') . '
        </div>
        <div class="card-body">';
    
    if (!empty($exams)) {
        $content .= '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">';
        
        foreach ($exams as $exam) {
            $status_map = [
                'upcoming' => ['class' => 'bg-info', 'text' => 'À venir', 'icon' => 'fa-calendar-plus'],
                'today' => ['class' => 'bg-warning', 'text' => 'Aujourd\'hui', 'icon' => 'fa-calendar-day'],
                'past' => ['class' => 'bg-secondary', 'text' => 'Passé', 'icon' => 'fa-calendar-check']
            ];
            
            $status = $status_map[$exam['status']];
            
            $content .= '<div class="col">
                <div class="card h-100 border-light hover-shadow">
                    <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title mb-1">' . htmlspecialchars($exam['course_name']) . '</h5>
                            ' . (!empty($exam['title']) ? '<h6 class="text-primary mb-0">' . htmlspecialchars($exam['title']) . '</h6>' : '') . '
                        </div>
                        <span class="badge ' . $status['class'] . ' rounded-pill">
                            <i class="fas ' . $status['icon'] . ' me-1"></i>' . $status['text'] . '
                        </span>
                    </div>
                    <div class="card-body">
                        ' . (!empty($exam['description']) ? '<p class="card-text small">' . nl2br(htmlspecialchars($exam['description'])) . '</p>' : '') . '
                        <div class="d-flex justify-content-between mt-3 small text-muted">
                            <div><i class="fas fa-calendar me-1"></i> ' . $exam['formatted_date'] . '</div>
                            <div><i class="fas fa-clock me-1"></i> ' . $exam['formatted_time'] . '</div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top-0 text-end">
                        <a href="view_exam.php?course_id=' . htmlspecialchars($exam['course_id']) . '" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye me-1"></i> Détails
                        </a>
                    </div>
                </div>
            </div>';
        }
        
        $content .= '</div>';
    } else {
        $content .= '<div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>Aucun examen programmé pour cette classe.
        </div>';
    }
    
    $content .= '</div>
    </div>';
}

// Ajouter du style CSS personnalisé
$content .= '<style>
    .hover-shadow:hover {
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
        transform: translateY(-2px);
        transition: all 0.3s ease;
    }
</style>

<script>
    function deleteAssignment(assignmentId) {
        if (confirm("\u00cates-vous s\u00fbr de vouloir supprimer ce devoir ?")) {
            window.location.href = `delete_assignment.php?id=${assignmentId}`;
        }
    }
</script>';

// Inclure le template
include('templates/layout.php');

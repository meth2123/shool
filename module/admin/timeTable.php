<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Initialiser le contenu pour le template
ob_start();

$admin_id = $_SESSION['login_id'];
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';

// Effacer les messages après les avoir récupérés
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Récupérer les classes pour les filtres
$classes_query = "SELECT id, name FROM class ORDER BY name";
$classes_result = $link->query($classes_query);
$has_classes = $classes_result && $classes_result->num_rows > 0;

// Récupérer les enseignants pour les filtres
$teachers_query = "SELECT id, name FROM teachers WHERE created_by = ? ORDER BY name";
$stmt = $link->prepare($teachers_query);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$teachers_result = $stmt->get_result();
$has_teachers = $teachers_result && $teachers_result->num_rows > 0;

// Construire la requête de base
$base_query = "
    SELECT DISTINCT
        cs.id,
        cs.class_id,
        cs.subject_id,
        cs.teacher_id,
        cs.slot_id,
        cs.day_of_week,
        cs.room,
        cs.semester,
        cs.academic_year,
        cs.created_by,
        c.name as class_name,
        s.name as subject_name,
        t.name as teacher_name,
        ts.start_time,
        ts.end_time,
        CONCAT(ts.start_time, ' - ', ts.end_time) as time_slot
    FROM class_schedule cs
    JOIN class c ON cs.class_id = c.id COLLATE utf8mb4_unicode_ci
    JOIN course s ON cs.subject_id = s.id COLLATE utf8mb4_unicode_ci
    JOIN teachers t ON cs.teacher_id = t.id COLLATE utf8mb4_unicode_ci
    JOIN time_slots ts ON cs.slot_id = ts.slot_id
    WHERE cs.created_by = ? COLLATE utf8mb4_unicode_ci
";

// Ajouter les filtres
$params = [];
$types = "s";
$params[] = $admin_id;

// Filtrer par classe si demandé
$filter_class = isset($_GET['class_id']) && !empty($_GET['class_id']) ? $_GET['class_id'] : '';
if (!empty($filter_class)) {
    $base_query .= " AND cs.class_id = ? COLLATE utf8mb4_unicode_ci";
    $types .= "s";
    $params[] = $filter_class;
}

// Filtrer par enseignant si demandé
$filter_teacher = isset($_GET['teacher_id']) && !empty($_GET['teacher_id']) ? $_GET['teacher_id'] : '';
if (!empty($filter_teacher)) {
    $base_query .= " AND cs.teacher_id = ? COLLATE utf8mb4_unicode_ci";
    $types .= "s";
    $params[] = $filter_teacher;
}

// Ajouter l'ordre pour éviter les répétitions et organiser logiquement
$base_query .= " ORDER BY cs.day_of_week, ts.start_time, c.name";

// Exécuter la requête finale
$stmt = $link->prepare($base_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$schedules_result = $stmt->get_result();
$has_schedules = $schedules_result && $schedules_result->num_rows > 0;
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="card-title mb-0">Gestion de l'Emploi du Temps</h2>
                        <a href="createTimeTable.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-2"></i>Ajouter un cours
                        </a>
                    </div>
                    
                    <!-- Formulaire de filtrage -->
                    <form action="" method="GET" class="row g-3 mb-4">
                        <div class="col-md-5">
                            <label for="class_id" class="form-label">Filtrer par classe</label>
                            <select name="class_id" id="class_id" class="form-select">
                                <option value="">Toutes les classes</option>
                                <?php while($class = $classes_result->fetch_assoc()): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo $filter_class == $class['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="teacher_id" class="form-label">Filtrer par enseignant</label>
                            <select name="teacher_id" id="teacher_id" class="form-select">
                                <option value="">Tous les enseignants</option>
                                <?php while($teacher = $teachers_result->fetch_assoc()): ?>
                                <option value="<?php echo $teacher['id']; ?>" <?php echo $filter_teacher == $teacher['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($teacher['name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                        </div>
                    </form>
                    
                    <p class="text-muted">Gérez les emplois du temps des classes et des enseignants</p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Filtrer les emplois du temps</h5>
                </div>
                <div class="card-body">
                    <form action="" method="get" class="row g-3">
                        <div class="col-md-4">
                            <label for="class_id" class="form-label">Classe</label>
                            <select name="class_id" id="class_id" class="form-select">
                                <option value="">Toutes les classes</option>
                                <?php if ($has_classes): while ($class = $classes_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($class['id']); ?>" 
                                        <?php echo $filter_class == $class['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['name']); ?>
                                </option>
                                <?php endwhile; endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-filter me-2"></i>Filtrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Emplois du temps</h5>
                    <div class="btn-group">
                        <a href="viewTeacherSchedules.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-chalkboard-teacher me-1"></i>Vue par enseignant
                        </a>
                        <a href="viewClassSchedules.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-users me-1"></i>Vue par classe
                        </a>
                        <a href="clean_duplicates_direct.php" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir nettoyer les emplois du temps en double ?');">
                            <i class="fas fa-broom me-1"></i>Nettoyer les doublons exacts
                        </a>
                        <a href="clean_duplicates_by_class.php" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir nettoyer les emplois du temps en double par classe et matière ? Cela supprimera les emplois du temps qui ont la même classe, matière, créneau et jour, mais des enseignants différents.');">
                            <i class="fas fa-broom me-1"></i>Nettoyer par classe
                        </a>
                        <a href="fix_timetable_index.php" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-tools me-1"></i>Corriger l'index
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($has_schedules): ?>
                    
                    <?php
                    // Organiser les emplois du temps par jour
                    $schedules_by_day = [];
                    $days_order = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                    
                    // Stocker tous les résultats dans un tableau
                    $all_schedules = [];
                    while ($schedule = $schedules_result->fetch_assoc()) {
                        $all_schedules[] = $schedule;
                    }
                    
                    // Trier par jour de la semaine selon l'ordre défini
                    usort($all_schedules, function($a, $b) use ($days_order) {
                        $day_a_index = array_search($a['day_of_week'], $days_order);
                        $day_b_index = array_search($b['day_of_week'], $days_order);
                        
                        if ($day_a_index === $day_b_index) {
                            // Si même jour, trier par heure de début
                            return strtotime($a['start_time']) - strtotime($b['start_time']);
                        }
                        
                        return $day_a_index - $day_b_index;
                    });
                    
                    // Grouper par jour
                    foreach ($all_schedules as $schedule) {
                        $schedules_by_day[$schedule['day_of_week']][] = $schedule;
                    }
                    ?>
                    
                    <ul class="nav nav-tabs mb-3" id="scheduleTabs" role="tablist">
                        <?php $first_day = true; foreach ($schedules_by_day as $day => $day_schedules): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $first_day ? 'active' : ''; ?>" 
                                    id="<?php echo strtolower($day); ?>-tab" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#<?php echo strtolower($day); ?>" 
                                    type="button" 
                                    role="tab">
                                <?php echo htmlspecialchars($day); ?>
                            </button>
                        </li>
                        <?php $first_day = false; endforeach; ?>
                    </ul>
                    
                    <div class="tab-content">
                        <?php $first_day = true; foreach ($schedules_by_day as $day => $day_schedules): ?>
                        <div class="tab-pane fade <?php echo $first_day ? 'show active' : ''; ?>" 
                             id="<?php echo strtolower($day); ?>" 
                             role="tabpanel">
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Horaire</th>
                                            <th>Classe</th>
                                            <th>Matière</th>
                                            <th>Enseignant</th>
                                            <th>Salle</th>
                                            <th>Semestre</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($day_schedules as $schedule): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($schedule['time_slot']); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['teacher_name']); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['room']); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['semester']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="updateTimeTable.php?schedule_id=<?php echo $schedule['id']; ?>" 
                                                       class="btn btn-outline-primary" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="deleteTimeTable.php?schedule_id=<?php echo $schedule['id']; ?>" 
                                                       class="btn btn-outline-danger" 
                                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet emploi du temps ?');" 
                                                       title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php $first_day = false; endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="fas fa-info-circle me-3 fs-4"></i>
                        <div>
                            <?php if (!empty($filter_class)): ?>
                            Aucun emploi du temps trouvé pour cette classe. <a href="timeTable.php" class="alert-link">Voir tous les emplois du temps</a>
                            <?php else: ?>
                            Aucun emploi du temps n'a été créé. Utilisez le bouton "Ajouter un cours" pour commencer à créer l'emploi du temps.
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include('templates/layout.php');
?>

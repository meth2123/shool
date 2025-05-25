<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Initialiser le contenu pour le template
ob_start();

$admin_id = $_SESSION['login_id'];
$teacher_id = $_GET['teacher_id'] ?? null;

// Récupérer la liste des enseignants
$teachers_query = "SELECT id, name FROM teachers WHERE created_by = ? ORDER BY name";
$stmt = $link->prepare($teachers_query);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$teachers_result = $stmt->get_result();
$has_teachers = $teachers_result && $teachers_result->num_rows > 0;

// Si un enseignant spécifique est demandé
$selected_teacher = null;
$schedules = null;

if ($teacher_id && $has_teachers) {
    // Récupérer les informations de l'enseignant
    $teacher_query = "SELECT id, name FROM teachers WHERE id = ? AND created_by = ?";
    $stmt = $link->prepare($teacher_query);
    $stmt->bind_param("ss", $teacher_id, $admin_id);
    $stmt->execute();
    $selected_teacher = $stmt->get_result()->fetch_assoc();
    
    if ($selected_teacher) {
        // Récupérer l'emploi du temps de l'enseignant
        $schedules_query = "
            SELECT 
                cs.*,
                c.name as class_name,
                s.name as subject_name,
                ts.start_time,
                ts.end_time,
                CONCAT(ts.start_time, ' - ', ts.end_time) as time_slot
            FROM class_schedule cs
            JOIN class c ON cs.class_id = c.id COLLATE utf8mb4_0900_ai_ci
            JOIN course s ON cs.subject_id = s.id COLLATE utf8mb4_0900_ai_ci
            JOIN time_slots ts ON cs.slot_id = ts.slot_id
            WHERE cs.teacher_id = ? COLLATE utf8mb4_0900_ai_ci AND cs.created_by = ? COLLATE utf8mb4_0900_ai_ci
            ORDER BY FIELD(cs.day_of_week, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'), ts.start_time
        ";
        $stmt = $link->prepare($schedules_query);
        $stmt->bind_param("ss", $teacher_id, $admin_id);
        $stmt->execute();
        $schedules_result = $stmt->get_result();
        $has_schedules = $schedules_result && $schedules_result->num_rows > 0;
        
        if ($has_schedules) {
            // Organiser les emplois du temps par jour
            $schedules = [
                'Lundi' => [],
                'Mardi' => [],
                'Mercredi' => [],
                'Jeudi' => [],
                'Vendredi' => [],
                'Samedi' => []
            ];
            
            while ($schedule = $schedules_result->fetch_assoc()) {
                $schedules[$schedule['day_of_week']][] = $schedule;
            }
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="card-title mb-0">Emploi du Temps des Enseignants</h2>
                        <a href="timeTable.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                    </div>
                    <p class="text-muted">Consultez l'emploi du temps par enseignant</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Sélectionner un enseignant</h5>
                </div>
                <div class="card-body">
                    <?php if ($has_teachers): ?>
                    <form action="" method="get" class="row g-3">
                        <div class="col-md-6">
                            <select name="teacher_id" id="teacher_id" class="form-select">
                                <option value="">Choisir un enseignant</option>
                                <?php while ($teacher = $teachers_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($teacher['id']); ?>" 
                                        <?php echo $teacher_id == $teacher['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($teacher['name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Afficher l'emploi du temps
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="fas fa-info-circle me-3 fs-4"></i>
                        <div>
                            Aucun enseignant n'a été trouvé. Veuillez d'abord ajouter des enseignants.
                            <div class="mt-2">
                                <a href="manageTeacher.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-plus-circle me-1"></i>Gérer les enseignants
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($selected_teacher): ?>
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        Emploi du temps de <?php echo htmlspecialchars($selected_teacher['name']); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($schedules): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th width="16%">Horaire</th>
                                    <th width="14%">Lundi</th>
                                    <th width="14%">Mardi</th>
                                    <th width="14%">Mercredi</th>
                                    <th width="14%">Jeudi</th>
                                    <th width="14%">Vendredi</th>
                                    <th width="14%">Samedi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Récupérer tous les créneaux horaires
                                $time_slots_query = "SELECT slot_id, start_time, end_time, CONCAT(start_time, ' - ', end_time) as time_range FROM time_slots ORDER BY start_time";
                                $time_slots_result = $link->query($time_slots_query);
                                
                                while ($slot = $time_slots_result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td class="table-light fw-bold">
                                        <?php echo htmlspecialchars($slot['time_range']); ?>
                                    </td>
                                    <?php foreach (array_keys($schedules) as $day): ?>
                                    <td>
                                        <?php
                                        $found = false;
                                        foreach ($schedules[$day] as $schedule) {
                                            if ($schedule['slot_id'] == $slot['slot_id']) {
                                                $found = true;
                                                echo '<div class="p-2 bg-light rounded border">';
                                                echo '<div class="fw-bold">' . htmlspecialchars($schedule['subject_name']) . '</div>';
                                                echo '<div class="small">' . htmlspecialchars($schedule['class_name']) . '</div>';
                                                echo '<div class="small text-muted">Salle: ' . htmlspecialchars($schedule['room']) . '</div>';
                                                echo '</div>';
                                                break;
                                            }
                                        }
                                        ?>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <h6 class="fw-bold">Liste des cours</h6>
                        <div class="table-responsive mt-3">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Jour</th>
                                        <th>Horaire</th>
                                        <th>Classe</th>
                                        <th>Matière</th>
                                        <th>Salle</th>
                                        <th>Semestre</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedules as $day => $day_schedules): ?>
                                        <?php foreach ($day_schedules as $schedule): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($day); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['time_slot']); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['room']); ?></td>
                                            <td>Semestre <?php echo htmlspecialchars($schedule['semester']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="updateTimeTable.php?schedule_id=<?php echo $schedule['id']; ?>" 
                                                       class="btn btn-outline-primary" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="deleteTimeTable.php?schedule_id=<?php echo $schedule['id']; ?>" 
                                                       class="btn btn-outline-danger" 
                                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce cours ?');" 
                                                       title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="fas fa-info-circle me-3 fs-4"></i>
                        <div>
                            Aucun cours n'a été programmé pour cet enseignant.
                            <div class="mt-2">
                                <a href="createTimeTable.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-plus-circle me-1"></i>Ajouter un cours
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include('templates/layout.php');
?>

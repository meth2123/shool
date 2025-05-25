<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Initialiser le contenu pour le template
ob_start();

$check = $_SESSION['login_id'] ?? null;
if(!isset($check)) {
    header("Location:../../");
    exit();
}

$schedule_id = $_GET['schedule_id'] ?? null;

if(!$schedule_id) {
    $_SESSION['error_message'] = "ID de l'emploi du temps non spécifié";
    header("Location: timeTable.php");
    exit();
}

// Récupérer les informations actuelles de l'emploi du temps
$stmt = $link->prepare("
    SELECT 
        cs.*,
        s.name as subject_name,
        c.name as class_name,
        t.name as teacher_name
    FROM class_schedule cs
    JOIN course s ON cs.subject_id = s.id COLLATE utf8mb4_0900_ai_ci
    JOIN class c ON cs.class_id = c.id COLLATE utf8mb4_0900_ai_ci
    JOIN teachers t ON cs.teacher_id = t.id COLLATE utf8mb4_0900_ai_ci
    WHERE cs.id = ? AND cs.created_by = ? COLLATE utf8mb4_0900_ai_ci
");
$admin_id = $_SESSION['login_id'];
$stmt->bind_param("is", $schedule_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$schedule = $result->fetch_assoc();

if(!$schedule) {
    $_SESSION['error_message'] = "Emploi du temps non trouvé ou vous n'avez pas les droits d'accès";
    header("Location: timeTable.php");
    exit();
}

// Récupérer les listes pour les menus déroulants
$classes_query = "SELECT id, name FROM class WHERE id IN (SELECT DISTINCT classid FROM course WHERE teacherid IN (SELECT id FROM teachers WHERE created_by = ?)) ORDER BY name";
$stmt = $link->prepare($classes_query);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$classes = $stmt->get_result();

$subjects_query = "SELECT id, name FROM course WHERE teacherid IN (SELECT id FROM teachers WHERE created_by = ?) ORDER BY name";
$stmt = $link->prepare($subjects_query);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$subjects = $stmt->get_result();

$teachers_query = "SELECT id, name FROM teachers WHERE created_by = ? ORDER BY name";
$stmt = $link->prepare($teachers_query);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$teachers = $stmt->get_result();

$timeSlots_query = "SELECT slot_id, CONCAT(start_time, ' - ', end_time) as time_range FROM time_slots ORDER BY start_time";
$timeSlots = $link->query($timeSlots_query);

// Jours de la semaine
$days_of_week = [
    'Lundi' => 'Lundi',
    'Mardi' => 'Mardi',
    'Mercredi' => 'Mercredi',
    'Jeudi' => 'Jeudi',
    'Vendredi' => 'Vendredi',
    'Samedi' => 'Samedi'
];

// Traiter la soumission du formulaire
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id'] ?? '';
    $subject_id = $_POST['subject_id'] ?? '';
    $teacher_id = $_POST['teacher_id'] ?? '';
    $slot_id = $_POST['slot_id'] ?? '';
    $day_of_week = $_POST['day_of_week'] ?? '';
    $room = $_POST['room'] ?? '';
    $semester = $_POST['semester'] ?? '';
    $academic_year = $_POST['academic_year'] ?? '';

    // Validation
    if(empty($class_id) || empty($subject_id) || empty($teacher_id) || 
       empty($slot_id) || empty($day_of_week) || empty($room) || empty($semester) || empty($academic_year)) {
        $error_message = "Tous les champs sont obligatoires";
    } else {
        // Vérifier les conflits (excluant l'entrée actuelle)
        $stmt = $link->prepare("
            SELECT id FROM class_schedule 
            WHERE class_id = ? AND slot_id = ? AND day_of_week = ? AND semester = ? AND academic_year = ?
            AND id != ?
        ");
        $stmt->bind_param("sisssi", $class_id, $slot_id, $day_of_week, $semester, $academic_year, $schedule_id);
        $stmt->execute();
        $conflict_result = $stmt->get_result();
        
        if($conflict_result->num_rows > 0) {
            $error_message = "Cette classe a déjà un cours programmé sur ce créneau";
        } else {
            // Mettre à jour l'emploi du temps
            $stmt = $link->prepare("
                UPDATE class_schedule 
                SET class_id = ?, subject_id = ?, teacher_id = ?, slot_id = ?, 
                    day_of_week = ?, room = ?, semester = ?, academic_year = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sssissssi", 
                $class_id, $subject_id, $teacher_id, $slot_id, 
                $day_of_week, $room, $semester, $academic_year, $schedule_id
            );

            if($stmt->execute()) {
                $_SESSION['success_message'] = "Emploi du temps mis à jour avec succès";
                header("Location: timeTable.php");
                exit();
            } else {
                $error_message = "Erreur lors de la mise à jour: " . $link->error;
            }
        }
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <!-- En-tête -->
            <div class="mb-4">
                <h2>Modifier l'Emploi du Temps</h2>
                <p class="text-muted">Modifiez les informations du cours dans l'emploi du temps</p>
            </div>

            <!-- Messages d'erreur -->
            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <!-- Formulaire -->
            <form action="" method="POST" class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Informations du cours</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="class_id" class="form-label">Classe</label>
                            <select name="class_id" id="class_id" required class="form-select">
                                <option value="">Sélectionnez une classe</option>
                                <?php while($class = $classes->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($class['id']); ?>"
                                            <?php echo $class['id'] === $schedule['class_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="subject_id" class="form-label">Matière</label>
                            <select name="subject_id" id="subject_id" required class="form-select">
                                <option value="">Sélectionnez une matière</option>
                                <?php while($subject = $subjects->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($subject['id']); ?>"
                                            <?php echo $subject['id'] === $schedule['subject_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="teacher_id" class="form-label">Professeur</label>
                            <select name="teacher_id" id="teacher_id" required class="form-select">
                                <option value="">Sélectionnez un professeur</option>
                                <?php while($teacher = $teachers->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($teacher['id']); ?>"
                                            <?php echo $teacher['id'] === $schedule['teacher_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($teacher['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="day_of_week" class="form-label">Jour</label>
                            <select name="day_of_week" id="day_of_week" required class="form-select">
                                <option value="">Sélectionnez un jour</option>
                                <?php foreach($days_of_week as $day_value => $day_label): ?>
                                    <option value="<?php echo htmlspecialchars($day_value); ?>"
                                            <?php echo $day_value === $schedule['day_of_week'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($day_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="slot_id" class="form-label">Créneau horaire</label>
                            <select name="slot_id" id="slot_id" required class="form-select">
                                <option value="">Sélectionnez un créneau</option>
                                <?php while($slot = $timeSlots->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($slot['slot_id']); ?>"
                                            <?php echo $slot['slot_id'] === $schedule['slot_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($slot['time_range']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="room" class="form-label">Salle</label>
                            <input type="text" name="room" id="room" value="<?php echo htmlspecialchars($schedule['room']); ?>"
                                   required class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label for="semester" class="form-label">Semestre</label>
                            <select name="semester" id="semester" required class="form-select">
                                <option value="1" <?php echo $schedule['semester'] === '1' ? 'selected' : ''; ?>>Semestre 1</option>
                                <option value="2" <?php echo $schedule['semester'] === '2' ? 'selected' : ''; ?>>Semestre 2</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="academic_year" class="form-label">Année académique</label>
                            <input type="text" name="academic_year" id="academic_year" 
                                   value="<?php echo htmlspecialchars($schedule['academic_year']); ?>"
                                   required pattern="\d{4}-\d{4}" placeholder="2023-2024"
                                   class="form-control">
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-end gap-2">
                    <a href="timeTable.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include('templates/layout.php');
?>

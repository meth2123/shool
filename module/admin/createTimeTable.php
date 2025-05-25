<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Initialiser le contenu pour le template
ob_start();

$success_message = '';
$error_message = '';

$admin_id = $_SESSION['login_id'];

// Récupérer les classes de l'administrateur
$classes_query = "SELECT id, name FROM class ORDER BY name";
$classes_result = $link->query($classes_query);
$has_classes = $classes_result && $classes_result->num_rows > 0;

// Nous allons récupérer les enseignants et les matières via AJAX
// pour la sélection en cascade, mais nous préparons quand même les données
// au cas où JavaScript est désactivé

// Récupérer les enseignants
$teachers_query = "SELECT id, name FROM teachers WHERE created_by = ? ORDER BY name";
$stmt = $link->prepare($teachers_query);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$teachers_result = $stmt->get_result();
$has_teachers = $teachers_result && $teachers_result->num_rows > 0;

// Récupérer les matières (cours)
$subjects_query = "SELECT c.id, c.name, c.teacherid, c.classid FROM course c ORDER BY c.name";
$subjects_result = $link->query($subjects_query);
$has_subjects = $subjects_result && $subjects_result->num_rows > 0;

// Préparer les données pour JavaScript
$all_subjects = [];
if ($has_subjects) {
    while ($subject = $subjects_result->fetch_assoc()) {
        $all_subjects[] = $subject;
    }
    // Réinitialiser le pointeur de résultat pour une utilisation ultérieure
    $subjects_result->data_seek(0);
}

// Récupérer les créneaux horaires (sans doublons)
$timeSlots_query = "SELECT DISTINCT slot_id, start_time, end_time, CONCAT(start_time, ' - ', end_time) as time_range FROM time_slots ORDER BY start_time";
$timeSlots_result = $link->query($timeSlots_query);
$has_timeSlots = $timeSlots_result && $timeSlots_result->num_rows > 0;

// Si aucun créneau horaire n'existe, en créer quelques-uns par défaut
if (!$has_timeSlots) {
    $default_slots = [
        ['08:00:00', '09:00:00'],
        ['09:00:00', '10:00:00'],
        ['10:00:00', '11:00:00'],
        ['11:00:00', '12:00:00'],
        ['13:00:00', '14:00:00'],
        ['14:00:00', '15:00:00'],
        ['15:00:00', '16:00:00'],
        ['16:00:00', '17:00:00']
    ];
    
    foreach ($default_slots as $slot) {
        $stmt = $link->prepare("INSERT INTO time_slots (start_time, end_time) VALUES (?, ?)");
        $stmt->bind_param("ss", $slot[0], $slot[1]);
        $stmt->execute();
    }
    
    // Récupérer à nouveau les créneaux horaires
    $timeSlots_result = $link->query($timeSlots_query);
    $has_timeSlots = $timeSlots_result && $timeSlots_result->num_rows > 0;
}

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $class_id = $_POST['class_id'] ?? '';
    $subject_id = $_POST['subject_id'] ?? '';
    $teacher_id = $_POST['teacher_id'] ?? '';
    $slot_id = $_POST['slot_id'] ?? '';
    $day_of_week = $_POST['day_of_week'] ?? '';
    $room = $_POST['room'] ?? '';
    $semester = $_POST['semester'] ?? '';
    $academic_year = $_POST['academic_year'] ?? '';
    
    // Validation
    if (empty($class_id) || empty($subject_id) || empty($teacher_id) || 
        empty($slot_id) || empty($day_of_week) || empty($room) || 
        empty($semester) || empty($academic_year)) {
        $error_message = "Tous les champs sont obligatoires";
    } else {
        // Vérifier si cet emploi du temps existe déjà (tous les champs qui constituent l'index unique)
        $stmt = $link->prepare("
            SELECT COUNT(*) as count FROM class_schedule 
            WHERE class_id = ? AND subject_id = ? AND teacher_id = ? AND slot_id = ? AND day_of_week = ? AND semester = ? AND academic_year = ?
        ");
        $stmt->bind_param("sssssss", $class_id, $subject_id, $teacher_id, $slot_id, $day_of_week, $semester, $academic_year);
        $stmt->execute();
        $result = $stmt->get_result();
        $duplicate_entry = $result->fetch_assoc()['count'] > 0;
        
        if ($duplicate_entry) {
            $error_message = "Cet emploi du temps existe déjà pour cette classe, cette matière, cet enseignant et ce créneau";
        } else {
            // Vérifier les conflits pour la classe (même classe, même créneau, même jour)
            $stmt = $link->prepare("
                SELECT COUNT(*) as count FROM class_schedule 
                WHERE class_id = ? AND slot_id = ? AND day_of_week = ? AND semester = ? AND academic_year = ?
            ");
            $stmt->bind_param("sssss", $class_id, $slot_id, $day_of_week, $semester, $academic_year);
            $stmt->execute();
            $result = $stmt->get_result();
            $class_conflict = $result->fetch_assoc()['count'] > 0;
            
            // Vérifier les conflits pour l'enseignant (même enseignant, même créneau, même jour)
            $stmt = $link->prepare("
                SELECT COUNT(*) as count FROM class_schedule 
                WHERE teacher_id = ? AND slot_id = ? AND day_of_week = ? AND semester = ? AND academic_year = ?
            ");
            $stmt->bind_param("sssss", $teacher_id, $slot_id, $day_of_week, $semester, $academic_year);
            $stmt->execute();
            $result = $stmt->get_result();
            $teacher_conflict = $result->fetch_assoc()['count'] > 0;
            
            if ($class_conflict) {
                $error_message = "Cette classe a déjà un cours programmé sur ce créneau";
            } else if ($teacher_conflict) {
                $error_message = "Cet enseignant a déjà un cours programmé sur ce créneau";
            } else {
                // Vérifier et ajouter les colonnes nécessaires si elles n'existent pas
                $required_columns = [
                    'day_of_week' => "ALTER TABLE class_schedule ADD COLUMN day_of_week VARCHAR(20) NOT NULL AFTER slot_id",
                    'semester' => "ALTER TABLE class_schedule ADD COLUMN semester VARCHAR(10) NOT NULL AFTER day_of_week",
                    'academic_year' => "ALTER TABLE class_schedule ADD COLUMN academic_year VARCHAR(10) NOT NULL AFTER semester",
                    'room' => "ALTER TABLE class_schedule ADD COLUMN room VARCHAR(50) NOT NULL AFTER academic_year"
                ];
                
                foreach ($required_columns as $column => $query) {
                    $column_exists = $link->query("SHOW COLUMNS FROM class_schedule LIKE '$column'");
                    if ($column_exists && $column_exists->num_rows == 0) {
                        // La colonne n'existe pas, nous allons l'ajouter
                        $link->query($query);
                    }
                }
                // Insérer le nouvel emploi du temps
                $stmt = $link->prepare("
                    INSERT INTO class_schedule 
                    (class_id, subject_id, teacher_id, slot_id, day_of_week, 
                    room, semester, academic_year, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("sssisssss", 
                    $class_id, $subject_id, $teacher_id, $slot_id, 
                    $day_of_week, $room, $semester, $academic_year, $admin_id
                );
                
                if ($stmt->execute()) {
                    $success_message = "Emploi du temps créé avec succès";
                    // Rediriger vers la page principale après un court délai
                    header("refresh:2;url=timeTable.php");
                } else {
                    $error_message = "Erreur lors de la création de l'emploi du temps: " . $link->error;
                }
            }
        }
    }
}

// Vérifier si toutes les données nécessaires sont disponibles
$can_create = $has_classes && $has_subjects && $has_teachers && $has_timeSlots;
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <!-- En-tête -->
            <div class="mb-4">
                <h2>Créer un Emploi du Temps</h2>
                <p class="text-muted">Ajoutez un nouveau cours à l'emploi du temps</p>
            </div>

            <!-- Messages de notification -->
            <?php if($success_message): ?>
            <div class="alert alert-success mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            </div>
            <?php endif; ?>

            <?php if($error_message): ?>
            <div class="alert alert-danger mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <?php if(!$can_create): ?>
            <div class="alert alert-warning mb-4">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <p class="mb-2">
                            <?php if(!$has_classes): ?>
                            Vous n'avez pas encore créé de classes. Veuillez d'abord créer une classe.
                            <?php elseif(!$has_subjects): ?>
                            Vous n'avez pas encore créé de cours. Veuillez d'abord créer un cours.
                            <?php elseif(!$has_teachers): ?>
                            Vous n'avez pas encore ajouté d'enseignants. Veuillez d'abord ajouter un enseignant.
                            <?php else: ?>
                            Certaines données nécessaires sont manquantes. Veuillez vérifier votre configuration.
                            <?php endif; ?>
                        </p>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if(!$has_classes): ?>
                            <a href="manageClass.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-users me-1"></i>Gérer les classes
                            </a>
                            <?php endif; ?>
                            
                            <?php if(!$has_subjects): ?>
                            <a href="course.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-book me-1"></i>Gérer les cours
                            </a>
                            <?php endif; ?>
                            
                            <?php if(!$has_teachers): ?>
                            <a href="manageTeacher.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-chalkboard-teacher me-1"></i>Gérer les enseignants
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Formulaire -->
            <form action="" method="post" class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Informations du cours</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="class_id" class="form-label">Classe <span class="text-danger">*</span></label>
                            <select name="class_id" id="class_id" required class="form-select" <?php echo !$has_classes ? 'disabled' : ''; ?>>
                                <option value="">Sélectionnez une classe</option>
                                <?php if($has_classes): while($class = $classes_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($class['id']); ?>">
                                    <?php echo htmlspecialchars($class['name']); ?>
                                </option>
                                <?php endwhile; endif; ?>
                            </select>
                            <small class="form-text text-muted">Sélectionnez d'abord une classe</small>
                        </div>

                        <div class="col-md-6">
                            <label for="teacher_id" class="form-label">Professeur <span class="text-danger">*</span></label>
                            <select name="teacher_id" id="teacher_id" required class="form-select" disabled>
                                <option value="">Sélectionnez d'abord une classe</option>
                            </select>
                            <small class="form-text text-muted">Les professeurs disponibles pour cette classe</small>
                        </div>

                        <div class="col-md-6">
                            <label for="subject_id" class="form-label">Matière <span class="text-danger">*</span></label>
                            <select name="subject_id" id="subject_id" required class="form-select" disabled>
                                <option value="">Sélectionnez d'abord un professeur</option>
                            </select>
                            <small class="form-text text-muted">Les matières enseignées par ce professeur dans cette classe</small>
                        </div>

                        <div class="col-md-6">
                            <label for="day_of_week" class="form-label">Jour</label>
                            <select name="day_of_week" id="day_of_week" required class="form-select">
                                <option value="">Sélectionnez un jour</option>
                                <?php foreach($days_of_week as $day_value => $day_label): ?>
                                <option value="<?php echo htmlspecialchars($day_value); ?>">
                                    <?php echo htmlspecialchars($day_label); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="slot_id" class="form-label">Créneau horaire</label>
                            <select name="slot_id" id="slot_id" required class="form-select" <?php echo !$has_timeSlots ? 'disabled' : ''; ?>>
                                <option value="">Sélectionnez un créneau</option>
                                <?php if($has_timeSlots): while($slot = $timeSlots_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($slot['slot_id']); ?>">
                                    <?php echo htmlspecialchars($slot['time_range']); ?>
                                </option>
                                <?php endwhile; endif; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="room" class="form-label">Salle</label>
                            <input type="text" name="room" id="room" required class="form-control" placeholder="Ex: Salle 101">
                        </div>

                        <div class="col-md-6">
                            <label for="semester" class="form-label">Semestre</label>
                            <select name="semester" id="semester" required class="form-select">
                                <option value="">Sélectionnez un semestre</option>
                                <option value="1">Semestre 1</option>
                                <option value="2">Semestre 2</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="academic_year" class="form-label">Année académique</label>
                            <input type="text" name="academic_year" id="academic_year" 
                                   required pattern="\d{4}-\d{4}" placeholder="2023-2024"
                                   class="form-control">
                            <div class="form-text">Format: AAAA-AAAA (ex: 2023-2024)</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-end gap-2">
                    <a href="timeTable.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Annuler
                    </a>
                    <button type="submit" name="submit" value="submit" class="btn btn-primary" <?php echo !$can_create ? 'disabled' : ''; ?>>
                        <i class="fas fa-save me-2"></i>Créer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script pour la sélection en cascade -->
<script>
// Stocker les données des matières pour JavaScript
const allSubjects = <?php echo json_encode($all_subjects); ?>;

// Fonction pour filtrer les enseignants par classe
document.getElementById('class_id').addEventListener('change', function() {
    const classId = this.value;
    const teacherSelect = document.getElementById('teacher_id');
    const subjectSelect = document.getElementById('subject_id');
    
    // Réinitialiser les sélections
    teacherSelect.innerHTML = '<option value="">Sélectionnez un professeur</option>';
    subjectSelect.innerHTML = '<option value="">Sélectionnez d’abord un professeur</option>';
    
    // Désactiver les sélections suivantes
    subjectSelect.disabled = true;
    
    if (!classId) {
        teacherSelect.disabled = true;
        return;
    }
    
    // Trouver les professeurs qui enseignent dans cette classe
    const teacherIds = new Set();
    const teacherNames = {};
    
    allSubjects.forEach(subject => {
        if (subject.classid === classId) {
            teacherIds.add(subject.teacherid);
        }
    });
    
    // Si aucun professeur n'est trouvé
    if (teacherIds.size === 0) {
        teacherSelect.innerHTML = '<option value="">Aucun professeur disponible pour cette classe</option>';
        teacherSelect.disabled = true;
        return;
    }
    
    // Récupérer les noms des professeurs via une requête AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `get_teachers.php?ids=${Array.from(teacherIds).join(',')}`, true);
    xhr.onload = function() {
        if (this.status === 200) {
            try {
                const teachers = JSON.parse(this.responseText);
                
                // Ajouter les options des professeurs
                teachers.forEach(teacher => {
                    const option = document.createElement('option');
                    option.value = teacher.id;
                    option.textContent = teacher.name;
                    teacherSelect.appendChild(option);
                });
                
                // Activer la sélection
                teacherSelect.disabled = false;
            } catch (e) {
                console.error('Erreur lors du parsing JSON:', e);
                // Fallback: utiliser les données côté client
                <?php if($has_teachers): ?>
                const teachers = [
                    <?php 
                    $teachers_result->data_seek(0);
                    while($teacher = $teachers_result->fetch_assoc()): 
                    ?>
                    { id: '<?php echo addslashes($teacher['id']); ?>', name: '<?php echo addslashes($teacher['name']); ?>' },
                    <?php endwhile; ?>
                ];
                
                // Filtrer les professeurs qui enseignent dans cette classe
                const filteredTeachers = teachers.filter(teacher => 
                    Array.from(teacherIds).includes(teacher.id)
                );
                
                // Ajouter les options des professeurs
                filteredTeachers.forEach(teacher => {
                    const option = document.createElement('option');
                    option.value = teacher.id;
                    option.textContent = teacher.name;
                    teacherSelect.appendChild(option);
                });
                
                // Activer la sélection
                teacherSelect.disabled = false;
                <?php endif; ?>
            }
        }
    };
    xhr.send();
});

// Fonction pour filtrer les matières par professeur et classe
document.getElementById('teacher_id').addEventListener('change', function() {
    const teacherId = this.value;
    const classId = document.getElementById('class_id').value;
    const subjectSelect = document.getElementById('subject_id');
    
    // Réinitialiser la sélection
    subjectSelect.innerHTML = '<option value="">Sélectionnez une matière</option>';
    
    if (!teacherId) {
        subjectSelect.disabled = true;
        return;
    }
    
    // Filtrer les matières par professeur et classe
    const filteredSubjects = allSubjects.filter(subject => 
        subject.teacherid === teacherId && subject.classid === classId
    );
    
    // Si aucune matière n'est trouvée
    if (filteredSubjects.length === 0) {
        subjectSelect.innerHTML = '<option value="">Aucune matière disponible pour ce professeur</option>';
        subjectSelect.disabled = true;
        return;
    }
    
    // Ajouter les options des matières
    filteredSubjects.forEach(subject => {
        const option = document.createElement('option');
        option.value = subject.id;
        option.textContent = subject.name;
        subjectSelect.appendChild(option);
    });
    
    // Activer la sélection
    subjectSelect.disabled = false;
});
</script>

<?php
// Créer un fichier get_teachers.php pour récupérer les enseignants via AJAX
$get_teachers_file = __DIR__ . '/get_teachers.php';
if (!file_exists($get_teachers_file)) {
    $get_teachers_content = <<<'EOT'
<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['login_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Récupérer les IDs des enseignants
$teacher_ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];

if (empty($teacher_ids)) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Préparer la requête avec des paramètres
$placeholders = implode(',', array_fill(0, count($teacher_ids), '?'));
$query = "SELECT id, name FROM teachers WHERE id IN ($placeholders) ORDER BY name";

$stmt = $link->prepare($query);

// Bind les paramètres
$types = str_repeat('s', count($teacher_ids));
$stmt->bind_param($types, ...$teacher_ids);

$stmt->execute();
$result = $stmt->get_result();

$teachers = [];
while ($row = $result->fetch_assoc()) {
    $teachers[] = $row;
}

header('Content-Type: application/json');
echo json_encode($teachers);
EOT;
    file_put_contents($get_teachers_file, $get_teachers_content);
}

$content = ob_get_clean();
include('templates/layout.php');
?>

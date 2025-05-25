<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Initialiser le contenu pour le template
ob_start();

$success_message = '';
$error_message = '';
$exam_data = null;

// Récupérer l'ID de l'examen depuis l'URL
$exam_id = isset($_GET['id']) ? $_GET['id'] : null;

// Si un ID est fourni, récupérer les données de l'examen
if ($exam_id) {
    $admin_id = $_SESSION['login_id'];
    
    // Debug
    error_log("Tentative de récupération de l'examen - ID: " . $exam_id . ", Admin ID: " . $admin_id);
    
    // Vérifier d'abord si l'examen existe
    $check_stmt = $link->prepare("SELECT COUNT(*) as count FROM examschedule WHERE id = ?");
    $check_stmt->bind_param("s", $exam_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $count = $check_result->fetch_assoc()['count'];
    
    error_log("L'examen existe-t-il ? Count = " . $count);
    
    if($count > 0) {
        // L'examen existe, récupérer ses détails
        $stmt = $link->prepare("SELECT e.*, c.name as course_name 
                               FROM examschedule e 
                               LEFT JOIN course c ON e.courseid = c.id 
                               WHERE e.id = ?");
        $stmt->bind_param("s", $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exam_data = $result->fetch_assoc();
        
        // Debug des données récupérées
        error_log("Données de l'examen récupérées : " . print_r($exam_data, true));
        
        if (!$exam_data) {
            error_log("Impossible de récupérer les données de l'examen");
        }
    } else {
        error_log("Aucun examen trouvé avec l'ID: " . $exam_id);
    }
}

// Traitement du formulaire de mise à jour
if(!empty($_POST['submit'])){
    $id = $_POST['id'];
    $examdate = $_POST['examdate'];
    $examtime = $_POST['examtime'];
    $courseid = $_POST['courseid'];
    $admin_id = $_SESSION['login_id'];
    
    // Debug des données du formulaire
    error_log("Données du formulaire - ID: $id, Date: $examdate, Time: $examtime, Course: $courseid, Admin: $admin_id");
    
    // Vérification que l'examen existe
    $stmt = $link->prepare("SELECT id FROM examschedule WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $exam_result = $stmt->get_result();
    
    if($exam_result->num_rows === 0) {
        $error_message = "L'examen n'existe pas.";
        error_log("Erreur: L'examen n'existe pas - ID: " . $id);
    } else {
        // Vérification que le cours existe
        $stmt = $link->prepare("SELECT id FROM course WHERE id = ?");
        $stmt->bind_param("s", $courseid);
        $stmt->execute();
        $course_result = $stmt->get_result();
        
        if($course_result->num_rows === 0) {
            $error_message = "Le cours spécifié n'existe pas.";
            error_log("Erreur: Le cours n'existe pas - ID: " . $courseid);
        } else {
            // Mise à jour avec requête préparée
            $stmt = $link->prepare("UPDATE examschedule SET examdate = ?, time = ?, courseid = ?, created_by = ? WHERE id = ?");
            $stmt->bind_param("sssss", $examdate, $examtime, $courseid, $admin_id, $id);
            
            if($stmt->execute()) {
                $success_message = "Planning d'examen mis à jour avec succès !";
                error_log("Mise à jour réussie pour l'examen ID: " . $id);
                // Rafraîchir les données
                $exam_data['examdate'] = $examdate;
                $exam_data['time'] = $examtime;
                $exam_data['courseid'] = $courseid;
            } else {
                $error_message = "Erreur lors de la mise à jour : " . $stmt->error;
                error_log("Erreur de mise à jour: " . $stmt->error);
            }
        }
    }
}

// Récupérer la liste des cours pour le select
$stmt = $link->prepare("SELECT id, name FROM course ORDER BY name");
$stmt->execute();
$courses_result = $stmt->get_result();

// Debug du nombre de cours disponibles
error_log("Nombre de cours disponibles : " . $courses_result->num_rows);
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <!-- En-tête -->
            <div class="mb-4">
                <h2>Modifier un Planning d'Examen</h2>
                <p class="text-muted">Mettez à jour les informations du planning d'examen</p>
            </div>
            
        <!-- Messages de notification -->
        <?php if($success_message): ?>
        <div class="alert alert-success mb-4" role="alert">
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <?php if($error_message): ?>
        <div class="alert alert-danger mb-4" role="alert">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <?php if(!$exam_data): ?>
        <div class="alert alert-warning mb-4">
            <div class="d-flex">
                <div class="me-3">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div>
                    <p class="mb-0">
                        Aucun examen trouvé avec cet identifiant.
                    </p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Formulaire -->
        <form action="" method="post" class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Informations de l'examen</h5>
            </div>
            <div class="card-body">
                <!-- ID de l'examen -->
                <div class="mb-3">
                    <label for="id" class="form-label">ID de l'examen</label>
                    <input type="text" name="id" id="id" readonly
                        value="<?php echo htmlspecialchars($exam_data['id']); ?>"
                        class="form-control bg-light">
                </div>

                <!-- Date de l'examen -->
                <div class="mb-3">
                    <label for="examdate" class="form-label">Date de l'examen</label>
                    <input type="date" name="examdate" id="examdate" required
                        value="<?php echo htmlspecialchars($exam_data['examdate']); ?>"
                        class="form-control">
                </div>

                <!-- Heure de l'examen -->
                <div class="mb-3">
                    <label for="examtime" class="form-label">Horaire de l'examen</label>
                    <input type="time" name="examtime" id="examtime" required
                        value="<?php echo htmlspecialchars($exam_data['time']); ?>"
                        class="form-control">
                </div>

                <!-- Cours -->
                <div class="mb-3">
                    <label for="courseid" class="form-label">Cours</label>
                    <select name="courseid" id="courseid" required
                        class="form-select">
                        <option value="">Sélectionnez un cours</option>
                        <?php while($course = $courses_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($course['id']); ?>"
                                <?php echo ($course['id'] == $exam_data['courseid']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

            </div>
            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="viewExamSchedule.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i>Annuler
                </a>
                <button type="submit" name="submit" value="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Mettre à jour
                </button>
            </div>
        </form>
        <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include('templates/layout.php');
?>

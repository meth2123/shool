<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Initialiser le contenu pour le template
ob_start();

// Traitement du formulaire
$success_message = '';
$error_message = '';

if(!empty($_POST['submit'])){
    $id = $_POST['id'];
    $examDate = $_POST['examDate'];
    $examTime = $_POST['examTime'];
    $courseId = $_POST['courseId'];
    $created_by = $_SESSION['login_id']; // Ajout du created_by

    // Vérification que le cours existe et appartient à l'admin
    $stmt = $link->prepare("SELECT id FROM course WHERE id = ? AND created_by = ?");
    $stmt->bind_param("ss", $courseId, $created_by);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0) {
        $error_message = "Le cours spécifié n'existe pas ou ne vous appartient pas.";
    } else {
        // Insertion avec requête préparée
        $stmt = $link->prepare("INSERT INTO examschedule (id, examdate, time, courseid, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $id, $examDate, $examTime, $courseId, $created_by);
        
        if($stmt->execute()) {
            $success_message = "Planning d'examen créé avec succès !";
        } else {
            $error_message = "Erreur lors de la création du planning : " . $stmt->error;
        }
    }
}

// Récupération des cours pour le select (uniquement ceux créés par l'admin connecté)
$admin_id = $_SESSION['login_id'];
$stmt = $link->prepare("SELECT id, name FROM course WHERE created_by = ? ORDER BY name");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$courses_result = $stmt->get_result();

// Vérifier s'il y a des cours disponibles
$has_courses = $courses_result->num_rows > 0;
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <!-- En-tête -->
            <div class="mb-4">
                <h2>Créer un Planning d'Examen</h2>
                <p class="text-muted">Remplissez le formulaire ci-dessous pour créer un nouveau planning d'examen</p>
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

        <?php if(!$has_courses): ?>
        <div class="alert alert-warning mb-4">
            <div class="d-flex">
                <div class="me-3">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div>
                    <p class="mb-2">
                        Vous n'avez pas encore créé de cours. Veuillez d'abord créer un cours avant de planifier un examen.
                    </p>
                    <p class="mb-0">
                        <a href="course.php" class="alert-link">
                            <i class="fas fa-arrow-right me-1"></i>
                            Aller à la gestion des cours
                        </a>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <form action="" method="post" class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Informations de l'examen</h5>
            </div>
            <div class="card-body">
                <!-- ID de l'examen -->
                <div class="mb-3">
                    <label for="id" class="form-label">ID de l'examen</label>
                    <input type="text" name="id" id="id" required
                        class="form-control"
                        placeholder="Ex: EXAM2024-001">
                </div>

                <!-- Date de l'examen -->
                <div class="mb-3">
                    <label for="examDate" class="form-label">Date de l'examen</label>
                    <input type="date" name="examDate" id="examDate" required
                        class="form-control">
                </div>

                <!-- Heure de l'examen -->
                <div class="mb-3">
                    <label for="examTime" class="form-label">Horaire de l'examen</label>
                    <input type="time" name="examTime" id="examTime" required
                        class="form-control">
                </div>

                <!-- Cours -->
                <div class="mb-3">
                    <label for="courseId" class="form-label">Cours</label>
                    <select name="courseId" id="courseId" required
                        class="form-select"
                        <?php echo !$has_courses ? 'disabled' : ''; ?>>
                        <option value="">Sélectionnez un cours</option>
                        <?php while($course = $courses_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($course['id']); ?>">
                            <?php echo htmlspecialchars($course['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <?php if(!$has_courses): ?>
                    <div class="form-text text-muted">Vous devez d'abord créer un cours avant de pouvoir planifier un examen.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="examSchedule.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i>Annuler
                </a>
                <button type="submit" name="submit" value="submit"
                    class="btn btn-primary"
                    <?php echo !$has_courses ? 'disabled' : ''; ?>>
                    <i class="fas fa-save me-2"></i>Créer le planning
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

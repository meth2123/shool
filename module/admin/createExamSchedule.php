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

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <!-- En-tête -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Créer un Planning d'Examen</h2>
            <p class="text-gray-600">Remplissez le formulaire ci-dessous pour créer un nouveau planning d'examen</p>
        </div>

        <!-- Messages de notification -->
        <?php if($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p><?php echo $success_message; ?></p>
        </div>
        <?php endif; ?>

        <?php if($error_message): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?php echo $error_message; ?></p>
        </div>
        <?php endif; ?>

        <?php if(!$has_courses): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Vous n'avez pas encore créé de cours. Veuillez d'abord créer un cours avant de planifier un examen.
                    </p>
                    <p class="mt-2">
                        <a href="course.php" class="text-yellow-700 font-medium hover:text-yellow-600">
                            <i class="fas fa-arrow-right mr-1"></i>
                            Aller à la gestion des cours
                        </a>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <form action="" method="post" class="bg-white shadow-lg rounded-lg p-6">
            <div class="space-y-6">
                <!-- ID de l'examen -->
                <div>
                    <label for="id" class="block text-sm font-medium text-gray-700 mb-2">ID de l'examen</label>
                    <input type="text" name="id" id="id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Ex: EXAM2024-001">
                </div>

                <!-- Date de l'examen -->
                <div>
                    <label for="examDate" class="block text-sm font-medium text-gray-700 mb-2">Date de l'examen</label>
                    <input type="date" name="examDate" id="examDate" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Heure de l'examen -->
                <div>
                    <label for="examTime" class="block text-sm font-medium text-gray-700 mb-2">Horaire de l'examen</label>
                    <div class="flex space-x-4">
                        <input type="time" name="examTime" id="examTime" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Cours -->
                <div>
                    <label for="courseId" class="block text-sm font-medium text-gray-700 mb-2">Cours</label>
                    <select name="courseId" id="courseId" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        <?php echo !$has_courses ? 'disabled' : ''; ?>>
                        <option value="">Sélectionnez un cours</option>
                        <?php while($course = $courses_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($course['id']); ?>">
                            <?php echo htmlspecialchars($course['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <?php if(!$has_courses): ?>
                    <p class="mt-1 text-sm text-gray-500">Vous devez d'abord créer un cours avant de pouvoir planifier un examen.</p>
                    <?php endif; ?>
                </div>

                <!-- Boutons -->
                <div class="flex justify-end space-x-4 pt-4">
                    <a href="examSchedule.php" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Annuler
                    </a>
                    <button type="submit" name="submit" value="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        <?php echo !$has_courses ? 'disabled' : ''; ?>>
                        Créer le planning
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include('templates/layout.php');
?>

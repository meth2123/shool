<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Initialiser le contenu pour le template
ob_start();

// Récupérer l'ID de l'admin connecté
$admin_id = $_SESSION['login_id'];

// Debug
error_log("Admin ID pour les statistiques : " . $admin_id);

// Calculer les statistiques
try {
    // 1. Examens à venir
    $stmt = $link->prepare("SELECT COUNT(*) as count 
                           FROM examschedule 
                           WHERE examdate > CURDATE() 
                           AND created_by = ?");
    $stmt->bind_param("s", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $upcoming_exams = $result->fetch_assoc()['count'];
    error_log("Examens à venir : " . $upcoming_exams);

    // 2. Examens en cours (aujourd'hui)
    $stmt = $link->prepare("SELECT COUNT(*) as count 
                           FROM examschedule 
                           WHERE DATE(examdate) = CURDATE() 
                           AND created_by = ?");
    $stmt->bind_param("s", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_exams = $result->fetch_assoc()['count'];
    error_log("Examens en cours : " . $current_exams);

} catch (Exception $e) {
    error_log("Erreur lors du calcul des statistiques : " . $e->getMessage());
    // En cas d'erreur, initialiser les variables à 0
    $upcoming_exams = 0;
    $current_exams = 0;
}

?>

<div class="container mx-auto px-4">
    <!-- En-tête de la page -->
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800 mb-4">Gestion des Examens</h2>
        <p class="text-gray-600">Gérez les plannings et les horaires des examens</p>
    </div>

    <!-- Grille des actions principales -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Carte Créer -->
        <a href="createExamSchedule.php" class="block">
            <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition duration-300">
                <div class="text-blue-500 mb-4">
                    <i class="fas fa-plus-circle text-4xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Créer un Planning</h3>
                <p class="text-gray-600">Planifiez de nouveaux examens et définissez leurs horaires</p>
            </div>
        </a>

        <!-- Carte Consulter -->
        <a href="viewExamSchedule.php" class="block">
            <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition duration-300">
                <div class="text-green-500 mb-4">
                    <i class="fas fa-calendar-alt text-4xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Consulter les Plannings</h3>
                <p class="text-gray-600">Visualisez tous les examens planifiés</p>
            </div>
        </a>

        <!-- Carte Modifier -->
        <a href="updateExamSchedule.php" class="block">
            <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition duration-300">
                <div class="text-orange-500 mb-4">
                    <i class="fas fa-edit text-4xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Modifier un Planning</h3>
                <p class="text-gray-600">Mettez à jour les informations des examens existants</p>
            </div>
        </a>
    </div>

    <!-- Section des statistiques -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Aperçu des Examens</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-blue-50 rounded-lg">
                <div class="flex items-center">
                    <div class="text-blue-500 mr-4">
                        <i class="fas fa-calendar-check text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Examens à venir</p>
                        <h4 class="text-xl font-bold text-gray-800">
                            <?php echo $upcoming_exams; ?>
                        </h4>
                    </div>
                </div>
            </div>
            
            <div class="p-4 bg-green-50 rounded-lg">
                <div class="flex items-center">
                    <div class="text-green-500 mr-4">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">En cours</p>
                        <h4 class="text-xl font-bold text-gray-800">
                            <?php echo $current_exams; ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include('templates/layout.php');
?>

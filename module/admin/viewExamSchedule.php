<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Initialiser le contenu pour le template
ob_start();

// Récupérer les examens du mois en cours avec les informations du cours
$admin_id = $_SESSION['login_id'];

// Debug
error_log("Admin ID: " . $admin_id);

$stmt = $link->prepare("SELECT e.*, c.name as course_name, t.name as teacher_name 
        FROM examschedule e 
        LEFT JOIN course c ON e.courseid = c.id 
        LEFT JOIN teachers t ON c.teacherid = t.id 
        WHERE e.created_by = ?
        ORDER BY e.examdate ASC, e.time ASC");

$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

// Debug
error_log("Nombre d'examens trouvés : " . $result->num_rows);

// Formater le mois et l'année en français
$date = new DateTime();
$formatter = new IntlDateFormatter(
    'fr_FR',
    IntlDateFormatter::FULL,
    IntlDateFormatter::NONE,
    null,
    null,
    'MMMM YYYY'
);
$current_month = "Tous les examens"; // Modifié pour refléter qu'on affiche tous les examens
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- En-tête -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Planning des Examens</h2>
            <p class="text-gray-600">Examens prévus pour <?php echo $current_month; ?></p>
        </div>

        <!-- Filtres et Actions -->
        <div class="mb-6 flex justify-between items-center">
            <div class="flex space-x-4">
                <a href="createExamSchedule.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>
                    Ajouter un examen
                </a>
            </div>
        </div>

        <!-- Tableau des examens -->
        <?php if($result && $result->num_rows > 0): ?>
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Heure</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cours</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enseignant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php 
                    while($row = $result->fetch_assoc()):
                        // Formatage de la date
                        $date = new DateTime($row['examdate']);
                        $formatted_date = $date->format('d/m/Y');
                        
                        // Statut de l'examen
                        $today = new DateTime();
                        $exam_date = new DateTime($row['examdate']);
                        $status_class = '';
                        $status_text = '';
                        
                        if($exam_date < $today) {
                            $status_class = 'bg-gray-100 text-gray-600';
                            $status_text = 'Terminé';
                        } elseif($exam_date == $today) {
                            $status_class = 'bg-green-100 text-green-800';
                            $status_text = 'Aujourd\'hui';
                        } else {
                            $status_class = 'bg-blue-100 text-blue-800';
                            $status_text = 'À venir';
                        }
                    ?>
                    <tr class="hover:bg-gray-50 <?php echo $status_class; ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($row['id']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $formatted_date; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($row['time']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($row['course_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($row['teacher_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-3">
                                <a href="updateExamSchedule.php?id=<?php echo $row['id']; ?>" 
                                   class="text-indigo-600 hover:text-indigo-900">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="#" onclick="confirmDelete('<?php echo $row['id']; ?>')" 
                                   class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Aucun examen n'est prévu pour ce mois.
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Script pour la confirmation de suppression -->
<script>
function confirmDelete(id) {
    if(confirm('Êtes-vous sûr de vouloir supprimer cet examen ?')) {
        window.location.href = 'deleteExamSchedule.php?id=' + id;
    }
}
</script>

<?php
$content = ob_get_clean();
include('templates/layout.php');
?>

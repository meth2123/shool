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

// Formater le mois et l'année en français sans utiliser IntlDateFormatter
$date = new DateTime();
$mois_fr = array(
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
);
$mois = $mois_fr[intval($date->format('m'))];
$annee = $date->format('Y');
$date_fr = $mois . ' ' . $annee;
$current_month = "Tous les examens"; // Modifié pour refléter qu'on affiche tous les examens
?>

<div class="container py-4">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="card-title h3 mb-3">Planning des Examens</h2>
                    <p class="text-muted">Examens prévus pour <?php echo $current_month; ?></p>
                    
                    <!-- Filtres et Actions -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            <a href="createExamSchedule.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Ajouter un examen
                            </a>
                        </div>
                        <div>
                            <span class="badge bg-info text-white rounded-pill">
                                <i class="fas fa-calendar-alt me-1"></i> <?php echo $date_fr; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des examens -->
    <div class="row">
        <div class="col-md-12">
            <?php if($result && $result->num_rows > 0): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0">ID</th>
                                    <th class="border-0">Date</th>
                                    <th class="border-0">Heure</th>
                                    <th class="border-0">Cours</th>
                                    <th class="border-0">Enseignant</th>
                                    <th class="border-0 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
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
                                    $badge_class = '';
                                    
                                    if($exam_date < $today) {
                                        $status_class = 'table-secondary';
                                        $status_text = 'Terminé';
                                        $badge_class = 'bg-secondary';
                                    } elseif($exam_date->format('Y-m-d') == $today->format('Y-m-d')) {
                                        $status_class = 'table-success';
                                        $status_text = 'Aujourd\'hui';
                                        $badge_class = 'bg-success';
                                    } else {
                                        $status_class = 'table-primary';
                                        $status_text = 'À venir';
                                        $badge_class = 'bg-primary';
                                    }
                                ?>
                                <tr class="<?php echo $status_class; ?>">
                                    <td>
                                        <span class="fw-bold"><?php echo htmlspecialchars($row['id']); ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar-day me-2 text-primary"></i>
                                            <?php echo $formatted_date; ?>
                                            <span class="badge <?php echo $badge_class; ?> ms-2"><?php echo $status_text; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <i class="fas fa-clock me-2 text-muted"></i>
                                        <?php echo htmlspecialchars($row['time']); ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-book me-2 text-primary"></i>
                                        <?php echo htmlspecialchars($row['course_name']); ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-chalkboard-teacher me-2 text-muted"></i>
                                        <?php echo htmlspecialchars($row['teacher_name']); ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="updateExamSchedule.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-outline-primary" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" onclick="confirmDelete('<?php echo $row['id']; ?>')" 
                                               class="btn btn-outline-danger" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-warning d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-triangle me-3"></i>
                <div>
                    Aucun examen n'est prévu pour ce mois.
                </div>
            </div>
            <?php endif; ?>
        </div>
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

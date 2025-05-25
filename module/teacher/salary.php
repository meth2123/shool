<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification de la session
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Définir la variable check pour le template layout.php
$check = $_SESSION['login_id'];
$teacher_id = $check;

// Récupérer les informations du professeur
$teacher_query = "SELECT * FROM teachers WHERE id = ?";
$teacher = db_fetch_row($teacher_query, [$teacher_id], 's');

// Récupérer l'historique des salaires
$salary_query = "SELECT * FROM teacher_salary_history 
                WHERE teacher_id = ? 
                ORDER BY year DESC, month DESC";

$salary_data = db_fetch_all($salary_query, [$teacher_id], 's');

// Calculer le total des paiements
$total_query = "SELECT SUM(final_salary) as total_amount 
                FROM teacher_salary_history 
                WHERE teacher_id = ? AND payment_date IS NOT NULL";

$total_result = db_fetch_row($total_query, [$teacher_id], 's');
$total_paid = $total_result ? $total_result['total_amount'] : 0;

// Calculer les statistiques du mois en cours
$current_month = date('m');
$current_year = date('Y');

$current_month_query = "SELECT * FROM teacher_salary_history 
                       WHERE teacher_id = ? AND month = ? AND year = ?";

$current_month_data = db_fetch_row($current_month_query, [$teacher_id, $current_month, $current_year], 'sis');

// Tableau des mois en français avec vérification de l'index
$month_names = [
    '01' => 'Janvier', '02' => 'Février', '03' => 'Mars',
    '04' => 'Avril', '05' => 'Mai', '06' => 'Juin',
    '07' => 'Juillet', '08' => 'Août', '09' => 'Septembre',
    '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
];

// Fonction pour formater le mois
function formatMonth($month) {
    global $month_names;
    // S'assurer que le mois est sur 2 chiffres
    $month = str_pad($month, 2, '0', STR_PAD_LEFT);
    return $month_names[$month] ?? 'Mois inconnu';
}

// Préparation du contenu pour le template
$content = '';
$content .= '<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Informations du Professeur</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="p-4 rounded bg-primary bg-opacity-10 h-100">
                            <h6 class="text-primary mb-2">Salaire de Base</h6>
                            <p class="display-6 fw-bold mb-0">';
                                $base_salary = $teacher["salary"] ?? 0;
                                $content .= number_format((float)$base_salary, 2, ",", " ") . ' €';
                            $content .= '</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-4 rounded bg-success bg-opacity-10 h-100">
                            <h6 class="text-success mb-2">Total des Paiements</h6>
                            <p class="display-6 fw-bold mb-0">';
                                $content .= number_format((float)$total_paid, 2, ",", " ") . ' €';
                            $content .= '</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-4 rounded bg-info bg-opacity-10 h-100">
                            <h6 class="text-info mb-2">Mois en Cours</h6>
                            <p class="display-6 fw-bold mb-0">';
                                if ($current_month_data && isset($current_month_data["final_salary"])) {
                                    $content .= number_format((float)$current_month_data["final_salary"], 2, ",", " ") . ' €';
                                } else {
                                    $content .= '<span class="text-muted fs-5">Non calculé</span>';
                                }
                            $content .= '</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Historique des Salaires</h5>
                <span class="badge bg-primary rounded-pill">' . count($salary_data) . ' entrées</span>
            </div>
            <div class="card-body">';

if (!empty($salary_data)) {
    $content .= '<div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Mois/Année</th>
                    <th>Salaire de Base</th>
                    <th>Jours Présents</th>
                    <th>Jours Absents</th>
                    <th>Salaire Final</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($salary_data as $salary) {
        $content .= '<tr>
            <td>' . formatMonth($salary["month"]) . ' ' . $salary["year"] . '</td>
            <td>' . number_format((float)$salary["base_salary"], 2, ",", " ") . ' €</td>
            <td>' . $salary["days_present"] . ' jours</td>
            <td>' . $salary["days_absent"] . ' jours</td>
            <td class="fw-bold">' . number_format((float)$salary["final_salary"], 2, ",", " ") . ' €</td>
            <td>';
        
        if ($salary["payment_date"]) {
            $content .= '<span class="badge bg-success">Payé le ' . date("d/m/Y", strtotime($salary["payment_date"])) . '</span>';
        } else {
            $content .= '<span class="badge bg-danger">Non payé</span>';
        }
        
        $content .= '</td>
        </tr>';
    }
    
    $content .= '</tbody>
        </table>
    </div>';
} else {
    $content .= '<div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Aucun historique de salaire trouvé.
    </div>';
}

$content .= '</div>
        </div>
    </div>
</div>';

// Inclure le template
include("templates/layout.php");


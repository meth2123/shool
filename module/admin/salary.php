<?php
include_once('main.php');
include_once('includes/auth_check.php');
include_once('../../service/mysqlcon.php');

// L'ID de l'administrateur est déjà défini dans auth_check.php
$admin_id = $_SESSION['login_id'];

// Gestion du mois et année sélectionnés
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Fonction pour obtenir le nombre de jours dans un mois
function getDaysInMonth($month = null, $year = null) {
    // Si les paramètres ne sont pas fournis, utiliser le mois et l'année courants
    if ($month === null) $month = date('m');
    if ($year === null) $year = date('Y');
    
    // Utiliser date('t') qui retourne le nombre de jours dans un mois
    return date('t', mktime(0, 0, 0, $month, 1, $year));
}

// Traitement du paiement des salaires
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_salary'])) {
    $link->begin_transaction();
    try {
        $employee_id = $_POST['employee_id'];
        $employee_type = $_POST['employee_type'];
        
        if ($employee_type === 'teacher') {
            $insert = "INSERT INTO teacher_salary_history 
                (teacher_id, month, year, base_salary, days_present, days_absent, final_salary, payment_date, created_by)
                SELECT 
                    t.id,
                    ?,
                    ?,
                    t.salary as base_salary,
                    COUNT(a.date),
                    ? - COUNT(a.date),
                    ROUND(t.salary * COUNT(a.date) / ?),
                    CURDATE(),
                    ?
                FROM teachers t
                LEFT JOIN attendance a ON t.id = a.attendedid 
                AND MONTH(a.date) = ?
                AND YEAR(a.date) = ?
                WHERE t.id = ? AND t.created_by = ?
                GROUP BY t.id, t.salary
                ON DUPLICATE KEY UPDATE
                    base_salary = VALUES(base_salary),
                    days_present = VALUES(days_present),
                    days_absent = VALUES(days_absent),
                    final_salary = VALUES(final_salary),
                    payment_date = CURDATE()";
        } else {
            $insert = "INSERT INTO staff_salary_history 
                (staff_id, month, year, base_salary, days_present, days_absent, final_salary, payment_date, created_by)
                SELECT 
                    s.id,
                    ?,
                    ?,
                    s.salary as base_salary,
                    COUNT(a.date),
                    ? - COUNT(a.date),
                    ROUND(s.salary * COUNT(a.date) / ?),
                    CURDATE(),
                    ?
                FROM staff s
                LEFT JOIN attendance a ON s.id = a.attendedid 
                AND MONTH(a.date) = ?
                AND YEAR(a.date) = ?
                WHERE s.id = ? AND s.created_by = ?
                GROUP BY s.id, s.salary
                ON DUPLICATE KEY UPDATE
                    base_salary = VALUES(base_salary),
                    days_present = VALUES(days_present),
                    days_absent = VALUES(days_absent),
                    final_salary = VALUES(final_salary),
                    payment_date = CURDATE()";
        }

        $days_in_month = getDaysInMonth($selected_month, $selected_year);
        
        $stmt = $link->prepare($insert);
        $stmt->bind_param("iiiiissss", 
            $selected_month, 
            $selected_year, 
            $days_in_month,
            $days_in_month,
            $admin_id,
            $selected_month,
            $selected_year,
            $employee_id,
            $admin_id
        );
        $stmt->execute();
        $link->commit();
        $success_message = "Le salaire a été payé et enregistré avec succès";
    } catch (Exception $e) {
        $link->rollback();
        $error_message = "Erreur lors du paiement du salaire : " . $e->getMessage();
    }
}

// Requête pour les enseignants
$sql = "SELECT 
    t.id, 
    t.name, 
    t.salary,
    COALESCE(th.final_salary, ROUND(t.salary * COUNT(a.date) / ?)) AS currentmonthlysalary,
    COALESCE(th.days_present, COUNT(a.date)) as present_days,
    COALESCE(th.days_absent, ? - COUNT(a.date)) as absent_days,
    th.payment_date
FROM teachers t
LEFT JOIN attendance a ON t.id = a.attendedid 
    AND MONTH(a.date) = ?
    AND YEAR(a.date) = ?
LEFT JOIN teacher_salary_history th ON t.id = th.teacher_id 
    AND th.month = ?
    AND th.year = ?
WHERE t.created_by = ?
GROUP BY t.id";

$days_in_month = getDaysInMonth($selected_month, $selected_year);
$stmt = $link->prepare($sql);
$stmt->bind_param("iiiiiss", 
    $days_in_month,
    $days_in_month,
    $selected_month,
    $selected_year,
    $selected_month,
    $selected_year,
    $admin_id
);
$stmt->execute();
$teacher_result = $stmt->get_result();

// Requête pour le personnel
$sql = "SELECT 
    s.id, 
    s.name, 
    s.salary,
    COALESCE(sh.final_salary, ROUND(s.salary * COUNT(a.date) / ?)) AS currentmonthlysalary,
    COALESCE(sh.days_present, COUNT(a.date)) as present_days,
    COALESCE(sh.days_absent, ? - COUNT(a.date)) as absent_days,
    sh.payment_date
FROM staff s
LEFT JOIN attendance a ON s.id = a.attendedid 
    AND MONTH(a.date) = ?
    AND YEAR(a.date) = ?
LEFT JOIN staff_salary_history sh ON s.id = sh.staff_id 
    AND sh.month = ?
    AND sh.year = ?
WHERE s.created_by = ?
GROUP BY s.id";

$stmt = $link->prepare($sql);
$stmt->bind_param("iiiiiss", 
    $days_in_month,
    $days_in_month,
    $selected_month,
    $selected_year,
    $selected_month,
    $selected_year,
    $admin_id
);
$stmt->execute();
$staff_result = $stmt->get_result();

// Tableau des mois en français
$months = array(
    1 => "Janvier", 2 => "Février", 3 => "Mars",
    4 => "Avril", 5 => "Mai", 6 => "Juin",
    7 => "Juillet", 8 => "Août", 9 => "Septembre",
    10 => "Octobre", 11 => "Novembre", 12 => "Décembre"
);

// Contenu de la page
$content = '
<div class="container py-4">
    <!-- Sélection du mois -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h4 mb-3">Sélectionner la période</h2>
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="month" class="form-label">Mois</label>
                    <select id="month" name="month" class="form-select">
                        ';
                        foreach ($months as $num => $name) {
                            $content .= '<option value="' . $num . '" ' . ($num == $selected_month ? 'selected' : '') . '>' . 
                                      htmlspecialchars($name) . '</option>';
                        }
                        $content .= '
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="year" class="form-label">Année</label>
                    <select id="year" name="year" class="form-select">
                        ';
                        $current_year = intval(date('Y'));
                        // Permettre la sélection d'années de 2020 à 2060
                        for ($y = 2060; $y >= 2020; $y--) {
                            $content .= '<option value="' . $y . '" ' . ($y == $selected_year ? 'selected' : '') . '>' . 
                                      $y . '</option>';
                        }
                        $content .= '
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Messages -->
    ';
    if (isset($success_message)) {
        $content .= '
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            ' . htmlspecialchars($success_message) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
    
    if (isset($error_message)) {
        $content .= '
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            ' . htmlspecialchars($error_message) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
    
    $content .= '

    <!-- Actions -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="btn-group">
                <a href="updateTeacherSalary.php" class="btn btn-outline-primary">
                    <i class="fas fa-edit me-2"></i>Modifier Salaires Enseignants
                </a>
                <a href="updateStaffSalary.php" class="btn btn-outline-primary">
                    <i class="fas fa-edit me-2"></i>Modifier Salaires Personnel
                </a>
            </div>
        </div>
        <div class="col-md-4 text-end">
            ';
            if ($selected_month == intval(date('m')) && $selected_year == intval(date('Y'))) {
                $content .= '
                <form method="POST">
                    <button type="submit" name="pay_salaries" class="btn btn-success">
                        <i class="fas fa-money-bill-wave me-2"></i>Payer les salaires du mois
                    </button>
                </form>';
            }
            $content .= '
        </div>
    </div>

    <!-- Section Salaires Enseignants -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h2 class="h4 mb-0">Salaires des Enseignants</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Salaire Base</th>
                            <th>Jours Présent</th>
                            <th>Jours Absent</th>
                            <th>Salaire à Payer</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        ';
                        if ($teacher_result->num_rows > 0) {
                            while ($row = $teacher_result->fetch_assoc()) {
                                $content .= '
                                <tr>
                                    <td>' . htmlspecialchars($row['id']) . '</td>
                                    <td>' . htmlspecialchars($row['name']) . '</td>
                                    <td>' . number_format($row['salary'], 2) . ' €</td>
                                    <td class="text-success">' . $row['present_days'] . '</td>
                                    <td class="text-danger">' . $row['absent_days'] . '</td>
                                    <td class="fw-bold text-primary">' . number_format($row['currentmonthlysalary'], 2) . ' €</td>
                                    <td>';
                                    
                                    if ($row['payment_date']) {
                                        $content .= '
                                        <span class="badge bg-success">
                                            Payé le ' . date('d/m/Y', strtotime($row['payment_date'])) . '
                                        </span>';
                                    } else {
                                        $content .= '
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-warning">En attente</span>';
                                            
                                            if ($selected_month == intval(date('m')) && $selected_year == intval(date('Y'))) {
                                                $content .= '
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="employee_id" value="' . $row['id'] . '">
                                                    <input type="hidden" name="employee_type" value="teacher">
                                                    <button type="submit" name="pay_salary" class="btn btn-sm btn-success">
                                                        <i class="fas fa-money-bill-wave me-1"></i>Payer
                                                    </button>
                                                </form>';
                                            }
                                            
                                        $content .= '
                                        </div>';
                                    }
                                    
                                $content .= '
                                    </td>
                                </tr>';
                            }
                        } else {
                            $content .= '
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">Aucun enseignant trouvé</td>
                            </tr>';
                        }
                        $content .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section Salaires Personnel -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h2 class="h4 mb-0">Salaires du Personnel</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Salaire Base</th>
                            <th>Jours Présent</th>
                            <th>Jours Absent</th>
                            <th>Salaire à Payer</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        ';
                        if ($staff_result->num_rows > 0) {
                            while ($row = $staff_result->fetch_assoc()) {
                                $content .= '
                                <tr>
                                    <td>' . htmlspecialchars($row['id']) . '</td>
                                    <td>' . htmlspecialchars($row['name']) . '</td>
                                    <td>' . number_format($row['salary'], 2) . ' €</td>
                                    <td class="text-success">' . $row['present_days'] . '</td>
                                    <td class="text-danger">' . $row['absent_days'] . '</td>
                                    <td class="fw-bold text-primary">' . number_format($row['currentmonthlysalary'], 2) . ' €</td>
                                    <td>';
                                    
                                    if ($row['payment_date']) {
                                        $content .= '
                                        <span class="badge bg-success">
                                            Payé le ' . date('d/m/Y', strtotime($row['payment_date'])) . '
                                        </span>';
                                    } else {
                                        $content .= '
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-warning">En attente</span>';
                                            
                                            if ($selected_month == intval(date('m')) && $selected_year == intval(date('Y'))) {
                                                $content .= '
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="employee_id" value="' . $row['id'] . '">
                                                    <input type="hidden" name="employee_type" value="staff">
                                                    <button type="submit" name="pay_salary" class="btn btn-sm btn-success">
                                                        <i class="fas fa-money-bill-wave me-1"></i>Payer
                                                    </button>
                                                </form>';
                                            }
                                            
                                        $content .= '
                                        </div>';
                                    }
                                    
                                $content .= '
                                    </td>
                                </tr>';
                            }
                        } else {
                            $content .= '
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">Aucun membre du personnel trouvé</td>
                            </tr>';
                        }
                        $content .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>';

// Inclure le template layout
include('templates/layout.php');
?>

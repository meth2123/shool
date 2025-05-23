<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

$admin_id = $_SESSION['login_id'];

// Gestion du mois et année sélectionnés
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Fonction pour obtenir le nombre de jours dans un mois
function getDaysInMonth($month, $year) {
    return cal_days_in_month(CAL_GREGORIAN, $month, $year);
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

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Salaires</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <div class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="../../source/logo.jpg" class="h-16 w-16 object-contain mr-4" alt="School Management System"/>
                    <h1 class="text-2xl font-bold text-gray-800">Système de Gestion Scolaire</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-tachometer-alt mr-2"></i>Tableau de bord
                    </a>
                    <span class="text-gray-600">Bonjour, <?php echo htmlspecialchars($login_session); ?></span>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Sélection du mois -->
        <div class="mb-8">
            <form method="GET" class="flex items-center space-x-4">
                <select name="month" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <?php foreach ($months as $num => $name): ?>
                        <option value="<?php echo $num; ?>" <?php echo ($num == $selected_month ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="year" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <?php 
                    $current_year = intval(date('Y'));
                    for ($y = $current_year; $y >= $current_year - 2; $y--): 
                    ?>
                        <option value="<?php echo $y; ?>" <?php echo ($y == $selected_year ? 'selected' : ''); ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-filter mr-2"></i>Filtrer
                </button>
            </form>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="mb-8 flex justify-between items-center">
            <div class="flex space-x-4">
                <a href="updateTeacherSalary.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-edit mr-2"></i>Modifier Salaires Enseignants
                </a>
                <a href="updateStaffSalary.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-edit mr-2"></i>Modifier Salaires Personnel
                </a>
            </div>
            <?php if ($selected_month == intval(date('m')) && $selected_year == intval(date('Y'))): ?>
            <form method="POST" class="flex justify-end">
                <button type="submit" name="pay_salaries" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-money-bill-wave mr-2"></i>Payer les salaires du mois
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Section Salaires Enseignants -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Salaires des Enseignants</h2>
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salaire Base</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jours Présent</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jours Absent</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salaire à Payer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $teacher_result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['id']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo number_format($row['salary'], 2); ?> €
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                        <?php echo $row['present_days']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                        <?php echo $row['absent_days']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                                        <?php echo number_format($row['currentmonthlysalary'], 2); ?> €
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php if ($row['payment_date']): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Payé le <?php echo date('d/m/Y', strtotime($row['payment_date'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    En attente
                                                </span>
                                                <?php if ($selected_month == intval(date('m')) && $selected_year == intval(date('Y'))): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="employee_id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="employee_type" value="teacher">
                                                    <button type="submit" name="pay_salary" class="bg-green-500 hover:bg-green-600 text-white text-xs px-2 py-1 rounded">
                                                        <i class="fas fa-money-bill-wave mr-1"></i>Payer
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Section Salaires Personnel -->
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Salaires du Personnel</h2>
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salaire Base</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jours Présent</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jours Absent</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salaire à Payer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $staff_result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['id']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo number_format($row['salary'], 2); ?> €
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                        <?php echo $row['present_days']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                        <?php echo $row['absent_days']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                                        <?php echo number_format($row['currentmonthlysalary'], 2); ?> €
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php if ($row['payment_date']): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Payé le <?php echo date('d/m/Y', strtotime($row['payment_date'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    En attente
                                                </span>
                                                <?php if ($selected_month == intval(date('m')) && $selected_year == intval(date('Y'))): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="employee_id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="employee_type" value="staff">
                                                    <button type="submit" name="pay_salary" class="bg-green-500 hover:bg-green-600 text-white text-xs px-2 py-1 rounded">
                                                        <i class="fas fa-money-bill-wave mr-1"></i>Payer
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

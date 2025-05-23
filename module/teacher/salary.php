<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification de la session
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$teacher_id = $_SESSION['login_id'];

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

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Salaires - Système de Gestion Scolaire</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <img src="../../source/logo.jpg" class="h-12 w-12 object-contain" alt="School Management System"/>
                    <h1 class="ml-4 text-xl font-semibold text-gray-800">Gestion des Salaires</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-home mr-2"></i>Accueil
                    </a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Informations du professeur -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Informations du Professeur</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-blue-800">Salaire de Base</h3>
                    <p class="text-2xl font-bold text-blue-900">
                        <?php 
                        $base_salary = $teacher['salary'] ?? 0;
                        echo number_format((float)$base_salary, 2); 
                        ?> €
                    </p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-green-800">Total des Paiements</h3>
                    <p class="text-2xl font-bold text-green-900">
                        <?php 
                        echo number_format((float)$total_paid, 2); 
                        ?> €
                    </p>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-purple-800">Mois en Cours</h3>
                    <p class="text-2xl font-bold text-purple-900">
                        <?php 
                        if ($current_month_data && isset($current_month_data['final_salary'])) {
                            echo number_format((float)$current_month_data['final_salary'], 2) . ' €';
                        } else {
                            echo 'Non calculé';
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Historique des salaires -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Historique des Salaires</h2>
                <?php if (!empty($salary_data)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mois/Année</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salaire de Base</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jours Présents</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jours Absents</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salaire Final</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($salary_data as $salary): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php 
                                            echo formatMonth($salary['month']) . ' ' . $salary['year']; 
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo number_format((float)$salary['base_salary'], 2); ?> €
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $salary['days_present']; ?> jours
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $salary['days_absent']; ?> jours
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo number_format((float)$salary['final_salary'], 2); ?> €
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php 
                                            if ($salary['payment_date']) {
                                                echo '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">';
                                                echo 'Payé le ' . date('d/m/Y', strtotime($salary['payment_date']));
                                                echo '</span>';
                                            } else {
                                                echo '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Non payé</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-gray-500">Aucun historique de salaire trouvé.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white shadow-lg mt-8">
        <div class="max-w-7xl mx-auto py-4 px-4">
            <p class="text-center text-gray-500 text-sm">
                © <?php echo date('Y'); ?> Système de Gestion Scolaire. Tous droits réservés.
            </p>
        </div>
    </footer>
</body>
</html>


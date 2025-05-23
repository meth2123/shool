<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Récupérer les informations du staff
$stinfo = db_fetch_row("SELECT * FROM staff WHERE id = ?", [$check]);

if (!$stinfo) {
    header("Location: ../../?error=staff_not_found");
    exit();
}

// Compter les jours de présence du mois courant
$attendances = db_fetch_all(
    "SELECT DISTINCT(date) FROM attendance 
     WHERE attendedid = ? 
     AND MONTH(date) = MONTH(CURRENT_DATE) 
     AND YEAR(date) = YEAR(CURRENT_DATE)",
    [$check]
);

$count = count($attendances);

// Calculer les salaires
$monthly_salary = round($stinfo['salary'] / 12, 2);
$daily_rate = $stinfo['salary'] / 300; // Base de calcul : 300 jours par an
$payable_salary = round($daily_rate * $count, 2);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Salaire - Système de Gestion Scolaire</title>
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
                    <h1 class="ml-4 text-xl font-semibold text-gray-800">Système de Gestion Scolaire</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Bonjour, <?php echo htmlspecialchars($stinfo['name']); ?></span>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Menu de navigation -->
    <div class="bg-white shadow-md mt-6 mx-4 lg:mx-auto max-w-7xl rounded-lg">
        <div class="flex flex-wrap justify-center gap-4 p-4">
            <a href="index.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-home mr-2"></i>Accueil
            </a>
            <a href="modify.php" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-edit mr-2"></i>Modifier mes informations
            </a>
            <a href="salary.php" class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-money-bill-wave mr-2"></i>Mon salaire
            </a>
            <a href="attendance.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-calendar-check mr-2"></i>Mes présences
            </a>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Mon Salaire</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Salaire mensuel -->
                    <div class="bg-blue-50 p-6 rounded-lg">
                        <div class="text-center">
                            <h3 class="text-lg font-medium text-blue-800 mb-2">Salaire mensuel de base</h3>
                            <p class="text-3xl font-bold text-blue-600">
                                <?php echo number_format($monthly_salary, 2, ',', ' '); ?> €
                            </p>
                            <p class="text-sm text-blue-500 mt-2">Basé sur un salaire annuel de <?php echo number_format($stinfo['salary'], 2, ',', ' '); ?> €</p>
                        </div>
                    </div>

                    <!-- Salaire du mois en cours -->
                    <div class="bg-green-50 p-6 rounded-lg">
                        <div class="text-center">
                            <h3 class="text-lg font-medium text-green-800 mb-2">Salaire du mois en cours</h3>
                            <p class="text-3xl font-bold text-green-600">
                                <?php echo number_format($payable_salary, 2, ',', ' '); ?> €
                            </p>
                            <p class="text-sm text-green-500 mt-2">Basé sur <?php echo $count; ?> jours de présence ce mois-ci</p>
                        </div>
                    </div>
                </div>

                <!-- Informations complémentaires -->
                <div class="mt-8 bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Détails du calcul</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Taux journalier</span>
                            <span class="font-medium"><?php echo number_format($daily_rate, 2, ',', ' '); ?> €</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Jours de présence (mois en cours)</span>
                            <span class="font-medium"><?php echo $count; ?> jours</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Base de calcul annuelle</span>
                            <span class="font-medium">300 jours</span>
                        </div>
                    </div>
                </div>
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


<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Récupération des informations du parent
$parent_info = db_fetch_row(
    "SELECT * FROM parents WHERE id = ?",
    [$check],
    's'
);

if (!$parent_info) {
    header("Location: ../../?error=parent_not_found");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page non trouvée - Système de Gestion Scolaire</title>
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
                    <span class="text-gray-600">Bonjour, <?php echo htmlspecialchars($parent_info['fathername']); ?></span>
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
                <i class="fas fa-key mr-2"></i>Changer le mot de passe
            </a>
            <a href="checkchild.php" class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-child mr-2"></i>Information enfant
            </a>
            <a href="childpayment.php" class="bg-indigo-500 hover:bg-indigo-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-money-bill-wave mr-2"></i>Paiements
            </a>
            <a href="childattendance.php" class="bg-pink-500 hover:bg-pink-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-calendar-check mr-2"></i>Présences
            </a>
            <a href="childreport.php" class="bg-teal-500 hover:bg-teal-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-file-alt mr-2"></i>Bulletins
            </a>
        </div>
    </div>

    <!-- Contenu principal - Page 404 -->
    <div class="max-w-7xl mx-auto px-4 py-16">
        <div class="text-center">
            <div class="mb-8">
                <i class="fas fa-exclamation-circle text-8xl text-red-500 mb-4"></i>
                <h1 class="text-6xl font-bold text-gray-800 mb-4">404</h1>
                <h2 class="text-3xl font-semibold text-gray-600 mb-8">Page non trouvée</h2>
                <p class="text-xl text-gray-600 mb-8">
                    La page de gestion des présences est en cours de développement.<br>
                    Nous vous prions de nous excuser pour ce désagrément.
                </p>
            </div>
            
            <div class="space-y-4">
                <p class="text-gray-600">Vous pouvez :</p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="index.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg text-lg font-medium transition duration-150 ease-in-out">
                        <i class="fas fa-home mr-2"></i>Retourner à l'accueil
                    </a>
                    <a href="checkchild.php" class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-3 rounded-lg text-lg font-medium transition duration-150 ease-in-out">
                        <i class="fas fa-child mr-2"></i>Voir les informations des enfants
                    </a>
                </div>
            </div>

            <div class="mt-12 p-6 bg-white rounded-lg shadow-md max-w-2xl mx-auto">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Fonctionnalités à venir</h3>
                <ul class="text-left space-y-3 text-gray-600">
                    <li class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Consultation des présences de vos enfants
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Historique des absences
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Statistiques de présence
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Notifications en cas d'absence
                    </li>
                </ul>
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
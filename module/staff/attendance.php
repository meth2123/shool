<?php
include_once('main.php');

// Récupérer les informations du staff à partir de la variable $login_session définie dans main.php
$staff_info = ['name' => $login_session];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Présences - Système de Gestion Scolaire</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                    <span class="text-gray-600">Bonjour, <?php echo htmlspecialchars($staff_info['name']); ?></span>
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
        <!-- Page Not Found -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8 text-center py-16">
            <div class="p-6">
                <div class="mb-8">
                    <i class="fas fa-exclamation-triangle text-yellow-500 text-8xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-800 mb-4">Page Non Trouvée</h2>
                <p class="text-xl text-gray-600 mb-8">Le module de gestion des présences est actuellement en cours de développement.</p>
                
                <div class="bg-yellow-50 p-6 rounded-lg mb-8 max-w-2xl mx-auto">
                    <h3 class="text-lg font-semibold text-yellow-700 mb-2">Information</h3>
                    <p class="text-gray-700">D'après nos informations, cette fonctionnalité sera bientôt disponible. Nous travaillons activement pour l'implémenter dans les meilleurs délais.</p>
                </div>
                
                <a href="index.php" class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-md text-lg font-medium transition duration-150 ease-in-out">
                    <i class="fas fa-home mr-2"></i>Retour à l'accueil
                </a>
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

    <!-- Aucun script JavaScript nécessaire pour cette page -->
</body>
</html>


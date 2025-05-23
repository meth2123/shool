<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Récupérer les informations du staff
$staff_info = db_fetch_row("SELECT name FROM staff WHERE id = ?", [$check], 'i');
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
    <script src="staffAttendance.js"></script>
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
        <!-- Section Présences -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Mes Présences</h2>
                
                <div class="bg-blue-50 p-4 rounded-lg mb-6">
                    <div class="flex items-center justify-center space-x-4">
                        <span class="text-blue-700 font-medium">Période :</span>
                        <label class="inline-flex items-center">
                            <input type="radio" class="form-radio text-blue-600" name="present" value="thismonth" checked
                                   onclick="ajaxRequestToGetAttendancePresentThisMonth();">
                            <span class="ml-2">Mois en cours</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" class="form-radio text-blue-600" name="present" value="all"
                                   onclick="ajaxRequestToGetAttendancePresentAll();">
                            <span class="ml-2">Historique complet</span>
                        </label>
                    </div>
                </div>

                <div id="mypresent" class="overflow-hidden rounded-lg border border-gray-200"></div>
            </div>
        </div>

        <!-- Section Absences -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Mes Absences</h2>
                
                <div class="bg-red-50 p-4 rounded-lg mb-6">
                    <div class="flex items-center justify-center space-x-4">
                        <span class="text-red-700 font-medium">Période :</span>
                        <label class="inline-flex items-center">
                            <input type="radio" class="form-radio text-red-600" name="absent" value="thismonth" checked
                                   onclick="ajaxRequestToGetAttendanceAbsentThisMonth();">
                            <span class="ml-2">Mois en cours</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" class="form-radio text-red-600" name="absent" value="all"
                                   onclick="ajaxRequestToGetAttendanceAbsentAll();">
                            <span class="ml-2">Historique complet</span>
                        </label>
                    </div>
                </div>

                <div id="myabsent" class="overflow-hidden rounded-lg border border-gray-200"></div>
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

    <script>
    // Charger les présences du mois en cours au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        ajaxRequestToGetAttendancePresentThisMonth();
        ajaxRequestToGetAttendanceAbsentThisMonth();
    });
    </script>
</body>
</html>


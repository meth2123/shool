<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Scolaire - Enseignant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-blue-800 text-white transition-transform duration-300 ease-in-out">
        <div class="p-6">
            <h2 class="text-2xl font-semibold">Espace Enseignant</h2>
        </div>
        <nav class="mt-6">
            <a href="index.php" class="flex items-center px-6 py-3 text-gray-100 hover:bg-blue-700">
                <i class="fas fa-home mr-3"></i>
                <span>Accueil</span>
            </a>
            <a href="courses.php" class="flex items-center px-6 py-3 text-gray-100 hover:bg-blue-700">
                <i class="fas fa-book mr-3"></i>
                <span>Mes Cours</span>
            </a>
            <a href="attendance.php" class="flex items-center px-6 py-3 text-gray-100 hover:bg-blue-700">
                <i class="fas fa-calendar-check mr-3"></i>
                <span>Présences</span>
            </a>
            <a href="schedule.php" class="flex items-center px-6 py-3 text-gray-100 hover:bg-blue-700">
                <i class="fas fa-calendar-alt mr-3"></i>
                <span>Emploi du temps</span>
            </a>
            <a href="profile.php" class="flex items-center px-6 py-3 text-gray-100 hover:bg-blue-700">
                <i class="fas fa-user mr-3"></i>
                <span>Mon Profil</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-8">
        <!-- Top Navigation -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Bienvenue, <?php echo htmlspecialchars($loged_user_name ?? ''); ?></h1>
            </div>
            <div class="flex items-center">
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition duration-200">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Déconnexion
                </a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <?php if (isset($content)) { echo $content; } ?>
        </div>
    </div>

    <script src="../../JS/jquery-1.12.3.js"></script>
</body>
</html> 
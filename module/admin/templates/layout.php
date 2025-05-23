<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Scolaire - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-blue-800 text-white transition-transform duration-300 ease-in-out">
        <div class="p-6">
            <h2 class="text-2xl font-semibold">Administration</h2>
        </div>
        <nav class="mt-6">
            <a href="index.php" class="flex items-center px-6 py-3 text-gray-100 hover:bg-blue-700">
                <i class="fas fa-home mr-3"></i>
                <span>Tableau de bord</span>
            </a>
            <a href="manageStudent.php" class="flex items-center px-6 py-3 text-gray-100 hover:bg-blue-700">
                <i class="fas fa-user-graduate mr-3"></i>
                <span>Gestion des étudiants</span>
            </a>
            <a href="manageTeacher.php" class="flex items-center px-6 py-3 text-gray-100 hover:bg-blue-700">
                <i class="fas fa-chalkboard-teacher mr-3"></i>
                <span>Gestion des enseignants</span>
            </a>
            <a href="manageParent.php" class="flex items-center px-6 py-3 text-gray-100 hover:bg-blue-700">
                <i class="fas fa-users mr-3"></i>
                <span>Gestion des parents</span>
            </a>
            <a href="manageStaff.php" class="flex items-center px-6 py-3 text-gray-100 hover:bg-blue-700">
                <i class="fas fa-user-tie mr-3"></i>
                <span>Gestion du personnel</span>
            </a>
            <a href="course.php" class="flex items-center px-6 py-3 text-gray-100 hover:bg-blue-700">
                <i class="fas fa-book mr-3"></i>
                <span>Gestion des cours</span>
            </a>
            <a href="attendance.php" class="flex items-center px-6 py-3 text-gray-100 hover:bg-blue-700">
                <i class="fas fa-calendar-check mr-3"></i>
                <span>Présences</span>
            </a>
            <a href="examSchedule.php" class="flex items-center px-6 py-3 text-gray-100 hover:bg-blue-700">
                <i class="fas fa-calendar-alt mr-3"></i>
                <span>Planning des examens</span>
            </a>
            <a href="payment.php" class="flex items-center px-6 py-3 text-gray-100 hover:bg-blue-700">
                <i class="fas fa-money-bill-wave mr-3"></i>
                <span>Paiements</span>
            </a>
            <a href="salary.php" class="flex items-center px-6 py-3 text-gray-100 hover:bg-blue-700">
                <i class="fas fa-money-check-alt mr-3"></i>
                <span>Salaires</span>
            </a>
            <a href="report.php" class="flex items-center px-6 py-3 text-gray-100 hover:bg-blue-700">
                <i class="fas fa-chart-bar mr-3"></i>
                <span>Rapports</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-8">
        <!-- Top Navigation -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Bienvenue, <?php echo htmlspecialchars($loged_user_name); ?></h1>
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

    <script src="JS/jquery-1.12.3.js"></script>
    <script src="JS/Attendance.js"></script>
</body>
</html> 
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>Administration - Système de Gestion Scolaire</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <img src="/gestion/source/logo.jpg" class="h-12 w-12 object-contain" alt="School Management System"/>
                    <h1 class="ml-4 text-xl font-semibold text-gray-800">Système de Gestion Scolaire</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="manage_notifications.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                        <i class="fas fa-bell mr-2"></i>
                        Notifications
                    </a>
                    <span class="text-gray-600">Bonjour, <?php echo htmlspecialchars($login_session); ?></span>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Menu de navigation -->
    <div class="bg-white shadow-md mb-6">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex space-x-4 py-3">
                <a href="index.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-tachometer-alt mr-2"></i>Tableau de bord
                </a>
                <a href="manageStudent.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-user-graduate mr-2"></i>Élèves
                </a>
                <a href="manageTeacher.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>Enseignants
                </a>
                <a href="manageParent.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-users mr-2"></i>Parents
                </a>
                <a href="manageClass.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-school mr-2"></i>Classes
                </a>
                <a href="manageCourse.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-book mr-2"></i>Cours
                </a>
                <a href="payment.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-money-bill-wave mr-2"></i>Paiements
                </a>
                <a href="report.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-chart-bar mr-2"></i>Rapports
                </a>
            </div>
        </div>
    </div>

    <!-- Messages de notification -->
    <?php if (isset($_SESSION['success'])): ?>
    <div class="max-w-7xl mx-auto px-4 mb-6">
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['success']); ?></span>
            <?php unset($_SESSION['success']); ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="max-w-7xl mx-auto px-4 mb-6">
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['error']); ?></span>
            <?php unset($_SESSION['error']); ?>
        </div>
    </div>
    <?php endif; ?> 
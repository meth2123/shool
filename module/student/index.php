<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Récupération des informations de l'étudiant
$student_info = db_fetch_row(
    "SELECT * FROM students WHERE id = ?",
    [$check],
    's'
);

if (!$student_info) {
    header("Location: ../../?error=student_not_found");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord étudiant - Système de Gestion Scolaire</title>
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
                    <span class="text-gray-600">Bonjour, <?php echo htmlspecialchars($student_info['name']); ?></span>
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
                <i class="fas fa-key mr-2"></i>Changer mot de passe
            </a>
            <a href="course.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-graduation-cap mr-2"></i>Mes cours et résultats
            </a>
            <a href="exam.php" class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-calendar-alt mr-2"></i>Planning des examens
            </a>
            <a href="attendance.php" class="bg-pink-500 hover:bg-pink-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-calendar-check mr-2"></i>Mes présences
            </a>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Informations de l'étudiant -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Mes Informations</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Photo de profil -->
                    <div class="flex flex-col items-center">
                        <img src="../images/<?php echo htmlspecialchars($check) . ".jpg"; ?>" 
                             class="h-48 w-48 rounded-full object-cover border-4 border-gray-200"
                             alt="<?php echo htmlspecialchars($check) . " photo"; ?>"/>
                    </div>
                    
                    <!-- Informations détaillées -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">ID Étudiant</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['id']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Nom</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['name']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Téléphone</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['phone']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Email</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['email']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Genre</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['sex']); ?></p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Date de naissance</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['dob']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Date d'admission</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['addmissiondate']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Adresse</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['address']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">ID Parent</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['parentid']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">ID Classe</label>
                                <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['classid']); ?></p>
                            </div>
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


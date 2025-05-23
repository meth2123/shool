<?php
$login_code = isset($_REQUEST['login']) ? $_REQUEST['login'] : '1';
$reset_success = isset($_REQUEST['reset']) ? $_REQUEST['reset'] : '';
$reset_error = isset($_REQUEST['error']) ? $_REQUEST['error'] : '';

if($login_code=="false"){
    $login_message = "Identifiants incorrects !";
    $login_type = "error";
} else {
    $login_message = "Veuillez vous connecter";
    $login_type = "info";
}

if(isset($_GET['error'])) {
    $error = $_GET['error'];
    $error_message = '';
    $student_name = isset($_GET['student_name']) ? htmlspecialchars($_GET['student_name']) : '';
    
    switch($error) {
        case 'student_not_found':
            $error_message = "L'étudiant n'a pas été trouvé dans la base de données.";
            break;
        case 'student_no_class':
            $error_message = "L'étudiant " . $student_name . " n'a pas de classe assignée. Veuillez contacter l'administrateur pour assigner une classe.";
            break;
        case 'student_class_not_found':
            $error_message = "La classe de l'étudiant n'a pas été trouvée. Veuillez contacter l'administrateur.";
            break;
        case 'login':
            $error_message = "Identifiant ou mot de passe incorrect.";
            break;
        default:
            $error_message = "Une erreur est survenue. Veuillez réessayer.";
    }
    
    if($error_message) {
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Erreur!</strong>
                <span class="block sm:inline">' . $error_message . '</span>
              </div>';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SchoolManager - Système de Gestion Scolaire</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <img src="source/logo.jpg" class="h-8 w-8 object-contain" alt="Logo"/>
                    <span class="ml-2 text-xl font-bold text-gray-900">SchoolManager</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="#features" class="text-gray-600 hover:text-gray-900">Fonctionnalités</a>
                    <a href="#pricing" class="text-gray-600 hover:text-gray-900">Tarifs</a>
                    <a href="login.php" class="text-blue-600 hover:text-blue-700">Se connecter</a>
                    <a href="module/subscription/register.php" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        S'abonner
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="bg-white">
        <div class="max-w-7xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl sm:tracking-tight lg:text-6xl">
                    Gérez votre établissement scolaire en toute simplicité
                </h1>
                <p class="mt-6 max-w-2xl mx-auto text-xl text-gray-500">
                    SchoolManager est une solution complète pour la gestion administrative et pédagogique de votre établissement scolaire.
                </p>
                <div class="mt-10 flex justify-center space-x-4">
                    <a href="login.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Se connecter
                    </a>
                    <a href="module/subscription/register.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        <i class="fas fa-crown mr-2"></i>
                        S'abonner maintenant
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900">
                    Fonctionnalités principales
                </h2>
                <p class="mt-4 text-lg text-gray-500">
                    Tout ce dont vous avez besoin pour gérer efficacement votre établissement
                </p>
            </div>

            <div class="mt-10">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Gestion des étudiants -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                    <i class="fas fa-user-graduate text-white text-2xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">Gestion des étudiants</h3>
                                </div>
                            </div>
                            <p class="mt-4 text-gray-500">
                                Inscription, suivi des notes, gestion des absences et bien plus encore.
                            </p>
                        </div>
                    </div>

                    <!-- Gestion des enseignants -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <i class="fas fa-chalkboard-teacher text-white text-2xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">Gestion des enseignants</h3>
                                </div>
                            </div>
                            <p class="mt-4 text-gray-500">
                                Planning des cours, gestion des emplois du temps, suivi des performances.
                            </p>
                        </div>
                    </div>

                    <!-- Gestion financière -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                    <i class="fas fa-money-bill-wave text-white text-2xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">Gestion financière</h3>
                                </div>
                            </div>
                            <p class="mt-4 text-gray-500">
                                Suivi des paiements, gestion des frais de scolarité, rapports financiers.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing Section -->
    <div id="pricing" class="bg-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900">
                    Tarifs simples et transparents
                </h2>
                <p class="mt-4 text-lg text-gray-500">
                    Un seul forfait pour tous les établissements
                </p>
            </div>

            <div class="mt-10 max-w-lg mx-auto">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-6 py-8 sm:p-10">
                        <div class="text-center">
                            <h3 class="text-2xl font-extrabold text-gray-900">
                                Forfait Standard
                            </h3>
                            <p class="mt-4 text-gray-500">
                                Accès à toutes les fonctionnalités
                            </p>
                            <div class="mt-6">
                                <span class="text-4xl font-extrabold text-gray-900">15 000 FCFA</span>
                                <span class="text-base font-medium text-gray-500">/mois</span>
                            </div>
                        </div>
                        <div class="mt-8">
                            <ul class="space-y-4">
                                <li class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-check text-green-500"></i>
                                    </div>
                                    <p class="ml-3 text-base text-gray-500">
                                        Gestion complète des étudiants et enseignants
                                    </p>
                                </li>
                                <li class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-check text-green-500"></i>
                                    </div>
                                    <p class="ml-3 text-base text-gray-500">
                                        Suivi des notes et des absences
                                    </p>
                                </li>
                                <li class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-check text-green-500"></i>
                                    </div>
                                    <p class="ml-3 text-base text-gray-500">
                                        Gestion financière et rapports
                                    </p>
                                </li>
                                <li class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-check text-green-500"></i>
                                    </div>
                                    <p class="ml-3 text-base text-gray-500">
                                        Support technique 24/7
                                    </p>
                                </li>
                            </ul>
                        </div>
                        <div class="mt-8">
                            <a href="module/subscription/register.php" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                S'abonner maintenant
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-base text-gray-400">
                    &copy; <?php echo date('Y'); ?> SchoolManager. Tous droits réservés.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>

<?php
include_once('main.php');
require_once('../../db/config.php');

// Get admin ID for filtering
$admin_id = $_SESSION['login_id'];

// Initialize database connection
$conn = getDbConnection();

// Get admin name
$sql = "SELECT name FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$login_session = $loged_user_name = $admin['name'];

if(!isset($login_session)){
    header("Location:../../");
    exit;
}

// Close database connection
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Parents</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="JS/login_logout.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <div class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="../../source/logo.jpg" class="h-16 w-16 object-contain mr-4" alt="School Management System"/>
                    <h1 class="text-2xl font-bold text-gray-800">Système de Gestion Scolaire</h1>
                </div>
                <div class="flex items-center">
                    <span class="mr-4">Bonjour, <?php echo htmlspecialchars($login_session);?></span>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="bg-white shadow-md mt-4">
        <div class="container mx-auto px-4">
            <div class="flex space-x-4 py-4">
                <a href="index.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-home mr-2"></i>Accueil
                </a>
                <a href="addParent.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-user-plus mr-2"></i>Nouveau Parent
                </a>
                <a href="viewParent.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-list mr-2"></i>Liste des Parents
                </a>
                <a href="updateParent.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-edit mr-2"></i>Modifier un Parent
                </a>
                <a href="deleteParent.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-trash mr-2"></i>Supprimer un Parent
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                <i class="fas fa-users mr-2 text-blue-500"></i>
                Gestion des Parents
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="addParent.php" class="bg-blue-50 hover:bg-blue-100 p-6 rounded-lg transition duration-200">
                    <i class="fas fa-user-plus text-3xl text-blue-500 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-800">Ajouter un Parent</h3>
                    <p class="text-gray-600 mt-2">Créer un nouveau compte parent</p>
                </a>

                <a href="viewParent.php" class="bg-green-50 hover:bg-green-100 p-6 rounded-lg transition duration-200">
                    <i class="fas fa-list text-3xl text-green-500 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-800">Liste des Parents</h3>
                    <p class="text-gray-600 mt-2">Voir tous les parents</p>
                </a>

                <a href="updateParent.php" class="bg-yellow-50 hover:bg-yellow-100 p-6 rounded-lg transition duration-200">
                    <i class="fas fa-edit text-3xl text-yellow-500 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-800">Modifier un Parent</h3>
                    <p class="text-gray-600 mt-2">Mettre à jour les informations</p>
                </a>

                <a href="deleteParent.php" class="bg-red-50 hover:bg-red-100 p-6 rounded-lg transition duration-200">
                    <i class="fas fa-trash text-3xl text-red-500 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-800">Supprimer un Parent</h3>
                    <p class="text-gray-600 mt-2">Supprimer un compte parent</p>
                </a>
            </div>
        </div>
    </div>
</body>
</html>

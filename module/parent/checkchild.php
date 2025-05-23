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

// Récupération de la liste des enfants avec leurs classes
$children = db_fetch_all(
    "SELECT s.*, c.name as class_name 
     FROM students s 
     LEFT JOIN class c ON s.classid = c.id
     WHERE s.parentid = ? 
     ORDER BY s.name",
    [$check],
    's'
);

if (!$children) {
    $children = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informations des Enfants - Système de Gestion Scolaire</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <span class="mr-4">Bonjour, <?php echo htmlspecialchars($login_session); ?></span>
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
                <a href="childattendance.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-calendar-check mr-2"></i>Présences
                </a>
                <a href="childreport.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-file-alt mr-2"></i>Bulletins
                </a>
                <a href="childpayment.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-money-bill mr-2"></i>Paiements
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Informations des Enfants</h2>
        
        <?php if (empty($children)): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            Aucun enfant n'est actuellement associé à votre compte.
                        </p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($children as $child): ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xl font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($child['name']); ?>
                                </h3>
                                <span class="px-3 py-1 text-sm font-medium rounded-full 
                                    <?php echo $child['sex'] === 'M' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'; ?>">
                                    <?php echo $child['sex'] === 'M' ? 'Garçon' : 'Fille'; ?>
                                </span>
                            </div>
                            
                            <div class="space-y-4">
                                <!-- Informations de base -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 mb-2">Informations personnelles</h4>
                                    <dl class="grid grid-cols-2 gap-2 text-sm">
                                        <dt class="text-gray-600">ID</dt>
                                        <dd class="text-gray-900"><?php echo htmlspecialchars($child['id']); ?></dd>
                                        
                                        <dt class="text-gray-600">Classe</dt>
                                        <dd class="text-gray-900"><?php echo htmlspecialchars($child['class_name'] ?? 'Non assignée'); ?></dd>
                                        
                                        <dt class="text-gray-600">Date de naissance</dt>
                                        <dd class="text-gray-900"><?php echo date('d/m/Y', strtotime($child['dob'])); ?></dd>
                                        
                                        <dt class="text-gray-600">Date d'admission</dt>
                                        <dd class="text-gray-900"><?php echo date('d/m/Y', strtotime($child['addmissiondate'])); ?></dd>
                                        
                                        <dt class="text-gray-600">Email</dt>
                                        <dd class="text-gray-900"><?php echo htmlspecialchars($child['email']); ?></dd>
                                        
                                        <dt class="text-gray-600">Téléphone</dt>
                                        <dd class="text-gray-900"><?php echo htmlspecialchars($child['phone']); ?></dd>
                                        
                                        <dt class="text-gray-600">Adresse</dt>
                                        <dd class="text-gray-900"><?php echo htmlspecialchars($child['address']); ?></dd>
                                    </dl>
                                </div>
                                
                                <!-- Actions rapides -->
                                <div class="flex space-x-2 pt-4">
                                    <a href="childcourse.php?id=<?php echo $child['id']; ?>" 
                                       class="flex-1 bg-blue-50 text-blue-700 hover:bg-blue-100 text-center px-4 py-2 rounded-lg text-sm font-medium transition duration-150">
                                        <i class="fas fa-book mr-2"></i>Cours
                                    </a>
                                    <a href="childattendance.php?id=<?php echo $child['id']; ?>" 
                                       class="flex-1 bg-green-50 text-green-700 hover:bg-green-100 text-center px-4 py-2 rounded-lg text-sm font-medium transition duration-150">
                                        <i class="fas fa-calendar-check mr-2"></i>Présences
                                    </a>
                                    <a href="childreport.php?id=<?php echo $child['id']; ?>" 
                                       class="flex-1 bg-purple-50 text-purple-700 hover:bg-purple-100 text-center px-4 py-2 rounded-lg text-sm font-medium transition duration-150">
                                        <i class="fas fa-file-alt mr-2"></i>Bulletin
                                    </a>
                                    <a href="childpayment.php?id=<?php echo $child['id']; ?>" 
                                       class="flex-1 bg-yellow-50 text-yellow-700 hover:bg-yellow-100 text-center px-4 py-2 rounded-lg text-sm font-medium transition duration-150">
                                        <i class="fas fa-money-bill mr-2"></i>Paiements
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Animation des cartes au survol
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.bg-white.rounded-lg.shadow-lg');
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.classList.add('transform', 'scale-105', 'transition-transform', 'duration-200');
            });
            card.addEventListener('mouseleave', () => {
                card.classList.remove('transform', 'scale-105', 'transition-transform', 'duration-200');
            });
        });
    });
    </script>
</body>
</html>
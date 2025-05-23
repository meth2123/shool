<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification de la session pour admin ou professeur
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Vérifier si l'utilisateur est un admin ou un professeur
$user_id = $_SESSION['login_id'];
$user_type = '';

// Vérifier si c'est un admin
$admin = db_fetch_row(
    "SELECT 1 FROM admin WHERE id = ?",
    [$user_id],
    's'
);

if ($admin) {
    $user_type = 'admin';
} else {
    // Vérifier si c'est un professeur
    $teacher = db_fetch_row(
        "SELECT 1 FROM teachers WHERE id = ?",
        [$user_id],
        's'
    );
    
    if ($teacher) {
        $user_type = 'teacher';
    } else {
        // Si ni admin ni professeur, rediriger vers la page de login
        header("Location: ../../index.php");
        exit();
    }
}

// Si c'est un professeur, utiliser son ID, sinon (admin) utiliser l'ID du professeur sélectionné
$teacher_id = ($user_type === 'teacher') ? $user_id : ($_GET['teacher_id'] ?? '');

// Si c'est un admin et qu'aucun professeur n'est sélectionné, afficher la liste des professeurs
if ($user_type === 'admin' && empty($teacher_id)) {
    $teachers = db_fetch_all(
        "SELECT id, name FROM teachers ORDER BY name",
        [],
        ''
    );
}

// Traitement de la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_class'])) {
    $class_id = $_POST['class_id'] ?? '';
    $course_id = $_POST['course_id'] ?? '';
    
    if ($class_id && $course_id && $teacher_id) {
        try {
            // Vérifier si l'assignation existe déjà
            $existing = db_fetch_row(
                "SELECT 1 FROM course 
                 WHERE classid = ? AND id = ? AND teacherid = ?",
                [$class_id, $course_id, $teacher_id],
                'sss'
            );
            
            if (!$existing) {
                // Mettre à jour le cours avec le professeur
                db_query(
                    "UPDATE course SET teacherid = ? WHERE id = ? AND classid = ?",
                    [$teacher_id, $course_id, $class_id],
                    'sss'
                );
                $success_message = "Classe assignée avec succès.";
            } else {
                $error_message = "Cette classe est déjà assignée à ce cours.";
            }
        } catch (Exception $e) {
            $error_message = "Erreur lors de l'assignation : " . $e->getMessage();
        }
    }
}

// Récupérer toutes les classes disponibles
$classes = db_fetch_all(
    "SELECT c.* 
     FROM class c 
     ORDER BY c.name",
    [],
    ''
);

// Récupérer les cours disponibles pour la classe sélectionnée
$selected_class = $_GET['class_id'] ?? '';
$courses = [];

if ($selected_class && $teacher_id) {
    $courses = db_fetch_all(
        "SELECT c.*, t.name as teacher_name 
         FROM course c 
         LEFT JOIN teachers t ON c.teacherid = t.id 
         WHERE c.classid = ? 
         ORDER BY c.name",
        [$selected_class],
        's'
    );
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigner une classe - Système de Gestion Scolaire</title>
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
                    <h1 class="ml-4 text-xl font-semibold text-gray-800">Assigner une classe</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if ($user_type === 'admin'): ?>
                        <a href="../admin/index.php" class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-home mr-2"></i>Retour au tableau de bord
                        </a>
                    <?php else: ?>
                        <a href="index.php" class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-home mr-2"></i>Accueil
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo $user_type === 'admin' ? '../admin/logout.php' : 'logout.php'; ?>" 
                       class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Messages de succès/erreur -->
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($user_type === 'admin' && empty($teacher_id)): ?>
            <!-- Sélection du professeur pour les admins -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Sélectionner un professeur</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($teachers as $teacher): ?>
                        <a href="?teacher_id=<?php echo htmlspecialchars($teacher['id']); ?>" 
                           class="block p-4 border rounded-lg hover:bg-gray-50 transition duration-150 ease-in-out">
                            <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($teacher['name']); ?></h3>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Sélection de la classe -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    <?php if ($user_type === 'admin'): ?>
                        Assigner une classe à <?php 
                            $teacher_info = db_fetch_row("SELECT name FROM teachers WHERE id = ?", [$teacher_id], 's');
                            echo htmlspecialchars($teacher_info['name'] ?? '');
                        ?>
                    <?php else: ?>
                        Sélectionner une classe
                    <?php endif; ?>
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($classes as $class): ?>
                        <a href="?class_id=<?php echo htmlspecialchars($class['id']); ?><?php echo $user_type === 'admin' ? '&teacher_id=' . $teacher_id : ''; ?>" 
                           class="block p-4 border rounded-lg hover:bg-gray-50 transition duration-150 ease-in-out <?php echo $selected_class === $class['id'] ? 'border-blue-500 bg-blue-50' : 'border-gray-200'; ?>">
                            <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($class['name']); ?></h3>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($class['description'] ?? ''); ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Liste des cours et formulaire d'assignation -->
            <?php if ($selected_class && !empty($courses)): ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Cours disponibles</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cours</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Professeur actuel</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($courses as $course): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($course['name']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $course['teacher_name'] ? htmlspecialchars($course['teacher_name']) : 'Non assigné'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($selected_class); ?>">
                                                    <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['id']); ?>">
                                                    <button type="submit" name="assign_class" 
                                                            class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                                            onclick="return confirm('Êtes-vous sûr de vouloir assigner ce cours ?');">
                                                        <i class="fas fa-user-plus mr-2"></i>
                                                        <?php echo $user_type === 'admin' ? 'Assigner' : 'S\'assigner'; ?>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php elseif ($selected_class): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Aucun cours trouvé pour cette classe.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
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
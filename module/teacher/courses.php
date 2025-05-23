<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification de la session (utilise la même méthode que main.php)
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$teacher_id = $_SESSION['login_id'];

// Récupérer les classes assignées à ce professeur
$classes = db_fetch_all(
    "SELECT DISTINCT c.* 
     FROM class c 
     JOIN course co ON c.id = co.classid 
     WHERE co.teacherid = ?
     ORDER BY c.name",
    [$teacher_id],
    's'
);

// Récupérer les cours si une classe est sélectionnée
$selected_class = $_GET['class_id'] ?? '';
$courses = [];

if ($selected_class) {
    $courses = db_fetch_all(
        "SELECT DISTINCT c.*, 
                (SELECT COUNT(DISTINCT stc.student_id) FROM student_teacher_course stc WHERE stc.course_id = c.id) as student_count,
                (SELECT COUNT(DISTINCT stc.student_id) FROM student_teacher_course stc WHERE stc.course_id = c.id AND stc.grade IS NOT NULL) as graded_count
         FROM course c 
         LEFT JOIN student_teacher_course stc ON c.id = stc.course_id
         WHERE c.classid = ? 
         AND (
             c.teacherid = ? 
             OR EXISTS (
                 SELECT 1 FROM student_teacher_course stc2 
                 WHERE stc2.course_id = c.id 
                 AND stc2.teacher_id = ?
                 AND stc2.class_id = ?
             )
         )
         ORDER BY c.name",
        [$selected_class, $teacher_id, $teacher_id, $selected_class],
        'ssss'
    );
}

// Debug: Afficher les informations de débogage
echo '<div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
    <p>ID du professeur : ' . htmlspecialchars($teacher_id) . '</p>
    <p>Classe sélectionnée : ' . htmlspecialchars($selected_class) . '</p>
    <p>Nombre de classes trouvées : ' . count($classes) . '</p>
    <p>Nombre de cours trouvés : ' . count($courses) . '</p>
</div>';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des notes - Système de Gestion Scolaire</title>
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
                    <h1 class="ml-4 text-xl font-semibold text-gray-800">Gestion des notes</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-home mr-2"></i>Accueil
                    </a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Sélection de la classe -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Sélectionner une classe</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($classes as $class): ?>
                    <a href="?class_id=<?php echo htmlspecialchars($class['id']); ?>" 
                       class="block p-4 border rounded-lg hover:bg-gray-50 transition duration-150 ease-in-out <?php echo $selected_class === $class['id'] ? 'border-blue-500 bg-blue-50' : 'border-gray-200'; ?>">
                        <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($class['name']); ?></h3>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($class['description'] ?? ''); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Liste des cours -->
        <?php if ($selected_class && !empty($courses)): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Cours de la classe</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($courses as $course): ?>
                            <div class="border rounded-lg p-4 hover:shadow-md transition duration-150 ease-in-out">
                                <h3 class="font-medium text-gray-900 mb-2"><?php echo htmlspecialchars($course['name']); ?></h3>
                                <div class="text-sm text-gray-500 mb-4">
                                    <p>Élèves : <?php echo $course['student_count']; ?></p>
                                    <p>Notes saisies : <?php echo $course['graded_count']; ?></p>
                                </div>
                                <a href="course.php?course_id=<?php echo htmlspecialchars($course['id']); ?>" 
                                   class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-150 ease-in-out">
                                    <i class="fas fa-graduation-cap mr-2"></i>
                                    Gérer les notes
                                </a>
                            </div>
                        <?php endforeach; ?>
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
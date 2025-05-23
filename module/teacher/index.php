<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Récupération des informations de l'enseignant
$teacher_info = db_fetch_row(
    "SELECT * FROM teachers WHERE id = ?",
    [$check],
    'i'
);

if (!$teacher_info) {
    header("Location: ../../?error=teacher_not_found");
    exit();
}

// Récupération des cours de l'enseignant
$courses = db_fetch_all(
    "SELECT c.*, cl.name as class_name 
     FROM course c
     LEFT JOIN class cl ON c.classid = cl.id
     WHERE c.teacherid = ?
     ORDER BY cl.name, c.name",
    [$check],
    's'
);

if (!$courses) {
    $courses = [];
}

// Récupération des statistiques
$stats = [
    'students' => db_fetch_row(
        "SELECT COUNT(DISTINCT studentid) as count
         FROM course
         WHERE teacherid = ?",
        [$check],
        's'
    )['count'] ?? 0,
    
    'courses' => count($courses),
    
    'attendance_today' => db_fetch_row(
        "SELECT COUNT(DISTINCT a.attendedid) as count
         FROM attendance a
         INNER JOIN course c ON c.studentid = a.attendedid
         WHERE c.teacherid = ? 
         AND DATE(a.date) = CURRENT_DATE",
        [$check],
        's'
    )['count'] ?? 0
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Système de Gestion Scolaire</title>
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
                    <span class="text-gray-600">Bonjour, <?php echo htmlspecialchars($teacher_info['name']); ?></span>
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
            <a href="updateTeacher.php" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-user-edit mr-2"></i>Modifier profil
            </a>
            <a href="viewProfile.php" class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-user mr-2"></i>Voir profil
            </a>
            <a href="courses.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-graduation-cap mr-2"></i>Notes
            </a>
            <a href="courses.php" class="bg-indigo-500 hover:bg-indigo-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-book mr-2"></i>Cours
            </a>
            <a href="attendance.php" class="bg-pink-500 hover:bg-pink-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-calendar-check mr-2"></i>Présences
            </a>
            <a href="exam.php" class="bg-teal-500 hover:bg-teal-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-clock mr-2"></i>Examens
            </a>
            <a href="salary.php" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-money-bill-wave mr-2"></i>Salaire
            </a>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Élèves</p>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo $stats['students']; ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-500">
                        <i class="fas fa-book text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Cours</p>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo $stats['courses']; ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                        <i class="fas fa-calendar-check text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Présences aujourd'hui</p>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo $stats['attendance_today']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des cours -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Mes cours</h2>
                <?php if (empty($courses)): ?>
                    <p class="text-gray-500 text-center py-4">Aucun cours assigné</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cours</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Classe</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($courses as $course): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($course['name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($course['class_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="course.php?id=<?php echo $course['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                <i class="fas fa-graduation-cap"></i> Notes
                                            </a>
                                            <a href="attendance.php?id=<?php echo $course['id']; ?>" class="text-green-600 hover:text-green-900">
                                                <i class="fas fa-calendar-check"></i> Présences
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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

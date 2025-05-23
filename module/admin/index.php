<?php
include_once('main.php');
include_once('includes/dashboard_stats.php');

$admin_id = $_SESSION['login_id'];
$stats = getDashboardStats($link, $admin_id);

$content = <<<HTML
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <!-- Statistiques des étudiants -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-700">Étudiants</h3>
            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">Total</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{$stats['students']}</p>
        <a href="manageStudent.php" class="mt-4 inline-block text-blue-600 hover:text-blue-800">Gérer les étudiants →</a>
    </div>

    <!-- Statistiques des enseignants -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-700">Enseignants</h3>
            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">Total</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{$stats['teachers']}</p>
        <a href="manageTeacher.php" class="mt-4 inline-block text-blue-600 hover:text-blue-800">Gérer les enseignants →</a>
    </div>

    <!-- Statistiques des cours -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-700">Cours</h3>
            <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-medium">Total</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{$stats['courses']}</p>
        <a href="course.php" class="mt-4 inline-block text-blue-600 hover:text-blue-800">Gérer les cours →</a>
    </div>

    <!-- Statistiques des classes -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-700">Classes</h3>
            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">Total</span>
        </div>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-3xl font-bold text-gray-900">{$stats['classes']}</p>
                <p class="text-sm text-gray-500">Total des classes</p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-semibold text-blue-600">{$stats['my_classes']}</p>
                <p class="text-sm text-gray-500">Mes classes</p>
            </div>
        </div>
        <a href="manageClass.php" class="mt-4 inline-block text-blue-600 hover:text-blue-800">Gérer les classes →</a>
    </div>
</div>

<!-- Actions rapides -->
<div class="mt-8">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Actions rapides</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="addStudent.php" class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <h3 class="text-gray-900 font-medium">Nouvel étudiant</h3>
            <p class="text-gray-500 text-sm">Ajouter un étudiant</p>
        </a>
        <a href="addTeacher.php" class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <h3 class="text-gray-900 font-medium">Nouvel enseignant</h3>
            <p class="text-gray-500 text-sm">Ajouter un enseignant</p>
        </a>
        <a href="addClass.php" class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <h3 class="text-gray-900 font-medium">Nouvelle classe</h3>
            <p class="text-gray-500 text-sm">Ajouter une classe</p>
        </a>
        <a href="addCourse.php" class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <h3 class="text-gray-900 font-medium">Nouveau cours</h3>
            <p class="text-gray-500 text-sm">Ajouter un cours</p>
        </a>
    </div>
    
    <!-- Gestion des notes et bulletins -->
    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="assignStudents.php" class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow border-l-4 border-purple-500">
            <h3 class="text-gray-900 font-medium">Assigner des élèves</h3>
            <p class="text-gray-500 text-sm">Gérer les élèves par cours</p>
        </a>
        <a href="assignClassTeacher.php" class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow border-l-4 border-indigo-500">
            <h3 class="text-gray-900 font-medium">Assigner une classe</h3>
            <p class="text-gray-500 text-sm">Assigner une classe à un prof</p>
        </a>
        <a href="manageGrades.php" class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow border-l-4 border-green-500">
            <h3 class="text-gray-900 font-medium">Gestion des notes</h3>
            <p class="text-gray-500 text-sm">Valider les notes soumises</p>
        </a>
        <a href="manageBulletins.php" class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow border-l-4 border-yellow-500">
            <h3 class="text-gray-900 font-medium">Bulletins</h3>
            <p class="text-gray-500 text-sm">Gérer les bulletins scolaires</p>
        </a>
    </div>
</div>

<div class="mt-8">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Gestion des Classes</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Gestion des classes -->
        <a href="manageClass.php" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-chalkboard text-3xl text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Gestion des Classes</h3>
                    <p class="mt-1 text-sm text-gray-500">Créer et gérer les classes</p>
                </div>
            </div>
        </a>

        <!-- Gestion des coefficients -->
        <a href="manageCoefficients.php" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-calculator text-3xl text-green-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Gestion des Coefficients</h3>
                    <p class="mt-1 text-sm text-gray-500">Définir les coefficients des matières</p>
                </div>
            </div>
        </a>

        <!-- Gestion des notes -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Gestion des Notes
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">
                                    <a href="bulletins.php" class="text-blue-600 hover:text-blue-800">
                                        Voir les bulletins
                                    </a>
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

include('templates/layout.php');
?>

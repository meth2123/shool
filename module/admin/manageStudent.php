<?php
include_once('main.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];
$result = getDataByAdmin($link, 'students', $admin_id);
$student_count = countDataByAdmin($link, 'students', $admin_id);

$content = <<<HTML
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-wrap -mx-4">
        <!-- Carte Ajouter un étudiant -->
        <div class="w-full md:w-1/2 lg:w-1/3 px-4 mb-8">
            <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
                <div class="text-center">
                    <div class="h-12 w-12 bg-blue-100 text-blue-600 rounded-lg mx-auto mb-4 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Ajouter un étudiant</h3>
                    <p class="text-gray-600 mb-4">Créer un nouveau profil étudiant</p>
                    <a href="addStudent.php" class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Ajouter
                    </a>
                </div>
            </div>
        </div>

        <!-- Carte Voir les étudiants -->
        <div class="w-full md:w-1/2 lg:w-1/3 px-4 mb-8">
            <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
                <div class="text-center">
                    <div class="h-12 w-12 bg-green-100 text-green-600 rounded-lg mx-auto mb-4 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Voir les étudiants</h3>
                    <p class="text-gray-600 mb-4">Liste complète des étudiants</p>
                    <a href="viewStudent.php" class="inline-block bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        Voir
                    </a>
                </div>
            </div>
        </div>

        <!-- Carte Modifier un étudiant -->
        <div class="w-full md:w-1/2 lg:w-1/3 px-4 mb-8">
            <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
                <div class="text-center">
                    <div class="h-12 w-12 bg-yellow-100 text-yellow-600 rounded-lg mx-auto mb-4 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Modifier un étudiant</h3>
                    <p class="text-gray-600 mb-4">Mettre à jour les informations</p>
                    <a href="updateStudent.php" class="inline-block bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
                        Modifier
                    </a>
                </div>
            </div>
        </div>

        <!-- Carte Supprimer un étudiant -->
        <div class="w-full md:w-1/2 lg:w-1/3 px-4 mb-8">
            <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
                <div class="text-center">
                    <div class="h-12 w-12 bg-red-100 text-red-600 rounded-lg mx-auto mb-4 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Supprimer un étudiant</h3>
                    <p class="text-gray-600 mb-4">Retirer un étudiant du système</p>
                    <a href="deleteStudent.php" class="inline-block bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                        Supprimer
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="bg-white rounded-lg shadow-lg p-6 mt-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Statistiques des étudiants</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-blue-50 rounded-lg p-4">
                <h3 class="text-blue-600 font-medium">Total des étudiants</h3>
                <p class="text-2xl font-bold text-blue-800">{$student_count}</p>
            </div>
            <!-- Ajoutez d'autres statistiques si nécessaire -->
        </div>
    </div>
</div>
HTML;

include('templates/layout.php');
?>

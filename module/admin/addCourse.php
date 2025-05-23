<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];

// Récupérer les informations de l'admin
$admin_sql = "SELECT name FROM admin WHERE id = ?";
$admin_stmt = $link->prepare($admin_sql);
$admin_stmt->bind_param("s", $admin_id);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$admin_row = $admin_result->fetch_assoc();
$admin_name = $admin_row['name'] ?? 'Admin';

// Récupérer les classes
$class_sql = "SELECT id, name FROM class WHERE created_by = ? ORDER BY name";
$class_stmt = $link->prepare($class_sql);
$class_stmt->bind_param("s", $admin_id);
$class_stmt->execute();
$class_result = $class_stmt->get_result();

// Récupérer les enseignants
$teacher_sql = "SELECT id, name FROM teachers WHERE created_by = ? ORDER BY name";
$teacher_stmt = $link->prepare($teacher_sql);
$teacher_stmt->bind_param("s", $admin_id);
$teacher_stmt->execute();
$teacher_result = $teacher_stmt->get_result();

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Ajouter un Cours</h2>
            <p class="mt-1 text-sm text-gray-600">Créez un nouveau cours en remplissant les informations ci-dessous.</p>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <form action="includes/process_course.php" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="created_by" value="' . htmlspecialchars($admin_id) . '">
                
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nom du cours</label>
                    <input type="text" name="name" id="name" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="classid" class="block text-sm font-medium text-gray-700">Classe</label>
                    <select name="classid" id="classid" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">';
                    
                    while ($class = $class_result->fetch_assoc()) {
                        $content .= '<option value="' . htmlspecialchars($class['id']) . '">' . 
                            htmlspecialchars($class['name']) . '</option>';
                    }

$content .= '
                    </select>
                </div>

                <div>
                    <label for="teacherid" class="block text-sm font-medium text-gray-700">Enseignant</label>
                    <select name="teacherid" id="teacherid" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">';
                    
                    while ($teacher = $teacher_result->fetch_assoc()) {
                        $content .= '<option value="' . htmlspecialchars($teacher['id']) . '">' . 
                            htmlspecialchars($teacher['name']) . '</option>';
                    }

$content .= '
                    </select>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="course.php" 
                       class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Annuler
                    </a>
                    <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Ajouter le cours
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>';

include('templates/layout.php');
?>

<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];

// Gestion des messages de succès/erreur
$success_message = isset($_GET['success']) ? '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">' . htmlspecialchars($_GET['success']) . '</div>' : '';
$error_message = isset($_GET['error']) ? '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">' . htmlspecialchars($_GET['error']) . '</div>' : '';

// Récupération des cours créés par cet admin
$sql = "SELECT c.*, t.name as teacher_name, cl.name as class_name 
        FROM course c 
        LEFT JOIN teachers t ON c.teacherid = t.id 
        LEFT JOIN class cl ON c.classid = cl.id 
        WHERE c.created_by = ?
        ORDER BY c.name";

$stmt = $link->prepare($sql);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

$content = '
<div class="container mx-auto px-4 py-8">
    ' . $success_message . $error_message . '
    
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Gestion des Cours</h2>
        <a href="addCourse.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
            Ajouter un Cours
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom du cours</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enseignant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Classe</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $content .= '
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">' . 
                                htmlspecialchars($row['id']) . '</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . 
                                htmlspecialchars($row['name']) . '</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . 
                                htmlspecialchars($row['teacher_name'] ?? 'Non assigné') . '</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . 
                                htmlspecialchars($row['class_name'] ?? 'Non assignée') . '</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <a href="updateCourse.php?id=' . htmlspecialchars($row['id']) . '" 
                                   class="text-blue-600 hover:text-blue-900">Modifier</a>
                                <a href="viewCourse.php?id=' . htmlspecialchars($row['id']) . '" 
                                   class="text-green-600 hover:text-green-900">Voir</a>
                                <button onclick="confirmDelete(\'' . htmlspecialchars($row['id']) . '\')" 
                                        class="text-red-600 hover:text-red-900">Supprimer</button>
                            </td>
                        </tr>';
                    }
                } else {
                    $content .= '
                    <tr>
                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                            Aucun cours trouvé. <a href="addCourse.php" class="text-blue-600 hover:text-blue-900">Ajouter un cours</a>
                        </td>
                    </tr>';
                }

$content .= '
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmDelete(courseId) {
    if (confirm("Êtes-vous sûr de vouloir supprimer ce cours ?")) {
        window.location.href = "deleteCourse.php?id=" + courseId;
    }
}
</script>';

include('templates/layout.php');
?>

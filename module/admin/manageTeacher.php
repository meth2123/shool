<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];

// Gestion des messages de succès/erreur
$success_message = isset($_GET['success']) ? '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">' . htmlspecialchars($_GET['success']) . '</div>' : '';
$error_message = isset($_GET['error']) ? '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">' . htmlspecialchars($_GET['error']) . '</div>' : '';

// Par défaut, on filtre par l'admin connecté
$where_clause = ' WHERE t.created_by = ?';
$params = [$admin_id];
$param_types = 's';

// Récupération des enseignants
$sql = "SELECT t.*, u.userid as creator_id 
        FROM teachers t 
        LEFT JOIN users u ON t.created_by = u.userid" . $where_clause . 
        " ORDER BY t.name";

$stmt = $link->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$content = '
<div class="container mx-auto px-4 py-8">
    ' . $success_message . $error_message . '
    
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Gestion des Enseignants</h2>
        <a href="addTeacher.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
            Ajouter un Enseignant
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Genre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d\'embauche</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salaire</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">';

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $gender_badge = $row['sex'] == 'female' ? 
                        '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-pink-100 text-pink-800">F</span>' :
                        '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">M</span>';
                    
                    $content .= '
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">' . htmlspecialchars($row['id']) . '</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['name']) . '</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($row['email']) . '</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($row['phone']) . '</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . $gender_badge . '</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($row['hiredate'] ?? 'N/A') . '</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($row['salary'] ?? 'N/A') . '</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <a href="updateTeacher.php?id=' . htmlspecialchars($row['id']) . '" 
                               class="text-blue-600 hover:text-blue-900">Modifier</a>
                            <a href="viewTeacher.php?id=' . htmlspecialchars($row['id']) . '" 
                               class="text-green-600 hover:text-green-900">Voir</a>
                            <button onclick="confirmDelete(\'' . htmlspecialchars($row['id']) . '\')" 
                                    class="text-red-600 hover:text-red-900">Supprimer</button>
                        </td>
                    </tr>';
                }
            } else {
                $content .= '
                <tr>
                    <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        Aucun enseignant trouvé. <a href="addTeacher.php" class="text-blue-600 hover:text-blue-900">Ajouter un enseignant</a>
                    </td>
                </tr>';
            }

$content .= '
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmDelete(teacherId) {
    if (confirm("Êtes-vous sûr de vouloir supprimer cet enseignant ?")) {
        window.location.href = "deleteTeacher.php?id=" + teacherId;
    }
}
</script>';

include('templates/layout.php');
?>

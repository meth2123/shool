<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];

// Récupérer les classes de l'admin connecté
$sql = "SELECT *, 
        CASE 
            WHEN created_by = ? THEN 'Ma classe'
            ELSE 'Classe par défaut'
        END as class_type 
        FROM class 
        WHERE created_by = ? OR created_by = '21'
        ORDER BY name, section";
$stmt = $link->prepare($sql);
$stmt->bind_param("ss", $admin_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();

// Message de succès/erreur
$message = '';
if (isset($_GET['success'])) {
    $message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">' . htmlspecialchars($_GET['success']) . '</div>';
} elseif (isset($_GET['error'])) {
    $message = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">' . htmlspecialchars($_GET['error']) . '</div>';
}

$content = '
<div class="container mx-auto px-4 py-8">
    ' . $message . '
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Gestion des Classes</h2>
        <a href="addClass.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
            Ajouter une classe
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salle</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $isDefaultClass = $row['created_by'] === '21';
        $typeClass = $isDefaultClass ? 'bg-gray-100' : '';
        $content .= '
                <tr class="' . $typeClass . '">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . ($isDefaultClass ? 'bg-gray-100 text-gray-800' : 'bg-green-100 text-green-800') . '">
                            ' . htmlspecialchars($row['class_type']) . '
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['id']) . '</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['name']) . '</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['section']) . '</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['room']) . '</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="updateClass.php?id=' . htmlspecialchars($row['id']) . '" class="text-blue-600 hover:text-blue-900 mr-3">Modifier</a>
                        <a href="deleteClass.php?id=' . htmlspecialchars($row['id']) . '" class="text-red-600 hover:text-red-900" 
                           onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer cette classe ?\');">Supprimer</a>
                    </td>
                </tr>';
    }
} else {
    $content .= '
                <tr>
                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        Aucune classe trouvée. <a href="addClass.php" class="text-blue-600 hover:text-blue-900">Créer une nouvelle classe</a>
                    </td>
                </tr>';
}

$content .= '
            </tbody>
        </table>
    </div>
</div>';

include('templates/layout.php');
?> 
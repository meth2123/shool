<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];

// Get staff list created by this admin
$sql = "SELECT s.*, u.userid 
        FROM staff s 
        LEFT JOIN users u ON s.id = u.userid 
        WHERE s.created_by = ? 
        ORDER BY s.name";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Gestion du Personnel</h2>
        <a href="addStaff.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
            Ajouter un membre du personnel
        </a>
    </div>

    ' . (isset($_GET['success']) ? '<div class="mb-4 p-4 text-green-700 bg-green-100 rounded-md">' . htmlspecialchars($_GET['success']) . '</div>' : '') . '
    ' . (isset($_GET['error']) ? '<div class="mb-4 p-4 text-red-700 bg-red-100 rounded-md">' . htmlspecialchars($_GET['error']) . '</div>' : '') . '

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adresse</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $content .= '
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['id']) . '</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['name']) . '</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['email']) . '</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['phone']) . '</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['address']) . '</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                <a href="viewStaff.php?id=' . htmlspecialchars($row['id']) . '" 
                   class="inline-block bg-blue-100 text-blue-700 px-3 py-1 rounded-md hover:bg-blue-200">
                    Voir
                </a>
                <a href="updateStaff.php?id=' . htmlspecialchars($row['id']) . '" 
                   class="inline-block bg-green-100 text-green-700 px-3 py-1 rounded-md hover:bg-green-200">
                    Modifier
                </a>
                <button onclick="confirmDelete(\'' . htmlspecialchars($row['id']) . '\')" 
                        class="inline-block bg-red-100 text-red-700 px-3 py-1 rounded-md hover:bg-red-200">
                    Supprimer
                </button>
            </td>
        </tr>';
    }
} else {
    $content .= '
        <tr>
            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                Aucun membre du personnel trouvé
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
function confirmDelete(staffId) {
    if (confirm("Êtes-vous sûr de vouloir supprimer ce membre du personnel ?")) {
        window.location.href = "deleteStaff.php?id=" + staffId;
    }
}
</script>';

include('templates/layout.php');
?>

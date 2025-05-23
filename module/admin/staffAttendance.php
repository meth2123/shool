<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

$admin_id = $_SESSION['login_id'];

// Get staff members who haven't been marked for attendance today
$sql = "SELECT * FROM staff 
        WHERE created_by = ? 
        AND id NOT IN (SELECT attendedid FROM attendance WHERE date = CURDATE())";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-900">Présences du Personnel</h2>
            <div class="text-sm text-gray-600">
                Date: ' . date('d/m/Y') . '
            </div>
        </div>

        ' . (isset($_GET['success']) ? '<div class="mb-4 p-4 text-green-700 bg-green-100 rounded-md">' . htmlspecialchars($_GET['success']) . '</div>' : '') . '
        ' . (isset($_GET['error']) ? '<div class="mb-4 p-4 text-red-700 bg-red-100 rounded-md">' . htmlspecialchars($_GET['error']) . '</div>' : '') . '

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $content .= '
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap space-x-2">
                <form action="attendStaff.php" method="post" class="inline-block">
                    <input type="hidden" value="' . htmlspecialchars($row['id']) . '" name="id">
                    <button type="submit" name="submit" value="present"
                            class="bg-green-100 text-green-700 px-3 py-1 rounded-md hover:bg-green-200">
                        Présent
                    </button>
                </form>
                <form action="attendStaff.php" method="post" class="inline-block">
                    <input type="hidden" value="' . htmlspecialchars($row['id']) . '" name="id">
                    <button type="submit" name="submit" value="absent"
                            class="bg-red-100 text-red-700 px-3 py-1 rounded-md hover:bg-red-200">
                        Absent
                    </button>
                </form>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['id']) . '</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['name']) . '</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['phone']) . '</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['email']) . '</td>
        </tr>';
    }
} else {
    $content .= '
        <tr>
            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                Tous les membres du personnel ont été marqués pour aujourd\'hui
            </td>
        </tr>';
}

$content .= '
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end space-x-4">
            <a href="viewAttendance.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                Voir l\'historique des présences
            </a>
        </div>
    </div>
</div>';

include('templates/layout.php');
?>

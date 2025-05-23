<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];
$staff_id = $_GET['id'] ?? '';

if (empty($staff_id)) {
    header("Location: manageStaff.php?error=" . urlencode("ID du personnel non spécifié"));
    exit;
}

// Get staff details
$sql = "SELECT s.*, u.userid, u.password 
        FROM staff s 
        LEFT JOIN users u ON s.id = u.userid 
        WHERE s.id = ? AND s.created_by = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("ss", $staff_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();

if (!$staff) {
    header("Location: manageStaff.php?error=" . urlencode("Personnel non trouvé ou accès non autorisé"));
    exit;
}

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-900">Détails du Personnel</h2>
            <a href="manageStaff.php" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
            </a>
        </div>

        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm font-medium text-gray-500">ID</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($staff['id']) . '</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Nom</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($staff['name']) . '</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Email</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($staff['email']) . '</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Téléphone</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($staff['phone']) . '</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Adresse</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($staff['address']) . '</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Genre</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($staff['sex']) . '</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Date de naissance</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($staff['dob']) . '</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Date d\'embauche</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($staff['hiredate']) . '</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Salaire</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($staff['salary']) . '</p>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                <a href="updateStaff.php?id=' . htmlspecialchars($staff_id) . '" 
                   class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="fas fa-edit mr-2"></i>Modifier
                </a>
                <button onclick="confirmDelete(\'' . htmlspecialchars($staff_id) . '\')"
                        class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    <i class="fas fa-trash mr-2"></i>Supprimer
                </button>
            </div>
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

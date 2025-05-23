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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $sex = $_POST['sex'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $salary = $_POST['salary'] ?? '';
    $password = $_POST['password'] ?? '';

    // Verify that the staff member belongs to this admin
    $check_sql = "SELECT id FROM staff WHERE id = ? AND created_by = ?";
    $check_stmt = $link->prepare($check_sql);
    $check_stmt->bind_param("ss", $staff_id, $admin_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        header("Location: manageStaff.php?error=" . urlencode("Personnel non trouvé ou accès non autorisé"));
        exit;
    }

    // Start transaction
    $link->begin_transaction();

    try {
        // Update staff information
        $update_sql = "UPDATE staff SET 
            name = ?, 
            email = ?, 
            phone = ?, 
            address = ?, 
            sex = ?, 
            dob = ?, 
            salary = ? 
            WHERE id = ? AND created_by = ?";
        
        $update_stmt = $link->prepare($update_sql);
        $update_stmt->bind_param("ssssssdss", 
            $name, $email, $phone, $address, $sex, $dob, $salary, $staff_id, $admin_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Erreur lors de la mise à jour des informations");
        }

        // Update password if provided
        if (!empty($password)) {
            $update_pass_sql = "UPDATE users SET password = ? WHERE userid = ?";
            $update_pass_stmt = $link->prepare($update_pass_sql);
            $update_pass_stmt->bind_param("ss", $password, $staff_id);
            
            if (!$update_pass_stmt->execute()) {
                throw new Exception("Erreur lors de la mise à jour du mot de passe");
            }
        }

        $link->commit();
        header("Location: viewStaff.php?id=" . urlencode($staff_id) . "&success=1");
        exit;
    } catch (Exception $e) {
        $link->rollback();
        $error = $e->getMessage();
    }
}

// Get staff details
$sql = "SELECT s.*, u.password 
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
            <h2 class="text-2xl font-bold text-gray-900">Modifier le Personnel</h2>
            <a href="viewStaff.php?id=' . htmlspecialchars($staff_id) . '" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-2"></i>Retour aux détails
            </a>
        </div>

        ' . (isset($error) ? '<div class="mb-4 p-4 text-red-700 bg-red-100 rounded-md">' . htmlspecialchars($error) . '</div>' : '') . '

        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <form method="POST" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nom</label>
                        <input type="text" name="name" id="name" required
                               value="' . htmlspecialchars($staff['name']) . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="email" required
                               value="' . htmlspecialchars($staff['email']) . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Téléphone</label>
                        <input type="tel" name="phone" id="phone" required
                               value="' . htmlspecialchars($staff['phone']) . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700">Adresse</label>
                        <input type="text" name="address" id="address" required
                               value="' . htmlspecialchars($staff['address']) . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="sex" class="block text-sm font-medium text-gray-700">Genre</label>
                        <select name="sex" id="sex" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="M" ' . ($staff['sex'] === 'M' ? 'selected' : '') . '>Masculin</option>
                            <option value="F" ' . ($staff['sex'] === 'F' ? 'selected' : '') . '>Féminin</option>
                        </select>
                    </div>

                    <div>
                        <label for="dob" class="block text-sm font-medium text-gray-700">Date de naissance</label>
                        <input type="date" name="dob" id="dob" required
                               value="' . htmlspecialchars($staff['dob']) . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="salary" class="block text-sm font-medium text-gray-700">Salaire</label>
                        <input type="number" name="salary" id="salary" required step="0.01"
                               value="' . htmlspecialchars($staff['salary']) . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Nouveau mot de passe (laisser vide pour ne pas changer)
                        </label>
                        <input type="password" name="password" id="password"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
				</div>

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Enregistrer les modifications
                    </button>
						</div>
            </form>
        </div>
    </div>
</div>';

include('templates/layout.php');
?>

<?php
include_once('main.php');
include_once('includes/auth_check.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

// L'ID de l'administrateur est déjà défini dans auth_check.php
// $admin_id = $_SESSION['login_id'];

// Function to generate a unique staff ID
function generateUniqueStaffId($link) {
    // Prefix for all staff IDs
    $prefix = "STF";
    
    // Get the last numeric part
    $last_id_sql = "SELECT id FROM staff WHERE id LIKE 'STF%' ORDER BY id DESC LIMIT 1";
    $result = $link->query($last_id_sql);
    
    if ($result && $result->num_rows > 0) {
        $last_id = $result->fetch_assoc()['id'];
        // Extract the numeric part (positions 3-6)
        $numeric_part = intval(substr($last_id, 3, 3));
        $numeric_part++;
    } else {
        $numeric_part = 1;
    }
    
    // Generate random letters (3 uppercase letters)
    $letters = '';
    for ($i = 0; $i < 3; $i++) {
        $letters .= chr(rand(65, 90)); // ASCII codes for A-Z
    }
    
    // Format the numeric part to be 3 digits with leading zeros
    $formatted_number = str_pad($numeric_part, 3, '0', STR_PAD_LEFT);
    
    // Combine all parts
    $new_id = $prefix . $formatted_number . $letters;
    
    // Verify uniqueness and try again if necessary
    $check_sql = "SELECT id FROM staff WHERE id = ?";
    $check_stmt = $link->prepare($check_sql);
    $check_stmt->bind_param("s", $new_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        // If ID exists (very unlikely), try again
        return generateUniqueStaffId($link);
    }
    
    return $new_id;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $sex = $_POST['sex'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $hiredate = date('Y-m-d'); // Current date as hire date
    $salary = $_POST['salary'] ?? '';
    $password = $_POST['password'] ?? '';

    // Start transaction
    $link->begin_transaction();

    try {
        // Generate unique staff ID
        $staff_id = generateUniqueStaffId($link);

        // Insert into staff table
        $insert_sql = "INSERT INTO staff (id, name, email, phone, address, sex, dob, hiredate, salary, created_by) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $link->prepare($insert_sql);
        $insert_stmt->bind_param("ssssssssds", 
            $staff_id, $name, $email, $phone, $address, $sex, $dob, $hiredate, $salary, $admin_id);
        
        if (!$insert_stmt->execute()) {
            throw new Exception("Erreur lors de l'ajout du membre du personnel");
        }

        // Insert into users table
        $insert_user_sql = "INSERT INTO users (userid, password, usertype) VALUES (?, ?, 'staff')";
        $insert_user_stmt = $link->prepare($insert_user_sql);
        $insert_user_stmt->bind_param("ss", $staff_id, $password);
        
        if (!$insert_user_stmt->execute()) {
            throw new Exception("Erreur lors de la création du compte utilisateur");
        }

        $link->commit();
        header("Location: manageStaff.php?success=" . urlencode("Membre du personnel ajouté avec succès. ID de connexion: " . $staff_id));
        exit;
    } catch (Exception $e) {
        $link->rollback();
        $error = $e->getMessage();
    }
}

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-900">Ajouter un Membre du Personnel</h2>
            <a href="manageStaff.php" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
            </a>
        </div>

        ' . (isset($error) ? '<div class="mb-4 p-4 text-red-700 bg-red-100 rounded-md">' . htmlspecialchars($error) . '</div>' : '') . '
        
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="p-4 bg-blue-50 border-b border-blue-100">
                <p class="text-sm text-blue-600">
                    <strong>Note:</strong> Un ID unique sera généré automatiquement au format STF001XXX, où XXX sont des lettres aléatoires.
                    Cet ID servira d\'identifiant de connexion.
                </p>
            </div>

            <form method="POST" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nom</label>
                        <input type="text" name="name" id="name" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="email" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Téléphone</label>
                        <input type="tel" name="phone" id="phone" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700">Adresse</label>
                        <input type="text" name="address" id="address" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="sex" class="block text-sm font-medium text-gray-700">Genre</label>
                        <select name="sex" id="sex" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Sélectionner</option>
                            <option value="M">Masculin</option>
                            <option value="F">Féminin</option>
                        </select>
                    </div>

                    <div>
                        <label for="dob" class="block text-sm font-medium text-gray-700">Date de naissance</label>
                        <input type="date" name="dob" id="dob" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="salary" class="block text-sm font-medium text-gray-700">Salaire</label>
                        <input type="number" name="salary" id="salary" required step="0.01"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Mot de passe
                        </label>
                        <input type="password" name="password" id="password" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
				</div>

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Ajouter le membre du personnel
                    </button>
						</div>
            </form>
        </div>
    </div>
</div>';

include('templates/layout.php');
?>

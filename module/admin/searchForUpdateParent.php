<?php
include_once('main.php');
require_once('../../db/config.php');

// Get admin ID for filtering
$admin_id = $_SESSION['login_id'];

// Initialize database connection
$conn = getDbConnection();

$searchKey = $_GET['key'];

// Prepare and execute query
$sql = "SELECT * FROM parents WHERE created_by = ? AND (id LIKE ? OR fathername LIKE ? OR mothername LIKE ?)";
$stmt = $conn->prepare($sql);
$searchParam = "%$searchKey%";
$stmt->bind_param("ssss", $admin_id, $searchParam, $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<p class="text-gray-500 italic">Aucun parent trouvé.</p>';
} else {
    // Start the form content
    echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
    
    while($row = $result->fetch_assoc()) {
        echo '
        <input type="hidden" name="id" value="'.htmlspecialchars($row['id']).'">
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">ID Parent</label>
            <input type="text" value="'.htmlspecialchars($row['id']).'" 
                   class="w-full px-4 py-2 border rounded-lg bg-gray-100" readonly>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe</label>
            <input type="password" name="password" value="'.htmlspecialchars($row['password']).'"
                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nom du père</label>
            <input type="text" name="fathername" value="'.htmlspecialchars($row['fathername']).'"
                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nom de la mère</label>
            <input type="text" name="mothername" value="'.htmlspecialchars($row['mothername']).'"
                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone du père</label>
            <input type="tel" name="fatherphone" value="'.htmlspecialchars($row['fatherphone']).'"
                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone de la mère</label>
            <input type="tel" name="motherphone" value="'.htmlspecialchars($row['motherphone']).'"
                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Adresse</label>
            <textarea name="address" rows="3" 
                      class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>'.htmlspecialchars($row['address']).'</textarea>
        </div>

        <div class="md:col-span-2">
            <button type="submit" name="submit" value="Submit"
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition duration-200">
                <i class="fas fa-save mr-2"></i>Enregistrer les modifications
            </button>
        </div>';
    }
    
    echo '</div>';
}

// Close database connection
$stmt->close();
$conn->close();
?>

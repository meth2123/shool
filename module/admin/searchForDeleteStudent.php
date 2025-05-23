<?php
include_once('main.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];
$searchKey = trim($_GET['key']);

try {
    if (empty($searchKey)) {
        throw new Exception("Veuillez entrer un terme de recherche");
    }

    // Recherche avec created_by et une recherche plus flexible
    $sql = "SELECT * FROM students WHERE created_by = ? AND (id LIKE ? OR name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $stmt = $link->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Erreur de préparation : " . $link->error);
    }

    $searchPattern = '%' . $searchKey . '%';  // Recherche plus flexible
    $stmt->bind_param("sssss", $admin_id, $searchPattern, $searchPattern, $searchPattern, $searchPattern);
    
    if (!$stmt->execute()) {
        throw new Exception("Erreur d'exécution : " . $stmt->error);
    }

    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo '<tr><td colspan="12" class="px-6 py-4 text-center text-gray-500">
            Aucun étudiant trouvé pour votre recherche "'.htmlspecialchars($searchKey).'"
        </td></tr>';
        exit;
    }
    
    while($row = $result->fetch_assoc()) {
        $picname = $row['id'];
        echo '<tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
                <input type="checkbox" name="deleteStudent[]" value="'.htmlspecialchars($row['id']).'">
            </td>
            <td class="px-6 py-4 whitespace-nowrap">'.htmlspecialchars($row['id']).'</td>
            <td class="px-6 py-4 whitespace-nowrap">'.htmlspecialchars($row['name']).'</td>
            <td class="px-6 py-4 whitespace-nowrap">'.htmlspecialchars($row['phone']).'</td>
            <td class="px-6 py-4 whitespace-nowrap">'.htmlspecialchars($row['email']).'</td>
            <td class="px-6 py-4 whitespace-nowrap">'.htmlspecialchars($row['sex']).'</td>
            <td class="px-6 py-4 whitespace-nowrap">'.htmlspecialchars($row['dob']).'</td>
            <td class="px-6 py-4 whitespace-nowrap">'.htmlspecialchars($row['addmissiondate']).'</td>
            <td class="px-6 py-4 whitespace-nowrap">'.htmlspecialchars($row['address']).'</td>
            <td class="px-6 py-4 whitespace-nowrap">'.htmlspecialchars($row['parentid']).'</td>
            <td class="px-6 py-4 whitespace-nowrap">'.htmlspecialchars($row['classid']).'</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <img src="../images/'.$picname.'.jpg" alt="'.$picname.'" class="h-20 w-20 object-cover rounded-full">
            </td>
        </tr>';
    }
} catch (Exception $e) {
    error_log("Erreur dans searchForDeleteStudent.php : " . $e->getMessage());
    echo '<tr><td colspan="12" class="px-6 py-4 text-center text-red-500">
        Une erreur est survenue : '.htmlspecialchars($e->getMessage()).'
    </td></tr>';
}
?> 
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
        echo '<tr><td colspan="13" class="px-6 py-4 text-center text-gray-500">
            Aucun étudiant trouvé pour votre recherche "'.htmlspecialchars($searchKey).'"
        </td></tr>';
        exit;
    }
    
    while($row = $result->fetch_assoc()) {
        $picname = $row['id'];
        echo '<tr class="hover:bg-gray-50">
            <form id="form_'.$row['id'].'" action="updateStudentHandler.php" method="post" class="w-full" enctype="multipart/form-data">
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="text" name="id" value="'.htmlspecialchars($row['id']).'" readonly 
                           class="bg-gray-100 rounded px-2 py-1 w-full">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="text" name="name" value="'.htmlspecialchars($row['name']).'" required 
                           class="border rounded px-2 py-1 w-full">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="password" name="password" placeholder="Nouveau mot de passe" 
                           class="border rounded px-2 py-1 w-full">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="tel" name="phone" value="'.htmlspecialchars($row['phone']).'" required 
                           class="border rounded px-2 py-1 w-full">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="email" name="email" value="'.htmlspecialchars($row['email']).'" required 
                           class="border rounded px-2 py-1 w-full">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <select name="gender" class="border rounded px-2 py-1 w-full">
                        <option value="Male" '.($row['sex'] == 'Male' ? 'selected' : '').'>Masculin</option>
                        <option value="Female" '.($row['sex'] == 'Female' ? 'selected' : '').'>Féminin</option>
                    </select>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="date" name="dob" value="'.htmlspecialchars($row['dob']).'" required 
                           class="border rounded px-2 py-1 w-full">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="date" name="addmissiondate" value="'.htmlspecialchars($row['addmissiondate']).'" required 
                           class="border rounded px-2 py-1 w-full">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="text" name="address" value="'.htmlspecialchars($row['address']).'" required 
                           class="border rounded px-2 py-1 w-full">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="text" name="parentid" value="'.htmlspecialchars($row['parentid']).'" required 
                           class="border rounded px-2 py-1 w-full">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="text" name="classid" value="'.htmlspecialchars($row['classid']).'" required 
                           class="border rounded px-2 py-1 w-full">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <img src="../images/'.$picname.'.jpg" alt="'.$picname.'" class="h-20 w-20 object-cover rounded-full">
                    <input type="file" name="photo" accept="image/*" class="mt-2">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="hidden" name="created_by" value="'.htmlspecialchars($admin_id).'">
                    <button type="button" onclick="submitForm(\'form_'.$row['id'].'\')" 
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Mettre à jour
                    </button>
                </td>
            </form>
        </tr>';
    }
} catch (Exception $e) {
    error_log("Erreur dans searchForUpdateStudent.php : " . $e->getMessage());
    echo '<tr><td colspan="13" class="px-6 py-4 text-center text-red-500">
        Une erreur est survenue : '.htmlspecialchars($e->getMessage()).'
    </td></tr>';
}
?>

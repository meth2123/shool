<?php
include_once('main.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];

// Récupérer uniquement les étudiants créés par l'admin connecté
$sql = "SELECT * FROM students WHERE created_by = ?";
$stmt = $link->prepare($sql);

// Débogage
error_log("Admin ID: " . $admin_id);
error_log("Requête SQL: " . $sql);

if (!$stmt) {
    error_log("Erreur de préparation: " . $link->error);
    die("Erreur de préparation: " . $link->error);
}

$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    error_log("Erreur d'exécution: " . $stmt->error);
    die("Erreur d'exécution: " . $stmt->error);
}

error_log("Nombre de résultats: " . $result->num_rows);

$string = "";
$images_dir = "../images/";

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold mb-6">Liste des étudiants</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Genre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de naissance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d\'admission</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adresse</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Parent</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Classe</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';

while($row = $result->fetch_assoc()) {
    error_log("Données étudiant: " . print_r($row, true));
    $picname = $row['id'];
    $content .= '
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">'.$row['id'].'</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">'.$row['name'].'</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">'.$row['phone'].'</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">'.$row['email'].'</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">'.$row['sex'].'</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">'.$row['dob'].'</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">'.$row['addmissiondate'].'</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">'.$row['address'].'</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">'.$row['parentid'].'</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">'.$row['classid'].'</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <img src="'.$images_dir.$picname.'.jpg" alt="'.$picname.'" class="h-20 w-20 object-cover rounded-full">
            </td>
        </tr>';
}

$content .= '
                </tbody>
            </table>
        </div>
    </div>
</div>';

include('templates/layout.php');
?>

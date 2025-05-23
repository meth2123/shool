<?php
include_once('main.php');
include_once('includes/admin_utils.php');

$searchKey = $_GET['key'];
$admin_id = $_SESSION['login_id'];

// Construire la condition de recherche
$additional_conditions = "AND (id LIKE ? OR name LIKE ? OR classid LIKE ?)";
$search_param = "%$searchKey%";

// Préparer la requête
$sql = "SELECT * FROM students WHERE created_by = ? " . $additional_conditions;
$stmt = $link->prepare($sql);
$stmt->bind_param("ssss", $admin_id, $search_param, $search_param, $search_param);
$stmt->execute();
$result = $stmt->get_result();

$string = "<tr>
    <th>ID</th>
    <th>Nom</th>
    <th>Téléphone</th>
    <th>Email</th>
    <th>Genre</th>
    <th>Date de naissance</th>
    <th>Date d'admission</th>
    <th>Adresse</th>
    <th>ID Parent</th>
    <th>ID Classe</th>
    <th>Photo</th>
</tr>";

$images_dir = "../images/";
while($row = $result->fetch_assoc()) {
    $picname = $row['id'];
    $string .= '<tr>
        <td>'.$row['id'].'</td>
        <td>'.$row['name'].'</td>
        <td>'.$row['phone'].'</td>
        <td>'.$row['email'].'</td>
        <td>'.$row['sex'].'</td>
        <td>'.$row['dob'].'</td>
        <td>'.$row['addmissiondate'].'</td>
        <td>'.$row['address'].'</td>
        <td>'.$row['parentid'].'</td>
        <td>'.$row['classid'].'</td>
        <td><img src="'.$images_dir.$picname.'.jpg" alt="'.$picname.'" width="150" height="150"></td>
    </tr>';
}

echo $string;
?>

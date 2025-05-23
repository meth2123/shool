<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

$admin_id = $_SESSION['login_id'];

$string = "<option>SELECT AN OPTION</option>";
$sql = "SELECT * FROM class WHERE created_by = ? OR created_by = '21' ORDER BY name, section";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()) {
    $string .= "<option value='".$row['id']."'>".$row['name']." [".$row['section']."]</option>";
}
echo $string;
?>

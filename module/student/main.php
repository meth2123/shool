<?php
include_once('../../service/mysqlcon.php');
include_once('../../service/db_utils.php');

$check = $_SESSION['login_id'];
$row = db_fetch_row("SELECT name FROM students WHERE id = ?", [$check]);
$login_session = $loged_user_name = $row['name'] ?? null;

if(!isset($login_session)) {
    header("Location:../../");
    exit();
}
?>

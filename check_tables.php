<?php
include_once('service/db_utils.php');

// Liste toutes les tables de la base de données
$query = "SHOW TABLES";
$tables = db_fetch_all($query, [], '');

echo "Tables dans la base de données:\n";
foreach ($tables as $table) {
    $table_name = array_values($table)[0];
    echo "- " . $table_name . "\n";
}
?> 
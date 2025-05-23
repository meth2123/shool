<?php
include_once('service/db_utils.php');

$tables = ['course', 'availablecourse', 'takencoursebyteacher'];

foreach ($tables as $table) {
    $query = "SHOW CREATE TABLE " . $table;
    try {
        $result = db_fetch_one($query, [], '');
        echo "\nTable: " . $table . "\n";
        echo $result['Create Table'] . "\n";
    } catch (Exception $e) {
        echo "Erreur pour la table " . $table . ": " . $e->getMessage() . "\n";
    }
} 
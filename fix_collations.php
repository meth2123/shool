<?php
include_once('service/db_utils.php');

// Vérification des collations actuelles
$tables = ['class', 'students', 'grade', 'bulletins'];
$query = "SELECT TABLE_NAME, TABLE_COLLATION 
          FROM information_schema.TABLES 
          WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME IN ('" . implode("','", $tables) . "')";

$results = db_fetch_all($query, [], '');
echo "Collations actuelles :\n";
foreach ($results as $result) {
    echo "{$result['TABLE_NAME']}: {$result['TABLE_COLLATION']}\n";
}

// Modification des tables pour utiliser utf8mb4_general_ci
$queries = [
    "ALTER TABLE class CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci",
    "ALTER TABLE students CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci",
    "ALTER TABLE grade CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci",
    "ALTER TABLE bulletins CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
];

echo "\nApplication des modifications...\n";
foreach ($queries as $query) {
    try {
        db_query($query, [], '');
        echo "Succès: $query\n";
    } catch (Exception $e) {
        echo "Erreur: " . $e->getMessage() . "\n";
    }
}

// Vérification finale
$results = db_fetch_all($query, [], '');
echo "\nNouvelles collations :\n";
foreach ($results as $result) {
    echo "{$result['TABLE_NAME']}: {$result['TABLE_COLLATION']}\n";
} 
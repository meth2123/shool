<?php
include_once('service/db_utils.php');

// Fonction pour vérifier si une colonne existe
function column_exists($table, $column) {
    $result = db_query("SHOW COLUMNS FROM $table LIKE '$column'");
    return $result->num_rows > 0;
}

// Fonction pour ajouter une colonne si elle n'existe pas
function add_column_if_not_exists($table, $column, $definition) {
    if (!column_exists($table, $column)) {
        try {
            db_query("ALTER TABLE $table ADD COLUMN $column $definition");
            echo "Colonne '$column' ajoutée avec succès.<br>";
            return true;
        } catch (Exception $e) {
            echo "Erreur lors de l'ajout de la colonne '$column': " . $e->getMessage() . "<br>";
            return false;
        }
    }
    return true;
}

// Vérifier et ajouter les colonnes nécessaires
$columns_to_add = [
    'title' => 'VARCHAR(255) AFTER courseid',
    'description' => 'TEXT AFTER title',
    'created_by' => 'VARCHAR(50) NOT NULL AFTER description'
];

$all_columns_added = true;
foreach ($columns_to_add as $column => $definition) {
    if (!add_column_if_not_exists('examschedule', $column, $definition)) {
        $all_columns_added = false;
    }
}

// Vérifier la structure finale de la table
echo "<br>Structure actuelle de la table examschedule :<br>";
$result = db_query("DESCRIBE examschedule");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Null'] . " - " . $row['Default'] . "<br>";
}

// Vérifier les données existantes
echo "<br>Données existantes dans la table :<br>";
$result = db_query("SELECT * FROM examschedule");
if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr>";
    foreach ($result->fetch_fields() as $field) {
        echo "<th>" . $field->name . "</th>";
    }
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Aucune donnée trouvée dans la table.<br>";
}

// Vérifier les contraintes de clé étrangère
echo "<br>Contraintes de clé étrangère :<br>";
$result = db_query("
    SELECT 
        TABLE_NAME,
        COLUMN_NAME,
        CONSTRAINT_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM
        INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
        REFERENCED_TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'examschedule'
");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Table: " . $row['TABLE_NAME'] . 
             ", Colonne: " . $row['COLUMN_NAME'] . 
             ", Contrainte: " . $row['CONSTRAINT_NAME'] . 
             ", Référence: " . $row['REFERENCED_TABLE_NAME'] . "." . $row['REFERENCED_COLUMN_NAME'] . "<br>";
    }
} else {
    echo "Aucune contrainte de clé étrangère trouvée.<br>";
}
?> 
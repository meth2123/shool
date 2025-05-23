<?php
include_once('service/db_utils.php');

// Vérifier si les colonnes existent déjà
$check_columns = "SHOW COLUMNS FROM examschedule LIKE 'title'";
$result = db_query($check_columns);
$title_exists = $result->num_rows > 0;

$check_columns = "SHOW COLUMNS FROM examschedule LIKE 'description'";
$result = db_query($check_columns);
$description_exists = $result->num_rows > 0;

// Ajouter les colonnes si elles n'existent pas
if (!$title_exists) {
    try {
        db_query("ALTER TABLE examschedule ADD COLUMN title VARCHAR(255) AFTER courseid");
        echo "Colonne 'title' ajoutée avec succès.<br>";
    } catch (Exception $e) {
        echo "Erreur lors de l'ajout de la colonne 'title': " . $e->getMessage() . "<br>";
    }
}

if (!$description_exists) {
    try {
        db_query("ALTER TABLE examschedule ADD COLUMN description TEXT AFTER title");
        echo "Colonne 'description' ajoutée avec succès.<br>";
    } catch (Exception $e) {
        echo "Erreur lors de l'ajout de la colonne 'description': " . $e->getMessage() . "<br>";
    }
}

// Vérifier la structure finale de la table
$check_structure = "DESCRIBE examschedule";
$result = db_query($check_structure);
echo "<br>Structure actuelle de la table examschedule :<br>";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "<br>";
}
?> 
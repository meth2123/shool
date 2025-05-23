<?php
require_once(__DIR__ . '/../../../db/config.php');

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Vérifier si la colonne existe déjà
    $check_column = "SHOW COLUMNS FROM attendance LIKE 'created_by'";
    $result = $conn->query($check_column);
    
    if ($result->num_rows == 0) {
        // La colonne n'existe pas, on l'ajoute
        $alter_table = "ALTER TABLE attendance ADD COLUMN created_by VARCHAR(50) AFTER attendedid";
        if ($conn->query($alter_table)) {
            echo "La colonne created_by a été ajoutée avec succès à la table attendance.\n";
        } else {
            throw new Exception("Erreur lors de l'ajout de la colonne: " . $conn->error);
        }
    } else {
        echo "La colonne created_by existe déjà dans la table attendance.\n";
    }

    $conn->close();
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
?> 
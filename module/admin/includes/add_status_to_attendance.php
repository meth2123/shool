<?php
require_once(__DIR__ . '/../../../db/config.php');

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Vérifier si la colonne existe déjà
    $check_column = "SHOW COLUMNS FROM attendance LIKE 'status'";
    $result = $conn->query($check_column);
    
    if ($result->num_rows == 0) {
        // La colonne n'existe pas, on l'ajoute
        $alter_table = "ALTER TABLE attendance ADD COLUMN status ENUM('present', 'absent') NOT NULL DEFAULT 'present' AFTER attendedid";
        if ($conn->query($alter_table)) {
            echo "La colonne status a été ajoutée avec succès à la table attendance.\n";
            
            // Mettre à jour les enregistrements existants
            $update_records = "UPDATE attendance SET status = 'present' WHERE status IS NULL";
            if ($conn->query($update_records)) {
                echo "Les enregistrements existants ont été mis à jour.\n";
            } else {
                throw new Exception("Erreur lors de la mise à jour des enregistrements: " . $conn->error);
            }
        } else {
            throw new Exception("Erreur lors de l'ajout de la colonne: " . $conn->error);
        }
    } else {
        echo "La colonne status existe déjà dans la table attendance.\n";
    }

    $conn->close();
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
?> 
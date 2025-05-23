<?php
require_once(__DIR__ . '/../../../db/config.php');

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Ajouter la colonne course_time si elle n'existe pas
    $check_column = "SHOW COLUMNS FROM attendance LIKE 'course_time'";
    $result = $conn->query($check_column);
    
    if ($result->num_rows == 0) {
        $alter_table = "ALTER TABLE attendance ADD COLUMN course_time TIME NULL AFTER date";
        if (!$conn->query($alter_table)) {
            throw new Exception("Erreur lors de l'ajout de la colonne course_time: " . $conn->error);
        }
        echo "La colonne course_time a été ajoutée avec succès.\n";
    } else {
        echo "La colonne course_time existe déjà.\n";
    }

    // Ajouter la colonne status si elle n'existe pas
    $check_column = "SHOW COLUMNS FROM attendance LIKE 'status'";
    $result = $conn->query($check_column);
    
    if ($result->num_rows == 0) {
        $alter_table = "ALTER TABLE attendance ADD COLUMN status ENUM('present', 'absent') NOT NULL DEFAULT 'present' AFTER course_time";
        if (!$conn->query($alter_table)) {
            throw new Exception("Erreur lors de l'ajout de la colonne status: " . $conn->error);
        }
        echo "La colonne status a été ajoutée avec succès.\n";
        
        // Mettre à jour les enregistrements existants
        $update_records = "UPDATE attendance SET status = 'present' WHERE status IS NULL";
        if (!$conn->query($update_records)) {
            throw new Exception("Erreur lors de la mise à jour des enregistrements: " . $conn->error);
        }
        echo "Les enregistrements existants ont été mis à jour.\n";
    } else {
        echo "La colonne status existe déjà.\n";
    }

    $conn->close();
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
?> 
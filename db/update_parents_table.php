<?php
require_once('config.php');

try {
    $conn = getDbConnection();
    
    // Add created_by column
    $sql1 = "ALTER TABLE parents 
             ADD COLUMN IF NOT EXISTS created_by VARCHAR(50) DEFAULT NULL,
             ADD INDEX IF NOT EXISTS idx_created_by (created_by)";
    
    if ($conn->query($sql1)) {
        echo "Colonne created_by ajoutée avec succès\n";
    }
    
    // Update existing records
    $sql2 = "UPDATE parents SET created_by = 'admin-default' WHERE created_by IS NULL";
    if ($conn->query($sql2)) {
        echo "Mise à jour des enregistrements existants réussie\n";
    }
    
    // Make created_by NOT NULL
    $sql3 = "ALTER TABLE parents MODIFY COLUMN created_by VARCHAR(50) NOT NULL";
    if ($conn->query($sql3)) {
        echo "Colonne created_by définie comme NOT NULL\n";
    }
    
    echo "Toutes les modifications ont été appliquées avec succès\n";
    
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 
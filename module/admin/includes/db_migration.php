<?php
include_once('../../service/mysqlcon.php');
include_once('admin_utils.php');

// Fonction pour ajouter la colonne created_by à une table
function addCreatedByToTable($link, $table) {
    // Vérifier si la colonne existe déjà
    $check_column = "SHOW COLUMNS FROM $table LIKE 'created_by'";
    $result = $link->query($check_column);

    if ($result && $result->num_rows === 0) {
        // Ajouter la colonne created_by
        $add_column = "ALTER TABLE $table ADD COLUMN created_by VARCHAR(20)";
        if ($link->query($add_column)) {
            echo "Colonne created_by ajoutée à la table $table avec succès\n";
            
            // Ajouter la clé étrangère
            $add_fk = "ALTER TABLE $table ADD FOREIGN KEY (created_by) REFERENCES admin(id)";
            if ($link->query($add_fk)) {
                echo "Clé étrangère ajoutée à la table $table avec succès\n";
            } else {
                echo "Erreur lors de l'ajout de la clé étrangère à $table: " . $link->error . "\n";
            }
        } else {
            echo "Erreur lors de l'ajout de la colonne à $table: " . $link->error . "\n";
        }
    } else {
        echo "La colonne created_by existe déjà dans la table $table\n";
    }

    // Obtenir l'ID du premier admin
    $sql = "SELECT id FROM admin ORDER BY id LIMIT 1";
    $result = $link->query($sql);
    $admin = $result->fetch_assoc();
    $default_admin = $admin['id'];

    // Mettre à jour les enregistrements existants
    $update_sql = "UPDATE $table SET created_by = ? WHERE created_by IS NULL";
    $stmt = $link->prepare($update_sql);
    $stmt->bind_param("s", $default_admin);
    $stmt->execute();
    
    echo "Mise à jour des enregistrements existants dans $table terminée\n";
}

// Liste des tables qui nécessitent la colonne created_by
$tables = [
    'students',
    'teachers',
    'staff',
    'parents',
    'course',
    'class'
];

// Ajouter la colonne created_by à toutes les tables
foreach ($tables as $table) {
    addCreatedByToTable($link, $table);
}

echo "Migration de la base de données terminée avec succès.\n";
?> 
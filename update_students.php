<?php
require_once 'service/mysqlcon.php';

// Vérifier si la colonne created_by existe
$result = $link->query("SHOW COLUMNS FROM students LIKE 'created_by'");
if ($result->num_rows === 0) {
    // Ajouter la colonne created_by
    $link->query("ALTER TABLE students ADD COLUMN created_by VARCHAR(50) NOT NULL DEFAULT 'ad-123-0'");
    echo "Colonne created_by ajoutée à la table students\n";
}

// Mettre à jour les enregistrements existants
$link->query("UPDATE students SET created_by = 'ad-123-0' WHERE created_by IS NULL OR created_by = ''");
echo "Enregistrements mis à jour\n";

// Vérifier si la clé étrangère existe
$result = $link->query("
    SELECT COUNT(*) as count 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'students'
    AND REFERENCED_TABLE_NAME = 'admin'
    AND COLUMN_NAME = 'created_by'
");
$row = $result->fetch_assoc();

if ($row['count'] === 0) {
    // Ajouter la clé étrangère
    $link->query("
        ALTER TABLE students 
        ADD CONSTRAINT fk_students_admin 
        FOREIGN KEY (created_by) 
        REFERENCES admin(id) 
        ON DELETE CASCADE
    ");
    echo "Clé étrangère ajoutée\n";
}

echo "Mise à jour terminée avec succès\n";
?> 
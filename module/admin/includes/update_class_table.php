<?php
include_once('../../../service/mysqlcon.php');

try {
    // Vérifier si l'admin par défaut existe
    $sql = "SELECT id FROM admin WHERE id = '21'";
    $result = $link->query($sql);
    
    if ($result->num_rows == 0) {
        // Créer l'admin par défaut s'il n'existe pas
        $sql = "INSERT INTO admin (id, name, password) VALUES ('21', 'Admin Système', 'admin123')";
        if (!$link->query($sql)) {
            throw new Exception("Erreur lors de la création de l'admin par défaut: " . $link->error);
        }
        echo "Admin par défaut créé avec succès.\n";
    }
    
    // Ajouter la colonne created_by si elle n'existe pas
    $sql = "ALTER TABLE class ADD COLUMN created_by varchar(20) DEFAULT '21'";
    if (!$link->query($sql)) {
        // Si la colonne existe déjà, ce n'est pas une erreur
        if ($link->errno != 1060) { // 1060 est le code d'erreur pour "Duplicate column name"
            throw new Exception("Erreur lors de l'ajout de la colonne: " . $link->error);
        }
    }
    
    // Ajouter la contrainte de clé étrangère si elle n'existe pas déjà
    $sql = "ALTER TABLE class ADD CONSTRAINT fk_class_admin 
            FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE SET NULL ON UPDATE CASCADE";
    if (!$link->query($sql)) {
        // Si la contrainte existe déjà, ce n'est pas une erreur
        if ($link->errno != 1061) { // 1061 est le code d'erreur pour "Duplicate key name"
            throw new Exception("Erreur lors de l'ajout de la contrainte: " . $link->error);
        }
    }
    
    // Mettre à jour les enregistrements existants où created_by est NULL
    $sql = "UPDATE class SET created_by = '21' WHERE created_by IS NULL";
    if (!$link->query($sql)) {
        throw new Exception("Erreur lors de la mise à jour des données: " . $link->error);
    }
    
    echo "La colonne created_by a été ajoutée et les données ont été mises à jour avec succès.\n";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
?> 
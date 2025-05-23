<?php
// Vérifier que la connexion à la base de données est disponible
if (!isset($link) || !($link instanceof mysqli)) {
    $root_path = $_SERVER['DOCUMENT_ROOT'] . '/gestion/';
    require_once($root_path . 'service/mysqlcon.php');
}

function addCreatedByColumnIfNotExists($link, $table) {
    // Vérifier que la connexion est valide
    if (!($link instanceof mysqli)) {
        error_log("Erreur: La connexion à la base de données n'est pas valide dans addCreatedByColumnIfNotExists");
        return false;
    }

    try {
        $sql = "SHOW COLUMNS FROM `$table` LIKE 'created_by'";
        $result = $link->query($sql);
        
        if ($result && $result->num_rows === 0) {
            $sql = "ALTER TABLE `$table` ADD COLUMN created_by VARCHAR(20)";
            $link->query($sql);
            
            // Ajouter la contrainte de clé étrangère
            $sql = "ALTER TABLE `$table` ADD FOREIGN KEY (created_by) REFERENCES admin(id)";
            $link->query($sql);
        }
        return true;
    } catch (Exception $e) {
        error_log("Erreur lors de l'ajout de la colonne created_by: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer les données filtrées par admin
function getDataByAdmin($link, $table, $admin_id, $additional_conditions = "") {
    if (!($link instanceof mysqli)) {
        error_log("Erreur: La connexion à la base de données n'est pas valide dans getDataByAdmin");
        return false;
    }

    $sql = "SELECT * FROM `$table` WHERE created_by = ? $additional_conditions";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("s", $admin_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Fonction pour compter les éléments par admin
function countDataByAdmin($link, $table, $admin_id) {
    $sql = "SELECT COUNT(*) as count FROM $table WHERE created_by = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("s", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Fonction pour vérifier si un admin a le droit de modifier/supprimer une donnée
function canAdminModifyData($link, $table, $admin_id, $data_id) {
    $sql = "SELECT created_by FROM $table WHERE id = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("s", $data_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row && $row['created_by'] === $admin_id;
}

// Initialiser les colonnes created_by pour toutes les tables principales
function initializeCreatedByColumns($link) {
    $tables = ['students', 'teachers', 'staff', 'parents', 'course', 'class'];
    foreach ($tables as $table) {
        addCreatedByColumnIfNotExists($link, $table);
    }
}

function getAllClasses($link) {
    $classes = array();
    $sql = "SELECT id, name, section FROM class ORDER BY name, section";
    $result = $link->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
    }
    return $classes;
}

// Fonction pour récupérer les classes créées par un admin spécifique
function getClassesByAdmin($link, $admin_id) {
    $classes = array();
    $sql = "SELECT id, name, section FROM class WHERE created_by = ? ORDER BY name, section";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("s", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
    }
    return $classes;
}

// Fonction pour compter les classes par admin
function countClassesByAdmin($link, $admin_id) {
    return countDataByAdmin($link, 'class', $admin_id);
}
?> 
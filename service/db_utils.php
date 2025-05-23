<?php
/**
 * Utilitaires pour la migration de mysql vers mysqli
 */

// Connexion à la base de données
$link = mysqli_connect('localhost', 'root', '', 'gestion');

if (!$link) {
    die("Erreur de connexion : " . mysqli_connect_error());
}

// Définir le jeu de caractères
mysqli_set_charset($link, "utf8");

/**
 * Exécute une requête SQL de manière sécurisée
 * @param string $sql La requête SQL avec des paramètres ?
 * @param array $params Les paramètres à lier
 * @param string $types Les types des paramètres (s: string, i: integer, d: double, b: blob)
 * @return mysqli_result|bool Le résultat de la requête
 */
function db_query($sql, $params = [], $types = '') {
    global $link;
    
    if (empty($params)) {
        $result = mysqli_query($link, $sql);
        if (!$result) {
            error_log("Erreur SQL: " . mysqli_error($link) . " - Requête: " . $sql);
            return false;
        }
        return $result;
    }
    
    $stmt = mysqli_prepare($link, $sql);
    if (!$stmt) {
        error_log("Erreur de préparation: " . mysqli_error($link) . " - Requête: " . $sql);
        return false;
    }
    
    // Si types n'est pas fourni, utiliser 's' pour tous les paramètres
    if (empty($types)) {
        $types = str_repeat('s', count($params));
    }
    
    // Vérifier que nous avons le bon nombre de paramètres
    if (strlen($types) !== count($params)) {
        error_log("Erreur: Nombre de types (" . strlen($types) . ") ne correspond pas au nombre de paramètres (" . count($params) . ")");
        mysqli_stmt_close($stmt);
        return false;
    }
    
    // Lier les paramètres
    if (!mysqli_stmt_bind_param($stmt, $types, ...$params)) {
        error_log("Erreur lors de la liaison des paramètres: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Erreur d'exécution: " . mysqli_stmt_error($stmt) . " - Requête: " . $sql);
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

/**
 * Exécute une requête SQL de type INSERT, UPDATE ou DELETE
 * @param string $sql La requête SQL
 * @param array $params Les paramètres à lier
 * @param string $types Les types des paramètres
 * @return bool True si la requête a réussi, False sinon
 */
function db_execute($sql, $params = [], $types = '') {
    global $link;
    
    if (!$link) {
        error_log("Erreur : Pas de connexion à la base de données");
        return false;
    }

    $stmt = $link->prepare($sql);
    if (!$stmt) {
        error_log("Erreur de préparation de la requête : " . $link->error);
        return false;
    }

    if (!empty($params)) {
        if (empty($types)) {
            $types = str_repeat('s', count($params));
        }
        $stmt->bind_param($types, ...$params);
    }

    $success = $stmt->execute();
    if (!$success) {
        error_log("Erreur d'exécution de la requête : " . $stmt->error);
    }
    
    return $success;
}

/**
 * Récupère une seule ligne de résultat
 * @param string $sql La requête SQL
 * @param array $params Les paramètres à lier
 * @param string $types Les types des paramètres
 * @return array|null La ligne de résultat ou null
 */
function db_fetch_row($sql, $params = [], $types = '') {
    $result = db_query($sql, $params, $types);
    if (!$result) {
        return false;
    }
    return mysqli_fetch_assoc($result);
}

/**
 * Récupère toutes les lignes de résultat
 * @param string $sql La requête SQL
 * @param array $params Les paramètres à lier
 * @param string $types Les types des paramètres
 * @return array Un tableau de lignes
 */
function db_fetch_all($sql, $params = [], $types = '') {
    $result = db_query($sql, $params, $types);
    if ($result === false) {
        error_log("Erreur lors de l'exécution de la requête: " . db_error());
        return [];
    }
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

// Fonction pour obtenir la dernière erreur SQL
function db_error() {
    global $link;
    return mysqli_error($link);
}

// Création de la table parent si elle n'existe pas
$create_parent_table = "CREATE TABLE IF NOT EXISTS parent (
    id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_by VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin(id)
)";

try {
    if ($link) {
        $link->query($create_parent_table);
    } else {
        error_log("Erreur : Pas de connexion à la base de données pour créer la table parent");
    }
} catch (Exception $e) {
    error_log("Erreur lors de la création de la table parent: " . $e->getMessage());
}
?> 
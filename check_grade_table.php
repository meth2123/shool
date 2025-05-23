<?php
require_once('service/db_utils.php');

try {
    $conn = getDbConnection();
    
    // Vérifier si la table existe
    $result = $conn->query("SHOW TABLES LIKE 'grade'");
    if ($result->num_rows === 0) {
        echo "La table 'grade' n'existe pas. Création de la table...\n";
        
        // Créer la table grade
        $sql = "CREATE TABLE IF NOT EXISTS `grade` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `studentid` varchar(20) NOT NULL,
            `grade` varchar(5) NOT NULL,
            `courseid` varchar(20) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if ($conn->query($sql)) {
            echo "Table 'grade' créée avec succès.\n";
        } else {
            echo "Erreur lors de la création de la table: " . $conn->error . "\n";
        }
    } else {
        echo "La table 'grade' existe. Structure actuelle:\n";
        $result = $conn->query("DESCRIBE grade");
        while ($row = $result->fetch_assoc()) {
            echo "Colonne: " . $row['Field'] . " | Type: " . $row['Type'] . " | Null: " . $row['Null'] . " | Key: " . $row['Key'] . " | Default: " . $row['Default'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 
<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Vérifier si l'utilisateur est connecté et est un administrateur
$check = $_SESSION['login_id'] ?? null;
if(!isset($check)) {
    header("Location:../../");
    exit();
}

echo "<h1>Structure de la table class_schedule</h1>";

// Vérifier la structure de la table class_schedule
$result = $link->query("DESCRIBE class_schedule");

if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
    
    $columns = [];
    while($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Vérifier si la colonne day_of_week existe
    if (!in_array('day_of_week', $columns)) {
        echo "<p style='color: red;'>La colonne 'day_of_week' n'existe pas dans la table class_schedule.</p>";
        
        // Ajouter la colonne day_of_week
        $add_column = "ALTER TABLE class_schedule ADD COLUMN day_of_week VARCHAR(20) NOT NULL AFTER slot_id";
        if ($link->query($add_column) === TRUE) {
            echo "<p style='color: green;'>La colonne 'day_of_week' a été ajoutée avec succès.</p>";
        } else {
            echo "<p style='color: red;'>Erreur lors de l'ajout de la colonne 'day_of_week': " . $link->error . "</p>";
        }
    } else {
        echo "<p style='color: green;'>La colonne 'day_of_week' existe déjà dans la table class_schedule.</p>";
    }
} else {
    echo "Erreur lors de la récupération de la structure: " . $link->error;
}

// Récupérer quelques exemples de données
echo "<h2>Exemples de données dans la table class_schedule</h2>";
$data = $link->query("SELECT * FROM class_schedule LIMIT 5");

if ($data && $data->num_rows > 0) {
    echo "<table border='1'>";
    
    // En-têtes de colonnes
    $first_row = $data->fetch_assoc();
    if ($first_row) {
        echo "<tr>";
        foreach (array_keys($first_row) as $column) {
            echo "<th>" . $column . "</th>";
        }
        echo "</tr>";
        
        // Afficher la première ligne
        echo "<tr>";
        foreach ($first_row as $value) {
            echo "<td>" . $value . "</td>";
        }
        echo "</tr>";
        
        // Afficher les lignes suivantes
        while($row = $data->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . $value . "</td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<p>Aucune donnée trouvée.</p>";
    }
    
    echo "</table>";
} else {
    echo "Aucune donnée trouvée ou erreur: " . $link->error;
}

echo "<br><a href='createTimeTable.php'>Retourner à la création d'emploi du temps</a>";
?>

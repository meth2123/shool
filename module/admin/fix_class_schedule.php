<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Vérifier si l'utilisateur est connecté et est un administrateur
$check = $_SESSION['login_id'] ?? null;
if(!isset($check)) {
    header("Location:../../");
    exit();
}

// Vérifier si la colonne day_of_week existe dans la table class_schedule
$check_column = "SHOW COLUMNS FROM class_schedule LIKE 'day_of_week'";
$result = $link->query($check_column);

if ($result && $result->num_rows == 0) {
    // La colonne n'existe pas, nous allons l'ajouter
    $add_column = "ALTER TABLE class_schedule ADD COLUMN day_of_week VARCHAR(20) NOT NULL AFTER slot_id";
    
    if ($link->query($add_column) === TRUE) {
        echo "La colonne 'day_of_week' a été ajoutée avec succès à la table class_schedule.<br>";
    } else {
        echo "Erreur lors de l'ajout de la colonne 'day_of_week': " . $link->error . "<br>";
    }
} else {
    echo "La colonne 'day_of_week' existe déjà dans la table class_schedule.<br>";
}

// Vérifier la structure complète de la table
echo "<h2>Structure actuelle de la table class_schedule</h2>";
$structure = "DESCRIBE class_schedule";
$result = $link->query($structure);

if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
    
    while($row = $result->fetch_assoc()) {
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
} else {
    echo "Erreur lors de la récupération de la structure: " . $link->error;
}

// Créer un lien pour retourner à la page de création d'emploi du temps
echo "<br><a href='createTimeTable.php'>Retourner à la création d'emploi du temps</a>";
?>

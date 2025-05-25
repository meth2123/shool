<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Vérifier si l'utilisateur est connecté et est un administrateur
$check = $_SESSION['login_id'] ?? null;
if(!isset($check)) {
    header("Location:../../");
    exit();
}

echo "<h1>Structure de la table course</h1>";

// Vérifier la structure de la table course
$result = $link->query("DESCRIBE course");

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

// Récupérer quelques exemples de données
echo "<h2>Exemples de données dans la table course</h2>";
$data = $link->query("SELECT * FROM course LIMIT 5");

if ($data && $data->num_rows > 0) {
    echo "<table border='1'>";
    
    // En-têtes de colonnes
    $first_row = $data->fetch_assoc();
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
    
    echo "</table>";
} else {
    echo "Aucune donnée trouvée ou erreur: " . $link->error;
}

echo "<br><a href='createTimeTable.php'>Retourner à la création d'emploi du temps</a>";
?>

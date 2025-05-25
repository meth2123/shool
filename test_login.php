<?php
// Script de test de connexion
session_start();
include_once('service/mysqlcon.php');

// Afficher les erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test de connexion</h1>";

// Vérifier la connexion à la base de données
if ($link) {
    echo "<p>Connexion à la base de données réussie</p>";
} else {
    echo "<p>Erreur de connexion à la base de données: " . mysqli_connect_error() . "</p>";
    exit;
}

// Afficher les utilisateurs disponibles
$sql = "SELECT userid, usertype, status FROM users";
$result = $link->query($sql);

echo "<h2>Utilisateurs disponibles :</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Type</th><th>Statut</th></tr>";

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['userid']) . "</td>";
        echo "<td>" . htmlspecialchars($row['usertype']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='3'>Aucun utilisateur trouvé</td></tr>";
}
echo "</table>";

// Formulaire de connexion
echo "<h2>Tester la connexion :</h2>";
echo "<form method='post' action=''>";
echo "<p>ID utilisateur : <input type='text' name='myid'></p>";
echo "<p>Mot de passe : <input type='password' name='mypassword'></p>";
echo "<p><input type='submit' name='submit' value='Connexion'></p>";
echo "</form>";

// Traitement du formulaire
if (isset($_POST['submit'])) {
    $myid = $_POST['myid'];
    $mypassword = $_POST['mypassword'];
    
    echo "<h2>Tentative de connexion pour : " . htmlspecialchars($myid) . "</h2>";
    
    // Récupérer les informations de l'utilisateur
    $sql = "SELECT usertype, password, status FROM users WHERE userid=?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("s", $myid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $stored_password = $row['password'];
        $usertype = $row['usertype'];
        $status = $row['status'];
        
        echo "<p>Utilisateur trouvé :</p>";
        echo "<ul>";
        echo "<li>Type : " . htmlspecialchars($usertype) . "</li>";
        echo "<li>Statut : " . htmlspecialchars($status) . "</li>";
        echo "<li>Mot de passe stocké : " . htmlspecialchars($stored_password) . "</li>";
        echo "<li>Mot de passe saisi : " . htmlspecialchars($mypassword) . "</li>";
        echo "</ul>";
        
        // Vérifier le statut
        if ($status !== 'active') {
            echo "<p style='color:red'>Erreur : Compte inactif</p>";
        }
        // Vérifier le mot de passe (comparaison simple pour le test)
        else if ($stored_password === $mypassword) {
            echo "<p style='color:green'>Connexion réussie !</p>";
        } else {
            echo "<p style='color:red'>Erreur : Mot de passe incorrect</p>";
        }
    } else {
        echo "<p style='color:red'>Erreur : Utilisateur non trouvé</p>";
    }
}
?>

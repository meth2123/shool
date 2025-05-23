<?php
include_once('../../../service/mysqlcon.php');

// Modifier la colonne password pour accepter des hash plus longs
$sql = "ALTER TABLE students MODIFY COLUMN password VARCHAR(255) NOT NULL";
if ($link->query($sql) === TRUE) {
    echo "La colonne password a été modifiée avec succès\n";
} else {
    echo "Erreur lors de la modification de la colonne: " . $link->error . "\n";
}

// Modifier aussi la table users car elle stocke aussi les mots de passe
$sql = "ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NOT NULL";
if ($link->query($sql) === TRUE) {
    echo "La colonne password de la table users a été modifiée avec succès\n";
} else {
    echo "Erreur lors de la modification de la colonne users: " . $link->error . "\n";
}

?> 
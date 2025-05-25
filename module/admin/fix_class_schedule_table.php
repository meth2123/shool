<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Vérifier si l'utilisateur est connecté et est un administrateur
$check = $_SESSION['login_id'] ?? null;
if(!isset($check)) {
    header("Location:../../");
    exit();
}

echo "<h1>Correction de la structure de la table class_schedule</h1>";

// Vérifier si la table class_schedule existe
$table_exists = $link->query("SHOW TABLES LIKE 'class_schedule'");
if ($table_exists && $table_exists->num_rows > 0) {
    echo "<p>La table class_schedule existe.</p>";
    
    // Vérifier si la colonne day_of_week existe
    $column_exists = $link->query("SHOW COLUMNS FROM class_schedule LIKE 'day_of_week'");
    if ($column_exists && $column_exists->num_rows == 0) {
        echo "<p>La colonne 'day_of_week' n'existe pas. Ajout de la colonne...</p>";
        
        // Ajouter la colonne day_of_week
        $add_column = "ALTER TABLE class_schedule ADD COLUMN day_of_week VARCHAR(20) NOT NULL AFTER slot_id";
        if ($link->query($add_column) === TRUE) {
            echo "<p style='color: green;'>La colonne 'day_of_week' a été ajoutée avec succès.</p>";
        } else {
            echo "<p style='color: red;'>Erreur lors de l'ajout de la colonne 'day_of_week': " . $link->error . "</p>";
        }
    } else {
        echo "<p>La colonne 'day_of_week' existe déjà.</p>";
    }
    
    // Afficher la structure actuelle de la table
    echo "<h2>Structure actuelle de la table class_schedule</h2>";
    $result = $link->query("DESCRIBE class_schedule");
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
    }
} else {
    echo "<p>La table class_schedule n'existe pas. Création de la table...</p>";
    
    // Créer la table class_schedule
    $create_table = "
    CREATE TABLE IF NOT EXISTS `class_schedule` (
      `id` int NOT NULL AUTO_INCREMENT,
      `class_id` varchar(50) NOT NULL,
      `subject_id` varchar(50) NOT NULL,
      `teacher_id` varchar(50) NOT NULL,
      `slot_id` int NOT NULL,
      `day_of_week` varchar(20) NOT NULL,
      `room` varchar(50) NOT NULL,
      `semester` varchar(10) NOT NULL,
      `academic_year` varchar(10) NOT NULL,
      `created_by` varchar(50) NOT NULL,
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `class_id` (`class_id`),
      KEY `subject_id` (`subject_id`),
      KEY `teacher_id` (`teacher_id`),
      KEY `slot_id` (`slot_id`),
      KEY `created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    ";
    
    if ($link->query($create_table) === TRUE) {
        echo "<p style='color: green;'>Table class_schedule créée avec succès.</p>";
    } else {
        echo "<p style='color: red;'>Erreur lors de la création de la table class_schedule: " . $link->error . "</p>";
    }
}

// Vérifier si la table time_slots existe
$table_exists = $link->query("SHOW TABLES LIKE 'time_slots'");
if ($table_exists && $table_exists->num_rows == 0) {
    echo "<p>La table time_slots n'existe pas. Création de la table...</p>";
    
    // Créer la table time_slots
    $create_table = "
    CREATE TABLE IF NOT EXISTS `time_slots` (
      `slot_id` int(11) NOT NULL AUTO_INCREMENT,
      `start_time` time NOT NULL,
      `end_time` time NOT NULL,
      PRIMARY KEY (`slot_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    ";
    
    if ($link->query($create_table) === TRUE) {
        echo "<p style='color: green;'>Table time_slots créée avec succès.</p>";
        
        // Ajouter quelques créneaux horaires par défaut
        $insert_slots = "
        INSERT INTO `time_slots` (`start_time`, `end_time`) VALUES
        ('08:00:00', '09:00:00'),
        ('09:00:00', '10:00:00'),
        ('10:00:00', '11:00:00'),
        ('11:00:00', '12:00:00'),
        ('12:00:00', '13:00:00'),
        ('13:00:00', '14:00:00'),
        ('14:00:00', '15:00:00'),
        ('15:00:00', '16:00:00'),
        ('16:00:00', '17:00:00')
        ";
        
        if ($link->query($insert_slots) === TRUE) {
            echo "<p style='color: green;'>Créneaux horaires par défaut ajoutés avec succès.</p>";
        } else {
            echo "<p style='color: red;'>Erreur lors de l'ajout des créneaux horaires par défaut: " . $link->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Erreur lors de la création de la table time_slots: " . $link->error . "</p>";
    }
} else {
    echo "<p>La table time_slots existe déjà.</p>";
}

echo "<br><a href='createTimeTable.php'>Retourner à la création d'emploi du temps</a>";
?>

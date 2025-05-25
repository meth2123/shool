<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Vérifier si l'utilisateur est connecté et est un administrateur
$check = $_SESSION['login_id'] ?? null;
if(!isset($check)) {
    header("Location:../../");
    exit();
}

echo "<h1>Reconstruction des tables pour l'emploi du temps</h1>";

// Vérifier si la table class_schedule existe
$table_exists = $link->query("SHOW TABLES LIKE 'class_schedule'");
if ($table_exists && $table_exists->num_rows > 0) {
    echo "La table class_schedule existe déjà.<br>";
    
    // Vérifier la structure actuelle de la table
    echo "<h2>Structure actuelle de la table class_schedule</h2>";
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
        echo "</table><br>";
        
        // Vérifier si les colonnes nécessaires existent
        $required_columns = [
            'id' => 'INT NOT NULL AUTO_INCREMENT',
            'class_id' => 'VARCHAR(50) NOT NULL',
            'subject_id' => 'VARCHAR(50) NOT NULL',
            'teacher_id' => 'VARCHAR(50) NOT NULL',
            'slot_id' => 'INT NOT NULL',
            'day_of_week' => 'VARCHAR(20) NOT NULL',
            'room' => 'VARCHAR(50) NOT NULL',
            'semester' => 'VARCHAR(10) NOT NULL',
            'academic_year' => 'VARCHAR(10) NOT NULL',
            'created_by' => 'VARCHAR(50) NOT NULL'
        ];
        
        $missing_columns = [];
        foreach ($required_columns as $column => $type) {
            if (!in_array($column, $columns)) {
                $missing_columns[$column] = $type;
            }
        }
        
        if (!empty($missing_columns)) {
            echo "<h3>Colonnes manquantes</h3>";
            echo "<ul>";
            foreach ($missing_columns as $column => $type) {
                echo "<li>$column ($type)</li>";
            }
            echo "</ul>";
            
            // Ajouter les colonnes manquantes
            foreach ($missing_columns as $column => $type) {
                $add_column = "ALTER TABLE class_schedule ADD COLUMN $column $type";
                if ($link->query($add_column) === TRUE) {
                    echo "Colonne '$column' ajoutée avec succès.<br>";
                } else {
                    echo "Erreur lors de l'ajout de la colonne '$column': " . $link->error . "<br>";
                }
            }
        } else {
            echo "Toutes les colonnes requises existent déjà.<br>";
        }
    } else {
        echo "Erreur lors de la récupération de la structure: " . $link->error . "<br>";
    }
} else {
    echo "La table class_schedule n'existe pas. Création de la table...<br>";
    
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
        echo "Table class_schedule créée avec succès.<br>";
    } else {
        echo "Erreur lors de la création de la table class_schedule: " . $link->error . "<br>";
    }
}

// Vérifier si la table time_slots existe
$table_exists = $link->query("SHOW TABLES LIKE 'time_slots'");
if ($table_exists && $table_exists->num_rows > 0) {
    echo "<br>La table time_slots existe déjà.<br>";
} else {
    echo "<br>La table time_slots n'existe pas. Création de la table...<br>";
    
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
        echo "Table time_slots créée avec succès.<br>";
        
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
            echo "Créneaux horaires par défaut ajoutés avec succès.<br>";
        } else {
            echo "Erreur lors de l'ajout des créneaux horaires par défaut: " . $link->error . "<br>";
        }
    } else {
        echo "Erreur lors de la création de la table time_slots: " . $link->error . "<br>";
    }
}

// Mettre à jour les fichiers PHP pour utiliser les noms de colonnes corrects
echo "<h2>Mise à jour des fichiers PHP</h2>";

// Mettre à jour createTimeTable.php
$file = 'createTimeTable.php';
$content = file_get_contents($file);
if ($content !== false) {
    // Remplacer classid par class_id et teacherid par teacher_id
    $content = str_replace('classid', 'class_id', $content);
    $content = str_replace('teacherid', 'teacher_id', $content);
    
    if (file_put_contents($file, $content) !== false) {
        echo "Fichier $file mis à jour avec succès.<br>";
    } else {
        echo "Erreur lors de la mise à jour du fichier $file.<br>";
    }
} else {
    echo "Erreur lors de la lecture du fichier $file.<br>";
}

// Mettre à jour updateTimeTable.php
$file = 'updateTimeTable.php';
$content = file_get_contents($file);
if ($content !== false) {
    // Remplacer classid par class_id et teacherid par teacher_id
    $content = str_replace('classid', 'class_id', $content);
    $content = str_replace('teacherid', 'teacher_id', $content);
    
    if (file_put_contents($file, $content) !== false) {
        echo "Fichier $file mis à jour avec succès.<br>";
    } else {
        echo "Erreur lors de la mise à jour du fichier $file.<br>";
    }
} else {
    echo "Erreur lors de la lecture du fichier $file.<br>";
}

// Mettre à jour timeTable.php
$file = 'timeTable.php';
$content = file_get_contents($file);
if ($content !== false) {
    // Remplacer classid par class_id et teacherid par teacher_id
    $content = str_replace('classid', 'class_id', $content);
    $content = str_replace('teacherid', 'teacher_id', $content);
    
    if (file_put_contents($file, $content) !== false) {
        echo "Fichier $file mis à jour avec succès.<br>";
    } else {
        echo "Erreur lors de la mise à jour du fichier $file.<br>";
    }
} else {
    echo "Erreur lors de la lecture du fichier $file.<br>";
}

// Mettre à jour viewTeacherSchedules.php
$file = 'viewTeacherSchedules.php';
$content = file_get_contents($file);
if ($content !== false) {
    // Remplacer classid par class_id et teacherid par teacher_id
    $content = str_replace('classid', 'class_id', $content);
    $content = str_replace('teacherid', 'teacher_id', $content);
    
    if (file_put_contents($file, $content) !== false) {
        echo "Fichier $file mis à jour avec succès.<br>";
    } else {
        echo "Erreur lors de la mise à jour du fichier $file.<br>";
    }
} else {
    echo "Erreur lors de la lecture du fichier $file.<br>";
}

// Mettre à jour viewClassSchedules.php
$file = 'viewClassSchedules.php';
$content = file_get_contents($file);
if ($content !== false) {
    // Remplacer classid par class_id et teacherid par teacher_id
    $content = str_replace('classid', 'class_id', $content);
    $content = str_replace('teacherid', 'teacher_id', $content);
    
    if (file_put_contents($file, $content) !== false) {
        echo "Fichier $file mis à jour avec succès.<br>";
    } else {
        echo "Erreur lors de la mise à jour du fichier $file.<br>";
    }
} else {
    echo "Erreur lors de la lecture du fichier $file.<br>";
}

// Mettre à jour deleteTimeTable.php
$file = 'deleteTimeTable.php';
$content = file_get_contents($file);
if ($content !== false) {
    // Remplacer classid par class_id et teacherid par teacher_id
    $content = str_replace('classid', 'class_id', $content);
    $content = str_replace('teacherid', 'teacher_id', $content);
    
    if (file_put_contents($file, $content) !== false) {
        echo "Fichier $file mis à jour avec succès.<br>";
    } else {
        echo "Erreur lors de la mise à jour du fichier $file.<br>";
    }
} else {
    echo "Erreur lors de la lecture du fichier $file.<br>";
}

// Mettre à jour create_timetable_tables.php
$file = 'create_timetable_tables.php';
$content = file_get_contents($file);
if ($content !== false) {
    // Remplacer classid par class_id et teacherid par teacher_id
    $content = str_replace('classid', 'class_id', $content);
    $content = str_replace('teacherid', 'teacher_id', $content);
    
    if (file_put_contents($file, $content) !== false) {
        echo "Fichier $file mis à jour avec succès.<br>";
    } else {
        echo "Erreur lors de la mise à jour du fichier $file.<br>";
    }
} else {
    echo "Erreur lors de la lecture du fichier $file.<br>";
}

echo "<br><a href='createTimeTable.php'>Retourner à la création d'emploi du temps</a>";
?>

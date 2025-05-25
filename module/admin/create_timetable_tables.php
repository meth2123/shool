<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

// Vérifier si l'utilisateur est connecté et est un administrateur
$check = $_SESSION['login_id'] ?? null;
if(!isset($check)) {
    header("Location:../../");
    exit();
}

// Créer la table time_slots si elle n'existe pas
$time_slots_table = "
CREATE TABLE IF NOT EXISTS `time_slots` (
  `slot_id` int(11) NOT NULL AUTO_INCREMENT,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  PRIMARY KEY (`slot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
";

// Créer la table class_schedule si elle n'existe pas
$class_schedule_table = "
CREATE TABLE IF NOT EXISTS `class_schedule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `classid` varchar(50) NOT NULL,
  `subject_id` varchar(50) NOT NULL,
  `teacherid` varchar(50) NOT NULL,
  `slot_id` int NOT NULL,
  `day_of_week` varchar(20) NOT NULL,
  `room` varchar(50) NOT NULL,
  `semester` varchar(10) NOT NULL,
  `academic_year` varchar(10) NOT NULL,
  `created_by` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `classid` (`classid`),
  KEY `subject_id` (`subject_id`),
  KEY `teacherid` (`teacherid`),
  KEY `slot_id` (`slot_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
";

// Exécuter les requêtes de création de tables
$success_message = '';
$error_message = '';

if ($link->query($time_slots_table) === TRUE) {
    $success_message .= "Table 'time_slots' créée avec succès.<br>";
    
    // Vérifier si la table est vide et ajouter des créneaux horaires par défaut
    $result = $link->query("SELECT COUNT(*) as count FROM time_slots");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $default_slots = [
            ['08:00:00', '09:00:00'],
            ['09:00:00', '10:00:00'],
            ['10:00:00', '11:00:00'],
            ['11:00:00', '12:00:00'],
            ['13:00:00', '14:00:00'],
            ['14:00:00', '15:00:00'],
            ['15:00:00', '16:00:00'],
            ['16:00:00', '17:00:00']
        ];
        
        $insert_success = true;
        foreach ($default_slots as $slot) {
            $stmt = $link->prepare("INSERT INTO time_slots (start_time, end_time) VALUES (?, ?)");
            $stmt->bind_param("ss", $slot[0], $slot[1]);
            if (!$stmt->execute()) {
                $insert_success = false;
                $error_message .= "Erreur lors de l'insertion du créneau horaire " . $slot[0] . " - " . $slot[1] . ": " . $link->error . "<br>";
            }
        }
        
        if ($insert_success) {
            $success_message .= "Créneaux horaires par défaut ajoutés avec succès.<br>";
        }
    }
} else {
    $error_message .= "Erreur lors de la création de la table 'time_slots': " . $link->error . "<br>";
}

if ($link->query($class_schedule_table) === TRUE) {
    $success_message .= "Table 'class_schedule' créée avec succès.<br>";
} else {
    $error_message .= "Erreur lors de la création de la table 'class_schedule': " . $link->error . "<br>";
}

// Rediriger vers la page d'emploi du temps avec un message
if (!empty($success_message)) {
    $_SESSION['success_message'] = $success_message;
}
if (!empty($error_message)) {
    $_SESSION['error_message'] = $error_message;
}

header("Location: timeTable.php");
exit();
?>

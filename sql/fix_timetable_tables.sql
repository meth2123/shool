-- Supprimer les tables existantes si nécessaire
DROP TABLE IF EXISTS class_schedule;
DROP TABLE IF EXISTS time_slots;

-- Table pour les créneaux horaires
CREATE TABLE IF NOT EXISTS time_slots (
    slot_id INT PRIMARY KEY AUTO_INCREMENT,
    day_number INT NOT NULL COMMENT '1=Lundi, 2=Mardi, etc.',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_slot (day_number, start_time, end_time)
);

-- Table pour l'emploi du temps des classes
CREATE TABLE IF NOT EXISTS class_schedule (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id VARCHAR(20) NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    slot_id INT NOT NULL,
    room VARCHAR(50) NOT NULL,
    created_by INT NOT NULL COMMENT 'ID de l\'administrateur qui a créé l\'emploi du temps',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES class(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES time_slots(slot_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE CASCADE,
    UNIQUE KEY unique_schedule (class_id, slot_id),
    UNIQUE KEY unique_teacher_slot (teacher_id, slot_id)
);

-- Ajouter le champ created_by à la table class si elle n'existe pas déjà
SET @dbname = DATABASE();
SET @tablename = "class";
SET @columnname = "created_by";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE 
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " INT NOT NULL COMMENT 'ID de l\'administrateur qui a créé la classe' AFTER id")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Ajouter la clé étrangère si elle n'existe pas déjà
SET @constraintName = "fk_class_admin";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE 
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (REFERENCED_TABLE_NAME = "admin")
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD CONSTRAINT ", @constraintName, " FOREIGN KEY (", @columnname, ") REFERENCES admin(id) ON DELETE CASCADE")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Ajouter le champ created_by à la table teachers si elle n'existe pas déjà
SET @tablename = "teachers";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE 
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " INT NOT NULL COMMENT 'ID de l\'administrateur qui a créé l\'enseignant' AFTER id")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Ajouter la clé étrangère pour teachers si elle n'existe pas déjà
SET @constraintName = "fk_teachers_admin";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE 
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (REFERENCED_TABLE_NAME = "admin")
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD CONSTRAINT ", @constraintName, " FOREIGN KEY (", @columnname, ") REFERENCES admin(id) ON DELETE CASCADE")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Insertion des créneaux horaires par défaut
INSERT INTO time_slots (day_number, start_time, end_time) VALUES
-- Lundi
(1, '08:00:00', '09:00:00'),
(1, '09:00:00', '10:00:00'),
(1, '10:15:00', '11:15:00'),
(1, '11:15:00', '12:15:00'),
(1, '15:00:00', '16:00:00'),
(1, '16:00:00', '17:00:00'),
-- Mardi
(2, '08:00:00', '09:00:00'),
(2, '09:00:00', '10:00:00'),
(2, '10:15:00', '11:15:00'),
(2, '11:15:00', '12:15:00'),
(2, '15:00:00', '16:00:00'),
(2, '16:00:00', '17:00:00'),
-- Mercredi
(3, '08:00:00', '09:00:00'),
(3, '09:00:00', '10:00:00'),
(3, '10:15:00', '11:15:00'),
(3, '11:15:00', '12:15:00'),
(3, '15:00:00', '16:00:00'),
(3, '16:00:00', '17:00:00'),
-- Jeudi
(4, '08:00:00', '09:00:00'),
(4, '09:00:00', '10:00:00'),
(4, '10:15:00', '11:15:00'),
(4, '11:15:00', '12:15:00'),
(4, '15:00:00', '16:00:00'),
(4, '16:00:00', '17:00:00'),
-- Vendredi
(5, '08:00:00', '09:00:00'),
(5, '09:00:00', '10:00:00'),
(5, '10:15:00', '11:15:00'),
(5, '11:15:00', '12:15:00'),
(5, '15:00:00', '16:00:00'),
(5, '16:00:00', '17:00:00'),
-- Samedi
(6, '08:00:00', '09:00:00'),
(6, '09:00:00', '10:00:00'),
(6, '10:15:00', '11:15:00'),
(6, '11:15:00', '12:15:00'),
(6, '15:00:00', '16:00:00'),
(6, '16:00:00', '17:00:00'); 
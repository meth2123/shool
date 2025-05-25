-- Créer la table des matières (subjects)
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_by VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Créer la table des créneaux horaires si elle n'existe pas
CREATE TABLE IF NOT EXISTS time_slots (
    slot_id INT AUTO_INCREMENT PRIMARY KEY,
    day_number INT NOT NULL COMMENT '1=Lundi, 2=Mardi, etc.',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_slot (day_number, start_time, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Créer la table de l'emploi du temps des classes si elle n'existe pas
CREATE TABLE IF NOT EXISTS class_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id VARCHAR(20) NOT NULL,
    subject_id INT NOT NULL,
    teacher_id VARCHAR(20) NOT NULL,
    slot_id INT NOT NULL,
    room VARCHAR(50) NOT NULL,
    created_by VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES class(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES time_slots(slot_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE CASCADE,
    UNIQUE KEY unique_schedule (class_id, slot_id),
    UNIQUE KEY unique_teacher_slot (teacher_id, slot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insérer quelques créneaux horaires par défaut s'ils n'existent pas
INSERT IGNORE INTO time_slots (day_number, start_time, end_time) VALUES
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

-- Insérer quelques matières par défaut pour l'administrateur par défaut
INSERT IGNORE INTO subjects (name, description, created_by) VALUES
('Mathématiques', 'Cours de mathématiques', 'ad-123-0'),
('Français', 'Cours de français', 'ad-123-0'),
('Anglais', 'Cours d''anglais', 'ad-123-0'),
('Histoire', 'Cours d''histoire', 'ad-123-0'),
('Géographie', 'Cours de géographie', 'ad-123-0'),
('Sciences', 'Cours de sciences', 'ad-123-0'),
('Informatique', 'Cours d''informatique', 'ad-123-0'),
('Sport', 'Cours d''éducation physique', 'ad-123-0'); 
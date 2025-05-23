-- Création de la table teacher_absences si elle n'existe pas
CREATE TABLE IF NOT EXISTS teacher_absences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    teacher_id VARCHAR(50) NOT NULL,
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_teacher_date (teacher_id, date),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    FOREIGN KEY (created_by) REFERENCES admin(id)
); 
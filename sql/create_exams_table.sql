USE gestion;

-- Suppression des tables existantes si elles existent
DROP TABLE IF EXISTS exam_results;
DROP TABLE IF EXISTS exams;

-- Création de la table exams
CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    type ENUM('Contrôle', 'Examen', 'Devoir', 'Projet') NOT NULL,
    coefficient DECIMAL(3,2) NOT NULL DEFAULT 1.00,
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    -- Contraintes de clé étrangère
    FOREIGN KEY (course_id) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE CASCADE,

    -- Index pour améliorer les performances
    INDEX idx_course_exams (course_id),
    INDEX idx_exam_date (date),
    INDEX idx_exam_type (type),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

-- Création de la table exam_results pour stocker les notes
CREATE TABLE IF NOT EXISTS exam_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_id VARCHAR(20) NOT NULL,
    score DECIMAL(4,2) NULL,
    comments TEXT NULL,
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    -- Contraintes de clé étrangère
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE CASCADE,

    -- Index pour améliorer les performances
    INDEX idx_exam_results (exam_id, student_id),
    INDEX idx_student_results (student_id),
    INDEX idx_created_by (created_by),

    -- Contrainte d'unicité pour éviter les doublons de notes
    UNIQUE KEY unique_exam_student (exam_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci; 
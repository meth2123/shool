USE gestion;

-- Renommer les colonnes pour correspondre à la nouvelle convention
ALTER TABLE grade
    CHANGE studentid student_id VARCHAR(50) NOT NULL,
    CHANGE courseid course_id VARCHAR(50) NOT NULL;

-- Ajouter les nouvelles colonnes
ALTER TABLE grade
    ADD COLUMN teacher_id VARCHAR(50) NOT NULL AFTER student_id,
    ADD COLUMN class_id VARCHAR(50) NOT NULL AFTER course_id,
    ADD COLUMN status ENUM('pending', 'validated', 'rejected') DEFAULT 'pending' AFTER grade,
    ADD COLUMN created_by VARCHAR(50) NOT NULL,
    ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN validated_by VARCHAR(50) NULL,
    ADD COLUMN validated_at TIMESTAMP NULL,
    ADD COLUMN rejected_by VARCHAR(50) NULL,
    ADD COLUMN rejected_at TIMESTAMP NULL,
    ADD COLUMN rejection_reason TEXT NULL;

-- Ajouter les clés étrangères
ALTER TABLE grade
    ADD FOREIGN KEY (student_id) REFERENCES students(id),
    ADD FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    ADD FOREIGN KEY (course_id) REFERENCES course(id),
    ADD FOREIGN KEY (class_id) REFERENCES class(id),
    ADD FOREIGN KEY (created_by) REFERENCES admin(id),
    ADD FOREIGN KEY (validated_by) REFERENCES admin(id),
    ADD FOREIGN KEY (rejected_by) REFERENCES admin(id); 
USE gestion;

-- Ajout des colonnes pour gérer les types de notes
ALTER TABLE student_teacher_course
ADD COLUMN grade_type ENUM('devoir', 'examen') DEFAULT NULL,
ADD COLUMN grade_number INT DEFAULT 1,
ADD COLUMN coefficient DECIMAL(3,2) DEFAULT 1.00; 
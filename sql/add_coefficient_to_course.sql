USE gestion;

-- Ajout de la colonne coefficient à la table course
ALTER TABLE course
ADD COLUMN coefficient DECIMAL(3,2) DEFAULT 1.00,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Mise à jour des coefficients existants dans student_teacher_course
UPDATE student_teacher_course stc
JOIN course c ON stc.course_id = c.id
SET stc.coefficient = c.coefficient
WHERE stc.grade_type = 'examen'; 
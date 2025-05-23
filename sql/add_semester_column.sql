USE gestion;

-- Ajout de la colonne semester à la table student_teacher_course
ALTER TABLE student_teacher_course
ADD COLUMN semester TINYINT DEFAULT 1,
ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL; 
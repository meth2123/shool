USE gestion;

-- Ajout de la colonne grade à la table student_teacher_course
ALTER TABLE student_teacher_course
ADD COLUMN grade DECIMAL(4,2) NULL; 
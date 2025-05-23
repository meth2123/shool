USE gestion;

-- Ajout d'une contrainte d'unicité pour les notes
ALTER TABLE student_teacher_course
ADD UNIQUE KEY unique_grade (student_id, teacher_id, course_id, class_id, grade_type, semester); 
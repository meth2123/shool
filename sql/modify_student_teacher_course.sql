USE gestion;

-- Supprimer les contraintes d'unicité existantes
ALTER TABLE student_teacher_course
DROP INDEX IF EXISTS unique_assignment,
DROP INDEX IF EXISTS unique_grade;

-- Supprimer la contrainte check_grade_number existante
ALTER TABLE student_teacher_course
DROP CONSTRAINT IF EXISTS check_grade_number;

-- Ajouter une nouvelle contrainte d'unicité qui inclut grade_type et grade_number
ALTER TABLE student_teacher_course
ADD CONSTRAINT unique_student_grade UNIQUE (
    student_id,
    teacher_id,
    course_id,
    class_id,
    grade_type,
    grade_number,
    semester
);

-- Ajouter un index pour améliorer les performances
CREATE INDEX idx_student_grades ON student_teacher_course (
    student_id,
    teacher_id,
    course_id,
    class_id,
    semester
);

-- Mettre à jour les notes existantes pour s'assurer qu'elles ont un grade_type et grade_number valides
UPDATE student_teacher_course
SET grade_type = 'devoir',
    grade_number = 1
WHERE grade_type IS NULL OR grade_type = '';

-- Ajouter des contraintes pour s'assurer que les valeurs sont valides
ALTER TABLE student_teacher_course
MODIFY COLUMN grade_type ENUM('devoir', 'examen') NOT NULL,
MODIFY COLUMN grade_number INT NOT NULL DEFAULT 1,
MODIFY COLUMN coefficient DECIMAL(3,2) NOT NULL DEFAULT 1.00;

-- Ajouter une nouvelle contrainte de vérification pour grade_number
ALTER TABLE student_teacher_course
ADD CONSTRAINT check_grade_number CHECK (
    (grade_type = 'devoir' AND grade_number IN (1, 2)) OR
    (grade_type = 'examen' AND grade_number IN (1, 2, 3))
); 
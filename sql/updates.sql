-- Ajout du champ created_by à la table teachers
ALTER TABLE teachers
ADD COLUMN created_by VARCHAR(50) NOT NULL,
ADD INDEX idx_teacher_created_by (created_by),
ADD FOREIGN KEY (created_by) REFERENCES users(userid);

-- Ajout du champ created_by à la table students
ALTER TABLE students
ADD COLUMN created_by VARCHAR(50) NOT NULL,
ADD INDEX idx_student_created_by (created_by),
ADD FOREIGN KEY (created_by) REFERENCES users(userid);

-- Mise à jour des enregistrements existants
-- Remplacer 'admin_default' par l'ID de l'administrateur par défaut
UPDATE teachers SET created_by = 'admin_default' WHERE created_by IS NULL;
UPDATE students SET created_by = 'admin_default' WHERE created_by IS NULL; 
-- Ajout de la colonne created_by à la table examschedule
ALTER TABLE examschedule
ADD COLUMN created_by VARCHAR(50) NOT NULL AFTER course_id,
ADD INDEX idx_created_by (created_by),
ADD CONSTRAINT fk_examschedule_created_by
FOREIGN KEY (created_by) REFERENCES users(userid)
ON DELETE RESTRICT
ON UPDATE CASCADE; 
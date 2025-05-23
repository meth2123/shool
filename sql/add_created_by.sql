-- Ajout de la colonne created_by à la table examschedule
ALTER TABLE examschedule ADD COLUMN created_by VARCHAR(50) NOT NULL AFTER courseid;

-- Mise à jour des enregistrements existants avec l'ID de l'administrateur par défaut
UPDATE examschedule SET created_by = 'admin' WHERE created_by = ''; 
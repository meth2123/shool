USE gestion;

-- Ajouter les colonnes manquantes à la table subscriptions
ALTER TABLE subscriptions
ADD COLUMN director_name VARCHAR(100) NOT NULL AFTER school_name,
ADD COLUMN address TEXT AFTER admin_phone; 
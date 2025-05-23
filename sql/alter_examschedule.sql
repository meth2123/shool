-- Ajout des colonnes title et description à la table examschedule
ALTER TABLE examschedule
ADD COLUMN title VARCHAR(255) AFTER courseid,
ADD COLUMN description TEXT AFTER title; 
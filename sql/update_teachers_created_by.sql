-- Mettre à jour le champ created_by pour les enseignants existants
-- On attribue temporairement tous les enseignants à l'administrateur par défaut
-- Vous devrez ensuite les réassigner aux bons administrateurs

-- D'abord, s'assurer que la colonne created_by existe
SET @dbname = DATABASE();
SET @tablename = "teachers";
SET @columnname = "created_by";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE 
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " INT NOT NULL COMMENT 'ID de l\'administrateur qui a créé l\'enseignant' AFTER id")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Mettre à jour les enregistrements existants avec l'ID de l'administrateur par défaut
-- Remplacez '1' par l'ID de l'administrateur par défaut
UPDATE teachers SET created_by = 1 WHERE created_by IS NULL OR created_by = '';

-- Ajouter la clé étrangère si elle n'existe pas déjà
SET @constraintName = "fk_teachers_admin";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE 
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (REFERENCED_TABLE_NAME = "admin")
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD CONSTRAINT ", @constraintName, " FOREIGN KEY (", @columnname, ") REFERENCES admin(id) ON DELETE CASCADE")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists; 
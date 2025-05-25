-- Mettre à jour le champ created_by pour les élèves existants
-- On attribue temporairement tous les élèves à l'administrateur par défaut (ad-123-0)

-- D'abord, s'assurer que la colonne created_by existe
SET @dbname = DATABASE();
SET @tablename = "students";
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
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " VARCHAR(50) NOT NULL DEFAULT 'ad-123-0'")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Mettre à jour les enregistrements existants avec l'ID de l'administrateur par défaut
UPDATE students SET created_by = 'ad-123-0' WHERE created_by IS NULL OR created_by = '';

-- Ajouter la clé étrangère si elle n'existe pas déjà
SET @constraintName = "fk_students_admin";
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
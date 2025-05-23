-- Add created_by column to parents table
ALTER TABLE parents
ADD COLUMN created_by VARCHAR(50) DEFAULT NULL,
ADD INDEX idx_created_by (created_by);

-- Update existing records to set created_by based on admin who created them
-- You may need to set a default admin ID here
UPDATE parents SET created_by = 'admin-default' WHERE created_by IS NULL;

-- Make created_by NOT NULL after updating existing records
ALTER TABLE parents
MODIFY COLUMN created_by VARCHAR(50) NOT NULL; 
-- Add created_by column to payment table
ALTER TABLE payment
ADD COLUMN created_by VARCHAR(50) DEFAULT NULL,
ADD INDEX idx_created_by (created_by);

-- Update existing records to set created_by based on student's creator
UPDATE payment p
INNER JOIN students s ON p.studentid = s.id
SET p.created_by = s.created_by;

-- Make created_by NOT NULL after updating existing records
ALTER TABLE payment
MODIFY COLUMN created_by VARCHAR(50) NOT NULL; 
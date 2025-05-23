USE gestion;

CREATE TABLE IF NOT EXISTS bulletins (
    id VARCHAR(50) PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    class_id VARCHAR(50) NOT NULL,
    period ENUM('1', '2', '3') NOT NULL,
    school_year VARCHAR(9) NOT NULL,
    average DECIMAL(4,2),
    rank INT,
    comments TEXT,
    status ENUM('draft', 'published') DEFAULT 'draft',
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (class_id) REFERENCES class(id),
    FOREIGN KEY (created_by) REFERENCES admin(id),
    UNIQUE KEY unique_bulletin (student_id, class_id, period, school_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 
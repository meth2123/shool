USE gestion;

CREATE TABLE IF NOT EXISTS grades (
    id VARCHAR(50) PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    teacher_id VARCHAR(50) NOT NULL,
    course_id VARCHAR(50) NOT NULL,
    class_id VARCHAR(50) NOT NULL,
    grade DECIMAL(4,2) NOT NULL,
    status ENUM('pending', 'validated', 'rejected') DEFAULT 'pending',
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    validated_by VARCHAR(50) NULL,
    validated_at TIMESTAMP NULL,
    rejected_by VARCHAR(50) NULL,
    rejected_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    FOREIGN KEY (course_id) REFERENCES course(id),
    FOREIGN KEY (class_id) REFERENCES class(id),
    FOREIGN KEY (created_by) REFERENCES admin(id),
    FOREIGN KEY (validated_by) REFERENCES admin(id),
    FOREIGN KEY (rejected_by) REFERENCES admin(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 
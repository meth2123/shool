-- Table de liaison entre les étudiants et les cours
CREATE TABLE student_courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    studentid VARCHAR(50) NOT NULL,
    courseid VARCHAR(50) NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Contraintes de clé étrangère
    FOREIGN KEY (studentid) REFERENCES students(id),
    FOREIGN KEY (courseid) REFERENCES course(id),
    FOREIGN KEY (created_by) REFERENCES users(userid),
    
    -- Index pour améliorer les performances
    INDEX idx_student_course (studentid, courseid),
    INDEX idx_course_students (courseid, studentid),
    INDEX idx_enrollment_status (status),
    
    -- Contrainte d'unicité pour éviter les doublons d'inscription
    UNIQUE KEY unique_enrollment (studentid, courseid)
); 
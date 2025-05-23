USE gestion;

-- Table des professeurs
CREATE TABLE IF NOT EXISTS teachers (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    subject VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des cours
CREATE TABLE IF NOT EXISTS course (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    teacherid VARCHAR(50),
    studentid VARCHAR(50),
    classid VARCHAR(20),
    created_by VARCHAR(50),
    FOREIGN KEY (teacherid) REFERENCES teachers(id),
    FOREIGN KEY (studentid) REFERENCES students(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des notes d'examens
CREATE TABLE IF NOT EXISTS exam_marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    studentid VARCHAR(50),
    courseid VARCHAR(50),
    score DECIMAL(5,2),
    date DATE DEFAULT CURRENT_DATE,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (studentid) REFERENCES students(id),
    FOREIGN KEY (courseid) REFERENCES course(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des présences
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    studentid VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent') NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (studentid) REFERENCES students(id),
    UNIQUE KEY unique_attendance (studentid, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 
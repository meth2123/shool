-- Table pour l'historique des salaires des enseignants
CREATE TABLE IF NOT EXISTS teacher_salary_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id VARCHAR(20) NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    base_salary DECIMAL(10,2) NOT NULL,
    days_present INT NOT NULL,
    days_absent INT NOT NULL,
    final_salary DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    created_by VARCHAR(20) NOT NULL,
    UNIQUE KEY month_year_teacher (month, year, teacher_id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table pour l'historique des salaires du personnel
CREATE TABLE IF NOT EXISTS staff_salary_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(20) NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    base_salary DECIMAL(10,2) NOT NULL,
    days_present INT NOT NULL,
    days_absent INT NOT NULL,
    final_salary DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    created_by VARCHAR(20) NOT NULL,
    UNIQUE KEY month_year_staff (month, year, staff_id),
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8; 
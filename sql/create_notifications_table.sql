-- Table des notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL COMMENT 'ID de l\'utilisateur (admin, enseignant ou élève)',
    user_type ENUM('admin', 'teacher', 'student') NOT NULL COMMENT 'Type d\'utilisateur',
    title VARCHAR(255) NOT NULL COMMENT 'Titre de la notification',
    message TEXT NOT NULL COMMENT 'Contenu de la notification',
    type ENUM('info', 'success', 'warning', 'error') NOT NULL DEFAULT 'info' COMMENT 'Type de notification',
    link VARCHAR(255) NULL COMMENT 'Lien optionnel associé à la notification',
    is_read BOOLEAN DEFAULT FALSE COMMENT 'Indique si la notification a été lue',
    created_by VARCHAR(50) NOT NULL COMMENT 'ID de l\'utilisateur qui a créé la notification',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE CASCADE,
    INDEX idx_user_notifications (user_id, user_type),
    INDEX idx_notification_status (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 
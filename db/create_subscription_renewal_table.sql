-- Table pour gérer les renouvellements d'abonnement
CREATE TABLE IF NOT EXISTS subscription_renewals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subscription_id INT NOT NULL,
    renewal_date DATETIME NOT NULL,
    expiry_date DATETIME NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 15000.00,
    payment_status ENUM('pending', 'completed', 'failed', 'expired') DEFAULT 'pending',
    payment_reference VARCHAR(100),
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    notification_sent BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
);

-- Ajout d'un index pour optimiser les recherches
CREATE INDEX idx_subscription_renewal_status ON subscription_renewals(payment_status, expiry_date);
CREATE INDEX idx_subscription_renewal_notification ON subscription_renewals(notification_sent, expiry_date);

-- Table pour les notifications d'abonnement
CREATE TABLE IF NOT EXISTS subscription_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subscription_id INT NOT NULL,
    type ENUM('expiry_warning', 'payment_failed', 'renewal_success', 'renewal_failed') NOT NULL,
    message TEXT NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME DEFAULT NULL,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
);

-- Ajout d'un index pour les notifications
CREATE INDEX idx_subscription_notification_type ON subscription_notifications(type, sent_at); 
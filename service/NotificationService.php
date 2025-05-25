<?php
class NotificationService {
    private $db;
    private $user_id;
    private $user_type;

    public function __construct($db, $user_id, $user_type) {
        $this->db = $db;
        $this->user_id = $user_id;
        $this->user_type = $user_type;
    }

    /**
     * Créer une nouvelle notification
     */
    public function create($title, $message, $user_id, $user_type, $type = 'info', $link = null) {
        $stmt = $this->db->prepare("
            INSERT INTO notifications 
            (user_id, user_type, title, message, type, link, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("sssssss", 
            $user_id,
            $user_type,
            $title,
            $message,
            $type,
            $link,
            $this->user_id
        );
        
        return $stmt->execute();
    }

    /**
     * Créer une notification pour plusieurs utilisateurs
     */
    public function createForMultipleUsers($title, $message, $user_ids, $user_type, $type = 'info', $link = null) {
        $values = [];
        $types = '';
        $params = [];
        
        foreach ($user_ids as $id) {
            $values[] = "(?, ?, ?, ?, ?, ?, ?)";
            $types .= "sssssss";
            $params[] = $id;
            $params[] = $user_type;
            $params[] = $title;
            $params[] = $message;
            $params[] = $type;
            $params[] = $link;
            $params[] = $this->user_id;
        }
        
        $sql = "INSERT INTO notifications (user_id, user_type, title, message, type, link, created_by) VALUES " . implode(", ", $values);
        $stmt = $this->db->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        return $stmt->execute();
    }

    /**
     * Récupérer les notifications non lues de l'utilisateur
     */
    public function getUnread() {
        $stmt = $this->db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND user_type = ? AND is_read = FALSE 
            ORDER BY created_at DESC
        ");
        
        $stmt->bind_param("ss", $this->user_id, $this->user_type);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Récupérer toutes les notifications de l'utilisateur
     */
    public function getAll($limit = 50, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND user_type = ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        
        $stmt->bind_param("ssii", $this->user_id, $this->user_type, $limit, $offset);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead($notification_id) {
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE id = ? AND user_id = ? AND user_type = ?
        ");
        
        $stmt->bind_param("iss", $notification_id, $this->user_id, $this->user_type);
        return $stmt->execute();
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead() {
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE user_id = ? AND user_type = ? AND is_read = FALSE
        ");
        
        $stmt->bind_param("ss", $this->user_id, $this->user_type);
        return $stmt->execute();
    }

    /**
     * Supprimer une notification
     */
    public function delete($notification_id) {
        $stmt = $this->db->prepare("
            DELETE FROM notifications 
            WHERE id = ? AND user_id = ? AND user_type = ?
        ");
        
        $stmt->bind_param("iss", $notification_id, $this->user_id, $this->user_type);
        return $stmt->execute();
    }

    /**
     * Compter les notifications non lues
     */
    public function countUnread() {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? AND user_type = ? AND is_read = FALSE
        ");
        
        $stmt->bind_param("ss", $this->user_id, $this->user_type);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'];
    }

    /**
     * Créer une notification pour tous les utilisateurs d'un type spécifique
     */
    public function createForAllUsersOfType($title, $message, $user_type, $type = 'info', $link = null) {
        // Récupérer tous les IDs des utilisateurs du type spécifié
        $table = $user_type . 's'; // admin -> admins, teacher -> teachers, student -> students
        $stmt = $this->db->prepare("SELECT id FROM $table");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $user_ids = [];
        while ($row = $result->fetch_assoc()) {
            $user_ids[] = $row['id'];
        }
        
        return $this->createForMultipleUsers($title, $message, $user_ids, $user_type, $type, $link);
    }
} 
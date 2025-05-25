<?php
class AuthService {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
    }
    
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public function getCurrentUserType() {
        return $_SESSION['user_type'] ?? null;
    }
    
    public function getCurrentUserName() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $userType = $this->getCurrentUserType();
        $userId = $this->getCurrentUserId();
        
        switch ($userType) {
            case 'admin':
                $stmt = $this->db->prepare("SELECT name FROM admin WHERE id = ?");
                break;
            case 'teacher':
                $stmt = $this->db->prepare("SELECT name FROM teachers WHERE id = ?");
                break;
            case 'student':
                $stmt = $this->db->prepare("SELECT name FROM students WHERE id = ?");
                break;
            default:
                return null;
        }
        
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                return $row['name'];
            }
        }
        
        return null;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /gestion/login.php');
            exit;
        }
    }
    
    public function requireUserType($type) {
        $this->requireLogin();
        if ($this->getCurrentUserType() !== $type) {
            header('Location: /gestion/login.php');
            exit;
        }
    }
} 
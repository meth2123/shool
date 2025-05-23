<?php
function logAdminAction($link, $admin_id, $action_type, $affected_table, $affected_id, $details = null) {
    $sql = "INSERT INTO admin_actions (admin_id, action_type, affected_table, affected_id, action_details, action_date) 
            VALUES (?, ?, ?, ?, ?, NOW())";
            
    $stmt = $link->prepare($sql);
    $stmt->bind_param("sssss", $admin_id, $action_type, $affected_table, $affected_id, $details);
    return $stmt->execute();
}

function createTableIfNotExists($link) {
    $sql = "CREATE TABLE IF NOT EXISTS admin_actions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id VARCHAR(20) NOT NULL,
        action_type ENUM('CREATE', 'UPDATE', 'DELETE') NOT NULL,
        affected_table VARCHAR(50) NOT NULL,
        affected_id VARCHAR(20) NOT NULL,
        action_details TEXT,
        action_date DATETIME NOT NULL,
        FOREIGN KEY (admin_id) REFERENCES admin(id)
    )";
    
    return $link->query($sql);
}

// Fonction pour obtenir l'historique des actions d'un admin
function getAdminActionHistory($link, $admin_id = null, $limit = 100) {
    $sql = "SELECT 
                aa.*,
                a.name as admin_name
            FROM admin_actions aa
            JOIN admin a ON aa.admin_id = a.id ";
    
    if ($admin_id) {
        $sql .= "WHERE aa.admin_id = ? ";
    }
    
    $sql .= "ORDER BY aa.action_date DESC LIMIT ?";
    
    $stmt = $link->prepare($sql);
    
    if ($admin_id) {
        $stmt->bind_param("si", $admin_id, $limit);
    } else {
        $stmt->bind_param("i", $limit);
    }
    
    $stmt->execute();
    return $stmt->get_result();
}

// Fonction pour formater les détails de l'action en JSON
function formatActionDetails($old_data = null, $new_data = null, $action_type = 'CREATE') {
    $details = [
        'action_type' => $action_type,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($old_data) {
        $details['old_data'] = $old_data;
    }
    
    if ($new_data) {
        $details['new_data'] = $new_data;
    }
    
    return json_encode($details);
}
?> 
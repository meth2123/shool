<?php
header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'services' => []
];

// Vérifier la connexion à la base de données
try {
    require_once 'service/db_utils.php';
    global $link;
    
    if ($link && $link->ping()) {
        $health['services']['database'] = [
            'status' => 'healthy',
            'message' => 'Connected to MySQL database'
        ];
    } else {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    $health['services']['database'] = [
        'status' => 'unhealthy',
        'message' => $e->getMessage()
    ];
    $health['status'] = 'unhealthy';
}

// Vérifier la configuration PayDunya
try {
    $paydunya_config = require 'service/paydunya_env.php';
    if ($paydunya_config['mode'] === 'live' && 
        !empty($paydunya_config['api_keys']['public_key']) && 
        !empty($paydunya_config['api_keys']['private_key'])) {
        $health['services']['paydunya'] = [
            'status' => 'healthy',
            'message' => 'PayDunya configuration is valid'
        ];
    } else {
        throw new Exception('Invalid PayDunya configuration');
    }
} catch (Exception $e) {
    $health['services']['paydunya'] = [
        'status' => 'unhealthy',
        'message' => $e->getMessage()
    ];
    $health['status'] = 'unhealthy';
}

// Vérifier l'accès au système de fichiers
try {
    $upload_dir = 'uploads';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    if (is_writable($upload_dir)) {
        $health['services']['filesystem'] = [
            'status' => 'healthy',
            'message' => 'Upload directory is writable'
        ];
    } else {
        throw new Exception('Upload directory is not writable');
    }
} catch (Exception $e) {
    $health['services']['filesystem'] = [
        'status' => 'unhealthy',
        'message' => $e->getMessage()
    ];
    $health['status'] = 'unhealthy';
}

// Retourner le statut HTTP approprié
if ($health['status'] === 'healthy') {
    http_response_code(200);
} else {
    http_response_code(503);
}

echo json_encode($health, JSON_PRETTY_PRINT); 
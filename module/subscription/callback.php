<?php
require_once __DIR__ . '/../../service/mysqlcon.php';
require_once __DIR__ . '/../../service/paydunya_service.php';

// Récupérer le payload
$payload = file_get_contents('php://input');

try {
    $paydunya = new PayDunyaService($link);
    $result = $paydunya->handleCallback($payload);
    
    if ($result) {
        http_response_code(200);
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(200);
        echo json_encode(['status' => 'ignored']);
    }
} catch (Exception $e) {
    error_log("Erreur callback PayDunya: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 
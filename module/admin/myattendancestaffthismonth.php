<?php
require_once('main.php');
require_once('../../db/config.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize response array
$response = [
    'error' => null,
    'records' => [],
    'debug' => []
];

try {
    // Log request data
    $response['debug']['post'] = $_POST;
    
    // Validate input
    if (!isset($_POST['id'])) {
        throw new Exception('Invalid staff ID');
    }

    $staff_id = $_POST['id'];
    $admin_id = $_SESSION['login_id'];

    // Log IDs
    $response['debug']['staff_id'] = $staff_id;
    $response['debug']['admin_id'] = $admin_id;

    // Initialize database connection
    $conn = getDbConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // First verify if the admin has access to this staff member's records
    $stmt = $conn->prepare("SELECT id FROM staff WHERE id = ? AND created_by = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare staff verification query: ' . $conn->error);
    }
    
    $stmt->bind_param("ss", $staff_id, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Access denied or staff member not found');
    }

    // Get present dates for the current month
    $query = "SELECT date, 'Present' as status 
              FROM attendance 
              WHERE attendedid = ? 
              AND created_by = ?
              AND MONTH(date) = MONTH(CURRENT_DATE()) 
              AND YEAR(date) = YEAR(CURRENT_DATE())
              ORDER BY date DESC";
              
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare attendance query: ' . $conn->error);
    }
    
    $stmt->bind_param("ss", $staff_id, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Log query info
    $response['debug']['query'] = $query;
    $response['debug']['rows_found'] = $result->num_rows;

    // Fetch all records
    while ($row = $result->fetch_assoc()) {
        $response['records'][] = [
            'date' => $row['date'],
            'status' => $row['status']
        ];
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    $response['debug']['error_details'] = $e->getTraceAsString();
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>

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
    error_log("Request received: " . print_r($_POST, true));
    $response['debug']['post'] = $_POST;
    
    // Validate input
    if (!isset($_POST['id'])) {
        throw new Exception('Invalid teacher ID');
    }

    $teacher_id = $_POST['id'];
    $admin_id = $_SESSION['login_id'];

    // Log IDs
    error_log("Teacher ID: " . $teacher_id . ", Admin ID: " . $admin_id);
    $response['debug']['teacher_id'] = $teacher_id;
    $response['debug']['admin_id'] = $admin_id;

    // Initialize database connection
    $conn = getDbConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // First verify if the admin has access to this teacher's records
    $stmt = $conn->prepare("SELECT id, name FROM teachers WHERE id = ? AND created_by = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare teacher verification query: ' . $conn->error);
    }
    
    $stmt->bind_param("ss", $teacher_id, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Access denied or teacher not found');
    }

    $teacher = $result->fetch_assoc();
    error_log("Teacher found: " . print_r($teacher, true));

    // Get absent dates for the current month
    $query = "SELECT date, 'Absent' as status 
              FROM teacher_absences 
              WHERE teacher_id = ? 
              AND created_by = ?
              AND MONTH(date) = MONTH(CURRENT_DATE()) 
              AND YEAR(date) = YEAR(CURRENT_DATE())
              ORDER BY date DESC";
              
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare absence query: ' . $conn->error);
    }
    
    $stmt->bind_param("ss", $teacher_id, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Log query info
    error_log("Query executed: " . $query);
    error_log("Number of records found: " . $result->num_rows);
    $response['debug']['query'] = $query;
    $response['debug']['rows_found'] = $result->num_rows;

    // Fetch all records
    while ($row = $result->fetch_assoc()) {
        $response['records'][] = [
            'date' => $row['date'],
            'status' => $row['status']
        ];
    }

    error_log("Final response: " . print_r($response, true));

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("Error occurred: " . $e->getMessage());
    $response['error'] = $e->getMessage();
    $response['debug']['error_details'] = $e->getTraceAsString();
}

// Send JSON response with proper headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
echo json_encode($response);
?>

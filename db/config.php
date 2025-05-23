<?php
// Database configuration
define('DB_HOST', 'localhost');     // Database host
define('DB_USER', 'root');         // Database user
define('DB_PASSWORD', '');         // Database password
define('DB_NAME', 'gestion');     // Database name

// Session configuration
if (!isset($_SESSION)) {
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone
date_default_timezone_set('UTC');

// Character encoding
ini_set('default_charset', 'UTF-8');

// Database connection function
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Common utility functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Session utility functions
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function getUserId() {
    return $_SESSION['login_id'] ?? null;
}

function getAdminId() {
    return $_SESSION['admin_id'] ?? 0;
}

// Constants for attendance status
define('STATUS_PRESENT', 'Present');
define('STATUS_ABSENT', 'Absent');
?> 
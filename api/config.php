<?php
/**
 * Database Configuration
 * Note Application - Online Notebook
 */

// Database Configuration
// For Hostinger: Use localhost as database host
define('DB_HOST', 'localhost'); // Hostinger default
define('DB_USER', 'u757840095_note2');
define('DB_PASS', 'MB?EM6aTa7&M');
define('DB_NAME', 'u757840095_note');
define('DB_PORT', 3306); // Standard MySQL port

// Application Configuration
define('APP_NAME', 'Note - Online Notebook');
define('APP_URL', 'https://note.websweos.com'); // Change to your domain
define('APP_VERSION', '1.0.0');
define('ENVIRONMENT', 'production'); // Set to 'development' for debugging

// Security Configuration
define('SESSION_TIMEOUT', 3600);
define('MAX_NOTE_LENGTH', 50000);
define('MAX_TITLE_LENGTH', 500);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt']);
define('MAX_FILE_SIZE', 5242880); // 5MB

// Error Reporting
// Production: Disable error display, log to file
if (ENVIRONMENT === 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php-errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

// Session Configuration for Hostinger
ini_set('session.save_path', __DIR__ . '/../tmp/sessions');
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_secure', 1); // HTTPS only
ini_set('session.cookie_httponly', 1); // No JavaScript access
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection

// CORS Configuration
header('Access-Control-Allow-Origin: ' . APP_URL);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Database Connection Function
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        if ($conn->connect_error) {
            throw new Exception('Connection failed: ' . $conn->connect_error);
        }
        
        $conn->set_charset('utf8mb4');
        return $conn;
    } catch (Exception $e) {
        die(json_encode(['error' => 'Database connection error: ' . $e->getMessage()]));
    }
}

// Response Handler
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Error Handler
function handleError($message, $statusCode = 400) {
    sendResponse(['error' => $message, 'status' => 'error'], $statusCode);
}

// Success Handler
function handleSuccess($data, $message = 'Success', $statusCode = 200) {
    sendResponse([
        'status' => 'success',
        'message' => $message,
        'data' => $data
    ], $statusCode);
}

// Utility: Sanitize Input
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Utility: Generate Unique ID
function generateId() {
    return uniqid('note_', true);
}

// Utility: Get Current Timestamp
function getCurrentTimestamp() {
    return date('Y-m-d H:i:s');
}

// Session Management
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Authentication Check
function isAuthenticated() {
    startSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get Current User
function getCurrentUser() {
    startSession();
    return $_SESSION['user_id'] ?? null;
}

// Set User Session
function setUserSession($userId, $userName, $email) {
    startSession();
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $userName;
    $_SESSION['user_email'] = $email;
    $_SESSION['login_time'] = time();
}

// Destroy Session
function destroyUserSession() {
    startSession();
    session_destroy();
    unset($_SESSION);
}

?>

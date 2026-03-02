<?php
/**
 * Note App - Complete API Handler
 * Simplified and production-ready for Hostinger
 * 
 * Visit: https://note.websweos.com/api/ for status
 */

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api-errors.log');

// Ensure logs directory exists
@mkdir(__DIR__ . '/../logs', 0755, true);
@mkdir(__DIR__ . '/../tmp/sessions', 0755, true);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'u757840095_note2');
define('DB_PASS', 'MB?EM6aTa7&M');
define('DB_NAME', 'u757840095_note');

// Enable session handling
@ini_set('session.save_path', __DIR__ . '/../tmp/sessions');
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Global database connection
$conn = null;

/**
 * Connect to database
 */
function dbConnect() {
    global $conn;
    
    if ($conn) return $conn;
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        apiError('Database connection failed: ' . $conn->connect_error, 503);
    }
    
    $conn->set_charset('utf8mb4');
    return $conn;
}

/**
 * Initialize database tables
 */
function initDB() {
    $db = dbConnect();
    
    // Users table
    $db->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(50) UNIQUE,
        username VARCHAR(100) UNIQUE,
        email VARCHAR(100) UNIQUE,
        password VARCHAR(255),
        full_name VARCHAR(150),
        bio TEXT,
        theme VARCHAR(20) DEFAULT 'light',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX(email),
        INDEX(username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Notes table
    $db->query("CREATE TABLE IF NOT EXISTS notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        note_id VARCHAR(50) UNIQUE,
        user_id INT,
        title VARCHAR(500),
        content LONGTEXT,
        color VARCHAR(20) DEFAULT '#FFFFFF',
        is_pinned INT DEFAULT 0,
        is_archived INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX(user_id),
        INDEX(is_pinned),
        INDEX(created_at),
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Labels table
    $db->query("CREATE TABLE IF NOT EXISTS labels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        label_id VARCHAR(50) UNIQUE,
        user_id INT,
        name VARCHAR(100),
        color VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_id),
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Initialize DB on first request
initDB();

/**
 * Send API response
 */
function apiResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

/**
 * Send API error
 */
function apiError($message, $status = 400) {
    apiResponse(['status' => 'error', 'error' => $message], $status);
}

/**
 * Send API success
 */
function apiSuccess($data, $message = 'Success') {
    apiResponse([
        'status' => 'success',
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * Get request endpoint
 */
function getEndpoint() {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path = str_replace('/api/', '', $path);
    return trim($path, '/');
}

/**
 * Get JSON input
 */
function getInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?: [];
}

/**
 * Sanitize string
 */
function sanitize($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate ID
 */
function generateId() {
    return uniqid('', true);
}

/**
 * Check auth
 */
function checkAuth() {
    if (empty($_SESSION['user_id']) || empty($_SESSION['user_name'])) {
        apiError('Unauthorized', 401);
    }
    return $_SESSION['user_id'];
}

// ===== ROUTE REQUESTS =====

$endpoint = getEndpoint();
$method = $_SERVER['REQUEST_METHOD'];
$input = getInput();

// Status check
if ($endpoint === '' || $endpoint === 'status') {
    apiSuccess(['status' => 'working', 'time' => date('Y-m-d H:i:s')]);
}

// ===== AUTH ENDPOINTS =====

// Register
elseif ($endpoint === 'auth/register' && $method === 'POST') {
    $username = sanitize($input['username'] ?? '');
    $email = sanitize($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $fulName = sanitize($input['full_name'] ?? 'User');
    
    if (!$username || !$email || !$password) {
        apiError('Missing required fields');
    }
    
    if (strlen($password) < 6) {
        apiError('Password must be at least 6 characters');
    }
    
    $db = dbConnect();
    
    // Check if exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        apiError('Email or username already exists', 409);
    }
    $stmt->close();
    
    // Create user
    $userId = generateId();
    $hashedPass = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO users (user_id, username, email, password, full_name) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $userId, $username, $email, $hashedPass, $fulName);
    
    if ($stmt->execute()) {
        $_SESSION['user_id'] = $db->insert_id;
        $_SESSION['user_name'] = $username;
        $_SESSION['user_email'] = $email;
        
        apiSuccess([
            'user_id' => $userId,
            'username' => $username,
            'email' => $email
        ], 'Registered successfully');
    } else {
        apiError('Registration failed');
    }
    $stmt->close();
}

// Login
elseif ($endpoint === 'auth/login' && $method === 'POST') {
    $email = sanitize($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    if (!$email || !$password) {
        apiError('Email and password required');
    }
    
    $db = dbConnect();
    $stmt = $db->prepare("SELECT id, user_id, username, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        apiError('Invalid credentials', 401);
    }
    
    $user = $result->fetch_assoc();
    
    if (!password_verify($password, $user['password'])) {
        apiError('Invalid credentials', 401);
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['username'];
    $_SESSION['user_email'] = $email;
    
    apiSuccess([
        'user_id' => $user['user_id'],
        'username' => $user['username'],
        'email' => $email
    ], 'Logged in successfully');
    
    $stmt->close();
}

// Logout
elseif ($endpoint === 'auth/logout' && $method === 'POST') {
    session_destroy();
    unset($_SESSION);
    apiSuccess([], 'Logged out successfully');
}

// ===== USER ENDPOINTS =====

// Get user
elseif ($endpoint === 'user' && $method === 'GET') {
    $userId = checkAuth();
    
    $db = dbConnect();
    $stmt = $db->prepare("SELECT user_id, username, email, full_name, bio, theme, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        apiSuccess($user);
    } else {
        apiError('User not found', 404);
    }
    $stmt->close();
}

// Update user
elseif ($endpoint === 'user' && $method === 'PUT') {
    $userId = checkAuth();
    
    $fullName = sanitize($input['full_name'] ?? '');
    $bio = sanitize($input['bio'] ?? '');
    $theme = sanitize($input['theme'] ?? 'light');
    
    $db = dbConnect();
    $stmt = $db->prepare("UPDATE users SET full_name = ?, bio = ?, theme = ? WHERE id = ?");
    $stmt->bind_param("sssi", $fullName, $bio, $theme, $userId);
    
    if ($stmt->execute()) {
        apiSuccess(['full_name' => $fullName, 'bio' => $bio, 'theme' => $theme]);
    } else {
        apiError('Update failed');
    }
    $stmt->close();
}

// ===== NOTES ENDPOINTS =====

// Get notes
elseif ($endpoint === 'notes' && $method === 'GET') {
    $userId = checkAuth();
    
    $db = dbConnect();
    $stmt = $db->prepare("SELECT note_id, title, content, color, is_pinned, is_archived, created_at FROM notes WHERE user_id = ? AND is_archived = 0 ORDER BY is_pinned DESC, created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notes = [];
    while ($row = $result->fetch_assoc()) {
        $notes[] = $row;
    }
    
    apiSuccess($notes);
    $stmt->close();
}

// Create note
elseif ($endpoint === 'notes' && $method === 'POST') {
    $userId = checkAuth();
    
    $title = sanitize($input['title'] ?? 'Untitled');
    $content = sanitize($input['content'] ?? '');
    
    $noteId = generateId();
    $db = dbConnect();
    
    $stmt = $db->prepare("INSERT INTO notes (note_id, user_id, title, content) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $noteId, $userId, $title, $content);
    
    if ($stmt->execute()) {
        apiSuccess(['note_id' => $noteId, 'title' => $title, 'content' => $content], 'Note created');
    } else {
        apiError('Failed to create note');
    }
    $stmt->close();
}

// Update note
elseif ($endpoint === 'notes' && $method === 'PUT') {
    $userId = checkAuth();
    
    $noteId = $input['note_id'] ?? '';
    $title = sanitize($input['title'] ?? '');
    $content = sanitize($input['content'] ?? '');
    $color = sanitize($input['color'] ?? '#FFFFFF');
    $isPinned = (int)($input['is_pinned'] ?? 0);
    
    $db = dbConnect();
    
    // Verify ownership
    $stmt = $db->prepare("SELECT id FROM notes WHERE note_id = ? AND user_id = ?");
    $stmt->bind_param("si", $noteId, $userId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        apiError('Note not found', 404);
    }
    $stmt->close();
    
    // Update note
    $stmt = $db->prepare("UPDATE notes SET title = ?, content = ?, color = ?, is_pinned = ? WHERE note_id = ?");
    $stmt->bind_param("sssss", $title, $content, $color, $isPinned, $noteId);
    
    if ($stmt->execute()) {
        apiSuccess(['note_id' => $noteId, 'title' => $title], 'Note updated');
    } else {
        apiError('Failed to update note');
    }
    $stmt->close();
}

// Delete note
elseif ($endpoint === 'notes' && $method === 'DELETE') {
    $userId = checkAuth();
    
    $noteId = $input['note_id'] ?? '';
    
    $db = dbConnect();
    
    // Verify ownership
    $stmt = $db->prepare("SELECT id FROM notes WHERE note_id = ? AND user_id = ?");
    $stmt->bind_param("si", $noteId, $userId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        apiError('Note not found', 404);
    }
    $stmt->close();
    
    // Delete note
    $stmt = $db->prepare("DELETE FROM notes WHERE note_id = ?");
    $stmt->bind_param("s", $noteId);
    
    if ($stmt->execute()) {
        apiSuccess([], 'Note deleted');
    } else {
        apiError('Failed to delete note');
    }
    $stmt->close();
}

// ===== LABELS ENDPOINTS =====

// Get labels
elseif ($endpoint === 'labels' && $method === 'GET') {
    $userId = checkAuth();
    
    $db = dbConnect();
    $stmt = $db->prepare("SELECT label_id, name, color FROM labels WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $labels = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row;
    }
    
    apiSuccess($labels);
    $stmt->close();
}

// Create label
elseif ($endpoint === 'labels' && $method === 'POST') {
    $userId = checkAuth();
    
    $name = sanitize($input['name'] ?? '');
    $color = sanitize($input['color'] ?? '#808080');
    
    if (!$name) {
        apiError('Label name required');
    }
    
    $labelId = generateId();
    $db = dbConnect();
    
    $stmt = $db->prepare("INSERT INTO labels (label_id, user_id, name, color) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $labelId, $userId, $name, $color);
    
    if ($stmt->execute()) {
        apiSuccess(['label_id' => $labelId, 'name' => $name, 'color' => $color]);
    } else {
        apiError('Failed to create label');
    }
    $stmt->close();
}

// ===== SEARCH ENDPOINT =====

elseif ($endpoint === 'search' && $method === 'GET') {
    $userId = checkAuth();
    
    $q = sanitize($_GET['q'] ?? '');
    
    if (!$q) {
        apiSuccess([]);
    }
    
    $db = dbConnect();
    $searchTerm = "%$q%";
    
    $stmt = $db->prepare("SELECT note_id, title, content, color, created_at FROM notes WHERE user_id = ? AND (title LIKE ? OR content LIKE ?)");
    $stmt->bind_param("iss", $userId, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notes = [];
    while ($row = $result->fetch_assoc()) {
        $notes[] = $row;
    }
    
    apiSuccess($notes);
    $stmt->close();
}

// ===== SHARE ENDPOINT =====

elseif ($endpoint === 'share' && $method === 'POST') {
    $userId = checkAuth();
    
    $noteId = $input['note_id'] ?? '';
    
    $db = dbConnect();
    
    // Verify ownership
    $stmt = $db->prepare("SELECT id FROM notes WHERE note_id = ? AND user_id = ?");
    $stmt->bind_param("si", $noteId, $userId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        apiError('Note not found', 404);
    }
    $stmt->close();
    
    // Generate share URL
    $shareUrl = 'https://note.websweos.com/share/' . $noteId;
    
    apiSuccess(['share_url' => $shareUrl]);
}

// ===== 404 =====

else {
    apiError('Endpoint not found', 404);
}

?>

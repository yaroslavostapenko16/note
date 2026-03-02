<?php
/**
 * Main API Handler
 * Note Application - RESTful API
 */

require_once 'config.php';
require_once 'database.php';

class NoteAPI {
    private $conn;
    private $method;
    private $endpoint;
    private $userId;
    
    public function __construct() {
        $this->conn = getDBConnection();
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->endpoint = $this->getEndpoint();
        $this->userId = $this->authenticateUser();
    }
    
    /**
     * Get API Endpoint
     */
    private function getEndpoint() {
        // First check if endpoint is passed as query parameter (from .htaccess rewrite)
        if (isset($_GET['endpoint']) && !empty($_GET['endpoint'])) {
            return trim($_GET['endpoint'], '/');
        }
        
        // Fallback to parse REQUEST_URI
        $request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $request = str_replace('/api/', '', $request);
        return trim($request, '/');
    }
    
    /**
     * Authenticate User via Session or Token
     */
    private function authenticateUser() {
        startSession();
        
        // Check session
        if (isset($_SESSION['user_id'])) {
            return $_SESSION['user_id'];
        }
        
        // Check Authorization header
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
            // Validate token here (implement JWT or custom token validation)
            // For now, return null for public endpoints
        }
        
        return null;
    }
    
    /**
     * Route API Requests
     */
    public function route() {
        $parts = explode('/', $this->endpoint);
        $action = $parts[0] ?? '';
        
        switch ($action) {
            case 'auth':
                $this->handleAuth();
                break;
            case 'notes':
                $this->handleNotes();
                break;
            case 'labels':
                $this->handleLabels();
                break;
            case 'search':
                $this->handleSearch();
                break;
            case 'user':
                $this->handleUser();
                break;
            case 'share':
                $this->handleShare();
                break;
            default:
                handleError('Endpoint not found', 404);
        }
    }
    
    /**
     * Handle Authentication
     */
    private function handleAuth() {
        if ($this->method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            switch ($this->endpoint) {
                case 'auth/register':
                    $this->register($data);
                    break;
                case 'auth/login':
                    $this->login($data);
                    break;
                case 'auth/logout':
                    $this->logout();
                    break;
                default:
                    handleError('Invalid auth endpoint', 404);
            }
        } else {
            handleError('Method not allowed', 405);
        }
    }
    
    /**
     * User Registration
     */
    private function register($data) {
        $username = sanitizeInput($data['username'] ?? '');
        $email = sanitizeInput($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $fullName = sanitizeInput($data['full_name'] ?? '');
        
        if (!$username || !$email || !$password) {
            handleError('Missing required fields', 400);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleError('Invalid email format', 400);
        }
        
        if (strlen($password) < 6) {
            handleError('Password must be at least 6 characters', 400);
        }
        
        // Check if user exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            handleError('Email or username already exists', 409);
        }
        $stmt->close();
        
        // Create user
        $userId = generateId();
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $this->conn->prepare(
            "INSERT INTO users (user_id, username, email, password, full_name) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssss", $userId, $username, $email, $hashedPassword, $fullName);
        
        if ($stmt->execute()) {
            setUserSession($this->conn->insert_id, $username, $email);
            handleSuccess([
                'user_id' => $userId,
                'username' => $username,
                'email' => $email
            ], 'User registered successfully', 201);
        } else {
            handleError('Registration failed', 500);
        }
        $stmt->close();
    }
    
    /**
     * User Login
     */
    private function login($data) {
        $email = sanitizeInput($data['email'] ?? '');
        $password = $data['password'] ?? '';
        
        if (!$email || !$password) {
            handleError('Email and password required', 400);
        }
        
        $stmt = $this->conn->prepare("SELECT id, user_id, username, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            handleError('Invalid credentials', 401);
        }
        
        $user = $result->fetch_assoc();
        
        if (!password_verify($password, $user['password'])) {
            handleError('Invalid credentials', 401);
        }
        
        // Update last login
        $now = getCurrentTimestamp();
        $updateStmt = $this->conn->prepare("UPDATE users SET last_login = ? WHERE id = ?");
        $updateStmt->bind_param("si", $now, $user['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        setUserSession($user['id'], $user['username'], $email);
        
        handleSuccess([
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'email' => $email
        ], 'Logged in successfully');
        
        $stmt->close();
    }
    
    /**
     * User Logout
     */
    private function logout() {
        destroyUserSession();
        handleSuccess([], 'Logged out successfully');
    }
    
    /**
     * Handle Notes Operations
     */
    private function handleNotes() {
        if (!$this->userId) {
            handleError('Unauthorized', 401);
        }
        
        switch ($this->method) {
            case 'GET':
                $this->getNotes();
                break;
            case 'POST':
                $this->createNote();
                break;
            case 'PUT':
                $this->updateNote();
                break;
            case 'DELETE':
                $this->deleteNote();
                break;
            default:
                handleError('Method not allowed', 405);
        }
    }
    
    /**
     * Get All Notes
     */
    private function getNotes() {
        $filters = [];
        $params = [];
        
        // Base query
        $query = "SELECT id, note_id, title, content, color, is_pinned, is_archived, created_at, updated_at FROM notes WHERE user_id = ?";
        $params[] = $this->userId;
        
        // Filter by status
        if (isset($_GET['archived']) && $_GET['archived'] === 'true') {
            $query .= " AND is_archived = 1";
        } else {
            $query .= " AND is_archived = 0";
        }
        
        // Exclude deleted notes
        $query .= " AND is_deleted = 0";
        
        // Sort
        $sortBy = sanitizeInput($_GET['sort'] ?? 'created_at');
        $sortOrder = (sanitizeInput($_GET['order'] ?? 'DESC') === 'ASC') ? 'ASC' : 'DESC';
        
        if ($sortBy === 'pinned') {
            $query .= " ORDER BY is_pinned DESC, created_at DESC";
        } else {
            $query .= " ORDER BY $sortBy $sortOrder";
        }
        
        // Pagination
        $limit = min((int)($_GET['limit'] ?? 50), 100);
        $offset = (int)($_GET['offset'] ?? 0);
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->conn->prepare($query);
        $types = str_repeat('i', count($params) - 2) . 'ii';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notes = [];
        while ($row = $result->fetch_assoc()) {
            $notes[] = $row;
        }
        
        handleSuccess($notes, 'Notes retrieved successfully');
        $stmt->close();
    }
    
    /**
     * Create Note
     */
    private function createNote() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $title = sanitizeInput($data['title'] ?? '');
        $content = sanitizeInput($data['content'] ?? '');
        $color = sanitizeInput($data['color'] ?? '#FFFFFF');
        $noteId = generateId();
        $now = getCurrentTimestamp();
        
        if (strlen($title) > MAX_TITLE_LENGTH) {
            handleError('Title exceeds maximum length', 400);
        }
        
        if (strlen($content) > MAX_NOTE_LENGTH) {
            handleError('Content exceeds maximum length', 400);
        }
        
        $stmt = $this->conn->prepare(
            "INSERT INTO notes (note_id, user_id, title, content, color, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sisssss", $noteId, $this->userId, $title, $content, $color, $now, $now);
        
        if ($stmt->execute()) {
            logActivity($this->conn, $this->userId, 'note_created', 'note', $this->conn->insert_id);
            handleSuccess(['note_id' => $noteId], 'Note created successfully', 201);
        } else {
            handleError('Failed to create note', 500);
        }
        $stmt->close();
    }
    
    /**
     * Update Note
     */
    private function updateNote() {
        $data = json_decode(file_get_contents('php://input'), true);
        $noteId = sanitizeInput($data['note_id'] ?? '');
        
        if (!$noteId) {
            handleError('Note ID required', 400);
        }
        
        // Verify ownership
        $stmt = $this->conn->prepare("SELECT id FROM notes WHERE note_id = ? AND user_id = ?");
        $stmt->bind_param("si", $noteId, $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        if ($result->num_rows === 0) {
            handleError('Note not found or unauthorized', 404);
        }
        
        $updateQuery = "UPDATE notes SET ";
        $updates = [];
        $params = [];
        $types = '';
        
        if (isset($data['title'])) {
            $title = sanitizeInput($data['title']);
            $updates[] = "title = ?";
            $params[] = $title;
            $types .= 's';
        }
        
        if (isset($data['content'])) {
            $content = sanitizeInput($data['content']);
            $updates[] = "content = ?";
            $params[] = $content;
            $types .= 's';
        }
        
        if (isset($data['color'])) {
            $color = sanitizeInput($data['color']);
            $updates[] = "color = ?";
            $params[] = $color;
            $types .= 's';
        }
        
        if (isset($data['is_pinned'])) {
            $isPinned = (int)$data['is_pinned'];
            $updates[] = "is_pinned = ?";
            $params[] = $isPinned;
            $types .= 'i';
        }
        
        if (isset($data['is_archived'])) {
            $isArchived = (int)$data['is_archived'];
            $updates[] = "is_archived = ?";
            $params[] = $isArchived;
            $types .= 'i';
        }
        
        if (empty($updates)) {
            handleError('No fields to update', 400);
        }
        
        $updates[] = "updated_at = ?";
        $now = getCurrentTimestamp();
        $params[] = $now;
        $types .= 's';
        
        $updateQuery .= implode(', ', $updates);
        $updateQuery .= " WHERE note_id = ? AND user_id = ?";
        $params[] = $noteId;
        $params[] = $this->userId;
        $types .= 'si';
        
        $stmt = $this->conn->prepare($updateQuery);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            logActivity($this->conn, $this->userId, 'note_updated', 'note', null);
            handleSuccess([], 'Note updated successfully');
        } else {
            handleError('Failed to update note', 500);
        }
        $stmt->close();
    }
    
    /**
     * Delete Note (Soft Delete)
     */
    private function deleteNote() {
        $data = json_decode(file_get_contents('php://input'), true);
        $noteId = sanitizeInput($data['note_id'] ?? '');
        
        if (!$noteId) {
            handleError('Note ID required', 400);
        }
        
        $now = getCurrentTimestamp();
        $stmt = $this->conn->prepare(
            "UPDATE notes SET is_deleted = 1, deleted_at = ? WHERE note_id = ? AND user_id = ?"
        );
        $stmt->bind_param("ssi", $now, $noteId, $this->userId);
        
        if ($stmt->execute()) {
            logActivity($this->conn, $this->userId, 'note_deleted', 'note', null);
            handleSuccess([], 'Note deleted successfully');
        } else {
            handleError('Failed to delete note', 500);
        }
        $stmt->close();
    }
    
    /**
     * Handle Labels Operations
     */
    private function handleLabels() {
        if (!$this->userId) {
            handleError('Unauthorized', 401);
        }
        
        switch ($this->method) {
            case 'GET':
                $this->getLabels();
                break;
            case 'POST':
                $this->createLabel();
                break;
            case 'DELETE':
                $this->deleteLabel();
                break;
            default:
                handleError('Method not allowed', 405);
        }
    }
    
    /**
     * Get All Labels
     */
    private function getLabels() {
        $stmt = $this->conn->prepare(
            "SELECT label_id, name, color FROM labels WHERE user_id = ? ORDER BY name ASC"
        );
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $labels = [];
        while ($row = $result->fetch_assoc()) {
            $labels[] = $row;
        }
        
        handleSuccess($labels);
        $stmt->close();
    }
    
    /**
     * Create Label
     */
    private function createLabel() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $name = sanitizeInput($data['name'] ?? '');
        $color = sanitizeInput($data['color'] ?? '#808080');
        $labelId = generateId();
        
        if (!$name) {
            handleError('Label name required', 400);
        }
        
        $stmt = $this->conn->prepare(
            "INSERT INTO labels (label_id, user_id, name, color) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("siss", $labelId, $this->userId, $name, $color);
        
        if ($stmt->execute()) {
            handleSuccess(['label_id' => $labelId], 'Label created successfully', 201);
        } else {
            handleError('Failed to create label', 500);
        }
        $stmt->close();
    }
    
    /**
     * Delete Label
     */
    private function deleteLabel() {
        $data = json_decode(file_get_contents('php://input'), true);
        $labelId = sanitizeInput($data['label_id'] ?? '');
        
        if (!$labelId) {
            handleError('Label ID required', 400);
        }
        
        $stmt = $this->conn->prepare("DELETE FROM labels WHERE label_id = ? AND user_id = ?");
        $stmt->bind_param("si", $labelId, $this->userId);
        
        if ($stmt->execute()) {
            handleSuccess([], 'Label deleted successfully');
        } else {
            handleError('Failed to delete label', 500);
        }
        $stmt->close();
    }
    
    /**
     * Handle Search
     */
    private function handleSearch() {
        if (!$this->userId) {
            handleError('Unauthorized', 401);
        }
        
        if ($this->method !== 'GET') {
            handleError('Method not allowed', 405);
        }
        
        $query = sanitizeInput($_GET['q'] ?? '');
        
        if (strlen($query) < 2) {
            handleError('Search query too short', 400);
        }
        
        $searchQuery = "%$query%";
        $stmt = $this->conn->prepare(
            "SELECT id, note_id, title, content, color, created_at FROM notes 
             WHERE user_id = ? AND is_deleted = 0 AND (title LIKE ? OR content LIKE ?)
             ORDER BY created_at DESC LIMIT 50"
        );
        $stmt->bind_param("iss", $this->userId, $searchQuery, $searchQuery);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notes = [];
        while ($row = $result->fetch_assoc()) {
            $notes[] = $row;
        }
        
        handleSuccess($notes, 'Search completed', 200);
        $stmt->close();
    }
    
    /**
     * Handle User Profile
     */
    private function handleUser() {
        if (!$this->userId) {
            handleError('Unauthorized', 401);
        }
        
        if ($this->method === 'GET') {
            $this->getUserProfile();
        } elseif ($this->method === 'PUT') {
            $this->updateUserProfile();
        } else {
            handleError('Method not allowed', 405);
        }
    }
    
    /**
     * Get User Profile
     */
    private function getUserProfile() {
        $stmt = $this->conn->prepare(
            "SELECT user_id, username, email, full_name, bio, theme, language, created_at FROM users WHERE id = ?"
        );
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            handleSuccess($row);
        } else {
            handleError('User not found', 404);
        }
        $stmt->close();
    }
    
    /**
     * Update User Profile
     */
    private function updateUserProfile() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $updates = [];
        $params = [];
        $types = '';
        
        if (isset($data['full_name'])) {
            $fullName = sanitizeInput($data['full_name']);
            $updates[] = "full_name = ?";
            $params[] = $fullName;
            $types .= 's';
        }
        
        if (isset($data['bio'])) {
            $bio = sanitizeInput($data['bio']);
            $updates[] = "bio = ?";
            $params[] = $bio;
            $types .= 's';
        }
        
        if (isset($data['theme'])) {
            $theme = sanitizeInput($data['theme']);
            if (in_array($theme, ['light', 'dark'])) {
                $updates[] = "theme = ?";
                $params[] = $theme;
                $types .= 's';
            }
        }
        
        if (empty($updates)) {
            handleError('No fields to update', 400);
        }
        
        $updates[] = "updated_at = ?";
        $now = getCurrentTimestamp();
        $params[] = $now;
        $types .= 's';
        
        $params[] = $this->userId;
        $types .= 'i';
        
        $updateQuery = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($updateQuery);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            handleSuccess([], 'Profile updated successfully');
        } else {
            handleError('Failed to update profile', 500);
        }
        $stmt->close();
    }
    
    /**
     * Handle Share Operations
     */
    private function handleShare() {
        if (!$this->userId) {
            handleError('Unauthorized', 401);
        }
        
        if ($this->method === 'POST') {
            $this->shareNote();
        } elseif ($this->method === 'GET') {
            $this->getSharedNotes();
        } else {
            handleError('Method not allowed', 405);
        }
    }
    
    /**
     * Share Note
     */
    private function shareNote() {
        $data = json_decode(file_get_contents('php://input'), true);
        $noteId = sanitizeInput($data['note_id'] ?? '');
        
        if (!$noteId) {
            handleError('Note ID required', 400);
        }
        
        $stmt = $this->conn->prepare("SELECT id FROM notes WHERE note_id = ? AND user_id = ?");
        $stmt->bind_param("si", $noteId, $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        if ($result->num_rows === 0) {
            handleError('Note not found', 404);
        }
        
        $note = $result->fetch_assoc();
        $shareId = generateId();
        $shareType = sanitizeInput($data['share_type'] ?? 'link');
        $canEdit = (int)($data['can_edit'] ?? 0);
        
        $stmt = $this->conn->prepare(
            "INSERT INTO shared_notes (share_id, note_id, shared_by, share_type, can_edit) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("siisi", $shareId, $note['id'], $this->userId, $shareType, $canEdit);
        
        if ($stmt->execute()) {
            handleSuccess([
                'share_id' => $shareId,
                'share_url' => APP_URL . '/share/' . $shareId
            ], 'Note shared successfully', 201);
        } else {
            handleError('Failed to share note', 500);
        }
        $stmt->close();
    }
    
    /**
     * Get Shared Notes
     */
    private function getSharedNotes() {
        $stmt = $this->conn->prepare(
            "SELECT sn.share_id, n.note_id, n.title, n.created_at FROM shared_notes sn 
             JOIN notes n ON sn.note_id = n.id 
             WHERE sn.shared_by = ? OR sn.shared_with = ?
             ORDER BY sn.created_at DESC"
        );
        $stmt->bind_param("ii", $this->userId, $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $shares = [];
        while ($row = $result->fetch_assoc()) {
            $shares[] = $row;
        }
        
        handleSuccess($shares);
        $stmt->close();
    }
    
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

/**
 * Log User Activity
 */
function logActivity($conn, $userId, $action, $entityType, $entityId) {
    $logId = generateId();
    $now = getCurrentTimestamp();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare(
        "INSERT INTO activity_logs (log_id, user_id, action, entity_type, entity_id, ip_address, user_agent, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sississs", $logId, $userId, $action, $entityType, $entityId, $ipAddress, $userAgent, $now);
    $stmt->execute();
    $stmt->close();
}

// Initialize Database on first request
$db = new Database();
if (!$db->checkTablesExist()) {
    $db->initDatabase();
}
$db->closeConnection();

// Route API Request
$api = new NoteAPI();
$api->route();

?>

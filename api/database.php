<?php
/**
 * Database Schema - Auto-initialized by api.php
 * 
 * This file is deprecated. All database initialization
 * now happens automatically in api.php on first request.
 * 
 * No manual database setup is needed - tables are created
 * and configured automatically.
 */

// Database is auto-initialized in api.php
// Nothing to do here

?>

        try {
            // Users Table
            $userTable = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(50) UNIQUE NOT NULL,
                username VARCHAR(100) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(150),
                profile_pic VARCHAR(255),
                bio TEXT,
                theme VARCHAR(20) DEFAULT 'light',
                language VARCHAR(10) DEFAULT 'en',
                notifications_enabled BOOLEAN DEFAULT 1,
                two_factor_enabled BOOLEAN DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                last_login DATETIME,
                is_active BOOLEAN DEFAULT 1,
                INDEX(email),
                INDEX(username),
                INDEX(created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            if (!$this->conn->query($userTable)) {
                throw new Exception('Error creating users table: ' . $this->conn->error);
            }
            
            // Notes Table
            $notesTable = "CREATE TABLE IF NOT EXISTS notes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                note_id VARCHAR(50) UNIQUE NOT NULL,
                user_id INT NOT NULL,
                title VARCHAR(500) NOT NULL,
                content LONGTEXT NOT NULL,
                color VARCHAR(20) DEFAULT '#FFFFFF',
                is_pinned BOOLEAN DEFAULT 0,
                is_archived BOOLEAN DEFAULT 0,
                is_deleted BOOLEAN DEFAULT 0,
                is_public BOOLEAN DEFAULT 0,
                views_count INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME,
                INDEX(user_id),
                INDEX(note_id),
                INDEX(is_pinned),
                INDEX(is_archived),
                INDEX(created_at),
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            if (!$this->conn->query($notesTable)) {
                throw new Exception('Error creating notes table: ' . $this->conn->error);
            }
            
            // Labels/Tags Table
            $labelsTable = "CREATE TABLE IF NOT EXISTS labels (
                id INT AUTO_INCREMENT PRIMARY KEY,
                label_id VARCHAR(50) UNIQUE NOT NULL,
                user_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                color VARCHAR(20),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX(user_id),
                INDEX(name),
                UNIQUE KEY(user_id, name),
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            if (!$this->conn->query($labelsTable)) {
                throw new Exception('Error creating labels table: ' . $this->conn->error);
            }
            
            // Note Labels Junction Table
            $noteLabelsTable = "CREATE TABLE IF NOT EXISTS note_labels (
                id INT AUTO_INCREMENT PRIMARY KEY,
                note_id INT NOT NULL,
                label_id INT NOT NULL,
                added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY(note_id, label_id),
                FOREIGN KEY(note_id) REFERENCES notes(id) ON DELETE CASCADE,
                FOREIGN KEY(label_id) REFERENCES labels(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            if (!$this->conn->query($noteLabelsTable)) {
                throw new Exception('Error creating note_labels table: ' . $this->conn->error);
            }
            
            // Attachments Table
            $attachmentsTable = "CREATE TABLE IF NOT EXISTS attachments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                attachment_id VARCHAR(50) UNIQUE NOT NULL,
                note_id INT NOT NULL,
                file_name VARCHAR(255) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                file_type VARCHAR(50),
                file_size INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX(note_id),
                FOREIGN KEY(note_id) REFERENCES notes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            if (!$this->conn->query($attachmentsTable)) {
                throw new Exception('Error creating attachments table: ' . $this->conn->error);
            }
            
            // Shared Notes Table
            $sharedNotesTable = "CREATE TABLE IF NOT EXISTS shared_notes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                share_id VARCHAR(50) UNIQUE NOT NULL,
                note_id INT NOT NULL,
                shared_by INT NOT NULL,
                shared_with INT,
                share_type ENUM('link', 'user', 'public') DEFAULT 'link',
                can_edit BOOLEAN DEFAULT 0,
                can_delete BOOLEAN DEFAULT 0,
                expires_at DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX(note_id),
                INDEX(share_id),
                INDEX(shared_by),
                FOREIGN KEY(note_id) REFERENCES notes(id) ON DELETE CASCADE,
                FOREIGN KEY(shared_by) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY(shared_with) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            if (!$this->conn->query($sharedNotesTable)) {
                throw new Exception('Error creating shared_notes table: ' . $this->conn->error);
            }
            
            // Activity Log Table
            $activityLogTable = "CREATE TABLE IF NOT EXISTS activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                log_id VARCHAR(50) UNIQUE NOT NULL,
                user_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                entity_type VARCHAR(50),
                entity_id INT,
                details JSON,
                ip_address VARCHAR(45),
                user_agent VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX(user_id),
                INDEX(action),
                INDEX(created_at),
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            if (!$this->conn->query($activityLogTable)) {
                throw new Exception('Error creating activity_logs table: ' . $this->conn->error);
            }
            
            // API Keys Table
            $apiKeysTable = "CREATE TABLE IF NOT EXISTS api_keys (
                id INT AUTO_INCREMENT PRIMARY KEY,
                key_id VARCHAR(50) UNIQUE NOT NULL,
                user_id INT NOT NULL,
                api_key VARCHAR(255) UNIQUE NOT NULL,
                api_secret VARCHAR(255) NOT NULL,
                name VARCHAR(100),
                last_used DATETIME,
                is_active BOOLEAN DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX(user_id),
                INDEX(api_key),
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            if (!$this->conn->query($apiKeysTable)) {
                throw new Exception('Error creating api_keys table: ' . $this->conn->error);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Database initialization error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Drop all tables (for development)
     */
    public function dropAllTables() {
        $tables = ['note_labels', 'attachments', 'shared_notes', 'activity_logs', 'api_keys', 'labels', 'notes', 'users'];
        
        foreach ($tables as $table) {
            $this->conn->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    /**
     * Check if tables exist
     */
    public function checkTablesExist() {
        $result = $this->conn->query("SHOW TABLES LIKE 'users'");
        return $result && $result->num_rows > 0;
    }
    
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Initialize database on first run
if (php_sapi_name() === 'cli') {
    $db = new Database();
    if (!$db->checkTablesExist()) {
        if ($db->initDatabase()) {
            echo "Database initialized successfully\n";
        } else {
            echo "Database initialization failed\n";
        }
    } else {
        echo "Database tables already exist\n";
    }
    $db->closeConnection();
}

?>

<?php
/**
 * Diagnostic Test Endpoint
 * Check if application is working properly
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test 1: Database Connection
echo "=== Diagnostic Report ===\n\n";

echo "1. PHP Version: " . phpversion() . "\n";
echo "2. Session Settings:\n";
echo "   - Session save path: " . ini_get('session.save_path') . "\n";
echo "   - Session name: " . session_name() . "\n";

// Test 2: Check if directories exist
$dirs = ['../logs', '../tmp', '../tmp/sessions', '../uploads'];
echo "\n3. Directory Check:\n";
foreach ($dirs as $dir) {
    $exists = is_dir($dir);
    $writable = is_writable($dir);
    echo "   - $dir: " . ($exists ? "EXISTS" : "MISSING") . " " . ($writable ? "(writable)" : "(NOT WRITABLE)") . "\n";
}

// Test 3: Database Connection
echo "\n4. Database Connection:\n";
require_once 'config.php';

try {
    $conn = new mysqli('localhost', 'u757840095_note2', 'MB?EM6aTa7&M', 'u757840095_note');
    
    if ($conn->connect_error) {
        echo "   - ERROR: " . $conn->connect_error . "\n";
    } else {
        echo "   - SUCCESS: Connected\n";
        
        // Check tables
        $result = $conn->query("SHOW TABLES");
        echo "   - Tables: " . $result->num_rows . " found\n";
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "   - EXCEPTION: " . $e->getMessage() . "\n";
}

// Test 4: API File Check
echo "\n5. API Files:\n";
$files = ['api.php', 'config.php', 'database.php', '.htaccess'];
foreach ($files as $file) {
    echo "   - $file: " . (file_exists($file) ? "EXISTS" : "MISSING") . "\n";
}

// Test 5: Session Test
echo "\n6. Session Test:\n";
@session_start();
$_SESSION['test'] = 'working';
echo "   - Session ID: " . session_id() . "\n";
echo "   - Session test: " . ($_SESSION['test'] ?? "FAILED") . "\n";

echo "\n=== End Diagnostic ===\n";
?>

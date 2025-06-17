<?php
// Set secure session parameters before starting the session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit();
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    require_once __DIR__ . '/../config/database.php';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, name, email, created_at FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting current user: " . $e->getMessage());
        return null;
    }
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Set session security parameters
function setSessionSecurity() {
    // Regenerate session ID periodically
    if (!isset($_SESSION['last_regeneration']) || 
        time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }

    // Set session timeout
    if (isset($_SESSION['last_activity']) && 
        time() - $_SESSION['last_activity'] > 1800) { // 30 minutes
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }
    $_SESSION['last_activity'] = time();
}

// Call session security function
setSessionSecurity();
?>
<?php
// Security helpers for CSRF protection and input validation

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('CSRF token validation failed');
    }
    return true;
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCSRFToken()) . '">';
}

// Input validation helpers
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    // At least 8 chars, 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 8 
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password);
}

function validateDate($date) {
    return (bool)strtotime($date);
}

function validateFileUpload($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return 'No file uploaded or upload failed';
    }

    // Check file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        return 'File too large (max 5MB)';
    }

    // Check file type
    $allowed = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($type, $allowed)) {
        return 'Invalid file type (PDF or DOC/DOCX only)';
    }

    return true;
}

// XSS prevention (use instead of htmlspecialchars)
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Safe redirect
function safeRedirect($url) {
    $allowed = [
        '/JobPortal/index.php',
        '/JobPortal/admin.php',
        '/JobPortal/login.php',
        '/JobPortal/register.php'
    ];
    
    if (!in_array($url, $allowed)) {
        $url = '/JobPortal/index.php';
    }
    
    header('Location: ' . $url);
    exit;
}

// Rate limiting (simple)
function checkRateLimit($key, $limit = 5, $window = 300) {
    $file = sys_get_temp_dir() . '/ratelimit_' . md5($key);
    $current = @file_get_contents($file) ?: '0:0';
    list($count, $timestamp) = explode(':', $current);
    
    if (time() - $timestamp > $window) {
        $count = 1;
        $timestamp = time();
    } else {
        $count++;
    }
    
    file_put_contents($file, $count . ':' . $timestamp);
    return $count <= $limit;
}
<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie if it exists
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_unset();
session_destroy();

// Set a success message in a temporary cookie
setcookie('logout_message', 'You have been successfully logged out.', time() + 5, '/');

// Redirect to login page
header('Location: /JobPortal/');
exit;

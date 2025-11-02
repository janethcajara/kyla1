<?php
require_once __DIR__ . '/config.php';

function connectDB() {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        // Respect optional DB_PORT if defined in config
        $portPart = defined('DB_PORT') && DB_PORT ? (";port=" . intval(DB_PORT)) : '';
        $dsn = "mysql:host=" . DB_HOST . $portPart . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Ensure logs directory exists
        $logDir = __DIR__ . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($logDir)) mkdir($logDir, 0777, true);
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'db_errors.log';
        $msg = date('Y-m-d H:i:s') . " - DB connection error: " . $e->getMessage() . "\n";
        // Append the error (do not expose exceptions in production)
        @file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);

        http_response_code(500);
        if (defined('DEBUG') && DEBUG) {
            // In development, return the exception message to help debugging
            echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
        } else {
            echo json_encode(['error' => 'Database connection failed']);
        }
        exit;
    }
}

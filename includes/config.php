<?php
// DB credentials - update these for your local XAMPP setup
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'jobportal');
define('DB_USER', 'root');
define('DB_PASS', '');
// Optional DB port (XAMPP/MySQL default is 3306)
define('DB_PORT', 3306);

// Enable verbose debug output locally. Set to false in production.
define('DEBUG', true);

define('UPLOAD_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);

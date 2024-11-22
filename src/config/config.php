<?php

// Session configuration
ini_set('session.gc_maxlifetime', 1800);
session_set_cookie_params(1800);

// Start the session
session_start();

$timeout_duration = 1800;  // seconds


// Check if the session is new or not
if (isset($_SESSION['LAST_ACTIVITY'])) {
    // Calculate the session's lifetime
    if ((time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        // Unset $_SESSION variable for the run-time
        session_unset();
        // Destroy the session data on the server
        session_destroy();
    }
}

// Update last activity time stamp
$_SESSION['LAST_ACTIVITY'] = time();

// Environment-based configuration
define('SITE_LINK', getenv('SITE_LINK') ?: 'http://localhost/');
define('ASSET_VERSION', '2.0');
define('FOLDER_PERMISSION', 0755);
define('ENVIRONMENT', getenv('ENVIRONMENT') ?: 'DEVELOPMENT');
define('DEBUG', filter_var(getenv('DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN));

// Database configuration
define('SERVER_NAME', getenv('MYSQL_HOST') ?: 'sonic-db');
define('USERNAME', getenv('MYSQL_USER') ?: 'sonic');
define('PASSWORD', getenv('MYSQL_PASSWORD'));
define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'sonic');

// Include functions
require_once dirname(__DIR__) . '/public/functions.php';

?>
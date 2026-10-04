<?php
/**
 * Golden Power Security — Global Configuration
 * Edit the constants below to match your hosting environment.
 */

// ---- Database ------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_PORT', '3307');
define('DB_NAME', 'gps_website');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---- Paths -----------------------------------------------------------------
// BASE_URL must point at the public site's root (no trailing slash).
define('BASE_URL', '/myproject-updated/myproject');
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');

// ---- Security --------------------------------------------------------------
// Session cookie name is namespaced so the public site and admin panel
// never accidentally share a login session.
define('ADMIN_SESSION_NAME', 'gps_admin_session');

// Show PHP errors while developing; set to 0 in production.
define('APP_DEBUG', 1);
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

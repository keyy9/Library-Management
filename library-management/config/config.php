<?php
// Database configuration for XAMPP MySQL
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'library_management');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('SITE_NAME', 'Library Management System');
// Change this to your XAMPP URL path
define('BASE_URL', 'http://localhost/library-management/public');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define paths for assets and pages
define('PUBLIC_URL', BASE_URL);
define('ASSETS_URL', BASE_URL . '/assets');

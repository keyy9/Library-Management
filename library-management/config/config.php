<?php
// Database configuration
define('DB_TYPE', 'sqlite');
define('DB_PATH', __DIR__ . '/../database.sqlite');

// Application configuration
define('SITE_NAME', 'Library Management System');
define('BASE_URL', 'http://localhost:8000');

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

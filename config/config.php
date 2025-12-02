<?php
// Configuration file for SmartShop POS System

// Environment detection
define('IS_PRODUCTION', isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'infinityfreeapp.com') !== false);

// Base URL configuration
if (IS_PRODUCTION) {
    define('BASE_URL', 'https://smartshop.infinityfreeapp.com/');
    define('APP_URL', BASE_URL . 'app/');
} else {
    define('BASE_URL', 'http://localhost/SMESHOP/');
    define('APP_URL', BASE_URL . 'app/');
}

// Path configurations
define('ROOT_PATH', __DIR__ . '/../');
define('APP_PATH', ROOT_PATH . 'app/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('PUBLIC_PATH', ROOT_PATH . 'public/');

// Database configuration (handled in Database class)

// Error reporting
if (IS_PRODUCTION) {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Session configuration removed to prevent warnings

// Timezone
date_default_timezone_set('Africa/Kigali');
?>
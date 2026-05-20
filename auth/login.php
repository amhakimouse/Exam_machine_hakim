<?php
/**
 * EventHub Pro — auth/login.php
 * Direct physical script compatibility wrapper
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Boot vendor autoloading and environment configurations
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Define BASE_PATH if not already defined (relative to public directory)
if (!defined('BASE_PATH')) {
    // When accessing auth/login.php, BASE_PATH should point to root, 
    // but the app is served via front controller in public/ or directly from root.
    // Let's compute it robustly.
    $script = $_SERVER['SCRIPT_NAME'];
    $dir = dirname(dirname($script));
    define('BASE_PATH', rtrim($dir, '/'));
}

// Dispatch to the main AuthController
use App\Controllers\AuthController;

$controller = new AuthController();
$controller->login();

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Response.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/ApiKeyValidator.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Global error handler
set_error_handler(function ($severity, $message, $file, $line) {
    Response::send(
        'error',
        'Internal server error. Please try again later.',
        500,
        "PHP Error: $message in $file on line $line"
    );
});

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'GET'])) {
    Response::send('error', 'Method Not Allowed. Use POST or GET.', 405);
}

// API Key validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::check($clientApiKey);

// DB operations
try {
    $db = new Database();
    $result = $db->query("DESC answers");  // just an example
} catch (Exception $e) {
    Response::send('error',  'Database operation failed. See server logs.', 500, $e->getMessage());
}

Response::send('success', 'Database connection successful');

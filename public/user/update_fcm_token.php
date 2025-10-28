<?php


require __DIR__ . '/../../vendor/autoload.php';

use Application\UseCases\UserUseCase;
use Infrastructure\Persistence\MySQLUserRepository;
use Helpers\ApiKeyValidator;
use Helpers\Response;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['UPDATE'])) {
    Response::send('error', 'Method not allowed. Use UPDATE', 405);
}

$headers = getallheaders();

// API Key validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::check($clientApiKey);

// Expected JSON structure
// {
// user_id: String,
// fcm_token: String nullable,
// }

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    Response::send('error', '無効なJSONデータです。', 400);
}

$user_id = $input['user_id'] ?? null;
$fcm_token = $input['fcm_token'] ?? null;


if (!isset($user_id)) {
    Response::send('error', 'ユーザーIDは必須です。', 400);
}

if ($user_id == null || !is_string($user_id)) {
    Response::send('error', '無効なユーザーID形式です。', 400);
}

if (!isset($fcm_token)) {
    Response::send('error', 'Fcm Tokenは必須です。', 400);
}


try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $userRepository = new MySQLUserRepository($connection);
    $userUseCase = new UserUseCase($userRepository);
    $userUseCase->updateFcmToken($user_id, $fcm_token);
    Response::send('success', 'FCMトークンの更新が成功しました。', 200);
} catch (Throwable $e) {
    Response::send('error', $e->getMessage(), 500);
}



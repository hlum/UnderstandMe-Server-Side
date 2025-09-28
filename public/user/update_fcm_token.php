<?php

use Application\UseCases\UserUseCase;
use Infrastructure\Persistence\MySQLUserRepository;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../src/Helpers/SafeRequire.php';
SafeRequire::requireFile(__DIR__ . '/../../config/config.php');
SafeRequire::requireFile(__DIR__ . '/../../src/Response.php');
SafeRequire::requireFile(__DIR__ . '/../../src/Helpers/ApiKeyValidator.php');
SafeRequire::requireFile(__DIR__ . '/../../src/Domain/Repositories/UserRepositoryInterface.php');
SafeRequire::requireFile(__DIR__ . '/../../src/Infrastructure/Persistence/MySQLUserRepository.php');
SafeRequire::requireFile(__DIR__ . '/../../src/Domain/Repositories/UserRepositoryInterface.php');
SafeRequire::requireFile(__DIR__ . '/../../src/Application/UseCases/UserUseCase.php');
SafeRequire::requireFile(__DIR__ . '/../../src/Domain/Entities/User.php');


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if(!in_array($_SERVER['REQUEST_METHOD'], ['UPDATE'])) {
    Response::send('error', 'Method not allowed. Use UPDATE', 405);
}

$headers = getallheaders();

// API Key validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::check($clientApiKey);

// Expected JSON structure
// {
    // id: String,
    // fcm_token: String nullable,
// }

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    Response::send('error', '無効なJSONデータです。', 400);
}

$id = $input['id'] ?? null;
$fcm_token = $input['fcm_token'] ?? null;

if (empty($id)) {
    Response::send('error', 'ユーザーIDは必須です。', 400);
}

if($id == null || !is_string($id)){
    Response::send('error', '無効なユーザーID形式です。', 400);
}

if($fcm_token == null) {
    Response::send('error', 'Fcm Tokenは必須です。', 400);
}


try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $userRepository = new MySQLUserRepository($connection);
    $userUseCase = new UserUseCase($userRepository);
    $userUseCase->updateFcmToken($id, $fcm_token);
    Response::send('success', 'FCMトークンの更新が成功しました。', 200);
} catch (Throwable $e) {
    Response::send('error',  $e->getMessage(), 500);
}



<?php


require __DIR__ . '/../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Application\UseCases\FCMTokenUseCase;
use Infrastructure\Persistence\MySQLFCMTokenRepository;
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

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST'])) {
    Response::send('error', 'Method not allowed. Use POST', 405);
}

$headers = getallheaders();

// API Key validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::check($clientApiKey);

// Expected JSON structure
// {
// user_id: String,
// device_id: String,
// device_type: String,
// fcm_token: String nullable,
// }

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    Response::send('error', '無効なJSONデータです。', 400);
}

$userID = $input['user_id'] ?? null;
$fcmToken = $input['fcm_token'] ?? null;
$deviceID = $input['device_id'] ?? null;
$deviceType = $input['device_type'] ?? null;

if (!isset($userID)) {
    Response::send('error', 'ユーザーIDは必須です。', 400);
}

if ($userID == null || !is_string($userID)) {
    Response::send('error', '無効なユーザーID形式です。', 400);
}

if (!isset($deviceID)) {
    Response::send('error', 'デバイスIDは必須です。', 400);
}

if (!isset($deviceType)) {
    Response::send('error', 'デバイスタイプは必須です。', 400);
}

if (!isset($fcmToken)) {
    Response::send('error', 'Fcm Tokenは必須です。', 400);
}


try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $userRepository = new MySQLUserRepository($connection);
    $fcmRepository = new MySQLFCMTokenRepository($connection);

    $fcmUseCase = new FCMTokenUseCase($fcmRepository, $userRepository);
    $fcmUseCase->registerOrUpdateFCMToken($userID, $deviceID, $deviceType, $fcmToken);

    Response::send('success', 'FCMトークンの更新が成功しました。', 200);

} catch(AppException $e) {
    Response::send('error', $e->getMessage(), $e->getStatusCode());
} catch (Throwable $e) {
    Response::send('error', $e->getMessage(), 500);
}



<?php


require __DIR__ . '/../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
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


try {

    if (!in_array($_SERVER['REQUEST_METHOD'], ['POST'])) {
        Response::send('fail', 'Method not allowed. Use POST', 405, null, 'validation_error');
    }

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
        throw new ValidationException('無効なJSONデータです。');
    }

    $userID = $input['user_id'] ?? null;
    $fcmToken = $input['fcm_token'] ?? null;
    $deviceID = $input['device_id'] ?? null;
    $deviceType = $input['device_type'] ?? null;

    if (!isset($userID)) {
        throw new ValidationException('ユーザーIDは必須です。');
    }

    if ($userID == null || !is_string($userID)) {
        throw new ValidationException('無効なユーザーID形式です。');
    }

    if (!isset($deviceID)) {
        throw new ValidationException('デバイスIDは必須です。');
    }

    if (!isset($deviceType)) {
        throw new ValidationException('デバイスタイプは必須です。');
    }

    if (!isset($fcmToken)) {
        throw new ValidationException('Fcm Tokenは必須です。');
    }

    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $userRepository = new MySQLUserRepository($connection);
    $fcmRepository = new MySQLFCMTokenRepository($connection);

    $fcmUseCase = new FCMTokenUseCase($fcmRepository, $userRepository);
    $fcmUseCase->registerOrUpdateFCMToken($userID, $deviceID, $deviceType, $fcmToken);

    Response::send('success', 'FCMトークンの更新が成功しました。', 200);

} catch(AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}



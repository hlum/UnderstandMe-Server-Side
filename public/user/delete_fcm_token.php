<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
use Application\UseCases\FCMTokenUseCase;
use Helpers\ApiKeyValidator;
use Helpers\Response;
use Infrastructure\Persistence\MySQLFCMTokenRepository;
use Infrastructure\Persistence\MySQLUserRepository;


require __DIR__ . '/../../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {

    if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE'])) {
        Response::send('fail', 'Method not allowed. Use DELETE', 405, null, 'validation_error');
    }


    // API Key validation
    $headers = getallheaders();
    $clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    $userID = ApiKeyValidator::check($clientApiKey);

    // Expected JSON structure
    // {
    // user_id: String,
    // device_id: String,
    // }

    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new ValidationException('無効なJSONデータです。');
    }

    $passedUserID = $input['user_id'] ?? null;
    $deviceID = $input['device_id'] ?? null;

    if (!isset($passedUserID)) {
        throw new ValidationException('ユーザーIDは必須です。');
    }

    if($userID != $passedUserID) {
        throw new ValidationException('トークンのユーザーIDと渡されたユーザーIDが一致しません。他のユーザーの情報を操作することはできません。');
    }

    if (!isset($deviceID)) {
        throw new ValidationException('デバイスIDは必須です。');
    }
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $fcmTokenRepository = new MySQLFCMTokenRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $fcmUseCase = new FCMTokenUseCase($fcmTokenRepository, $userRepository);

    $fcmUseCase->deleteFCMToken($passedUserID, $deviceID);

    Response::send('success', 'FCMトークンが正常に削除されました。', 200);
} catch (AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}


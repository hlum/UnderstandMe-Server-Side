<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


require __DIR__ . '/../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
use Infrastructure\Persistence\MySQLUserRepository;
use Application\UseCases\UserUseCase;
use Helpers\ApiKeyValidator;
use Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


try {
    if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE'])) {
        Response::send('fail', 'Method not allowed. Use DELETE', 405, null, 'validation_error');
    }
    // API KEY Validation
    $headers = getallheaders();
    $clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    $userID = ApiKeyValidator::check($clientApiKey);


   $passedUserID = $_GET['id'] ?? null;
    if (!isset($passedUserID)) {
        throw new ValidationException('ユーザーIDは必須です。');
    }

    if($userID != $passedUserID) {
        throw new ValidationException('トークンのユーザーIDと渡されたユーザーIDが一致しません。他のユーザーの情報を操作することはできません。');
    }

    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $userRepository = new MySQLUserRepository($connection);
    $userUseCase = new UserUseCase($userRepository);
} catch(AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
}catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}

try {
    $userUseCase->deleteUserByID($passedUserID);
    Response::send('success', 'ユーザーの取得に成功しました', 200, null);

} catch(AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}

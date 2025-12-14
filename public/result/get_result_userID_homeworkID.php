<?php

require __DIR__ . '/../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
use Application\UseCases\ResultUseCase;
use Helpers\ApiKeyValidator;
use Helpers\Response;
use Infrastructure\Persistence\MySQLResultRepository;


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET'])) {
    Response::send('fail', 'Method not allowed. Use GET', 405, null, 'validation_error');
}


$headers = getallheaders();
// API KEY Validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::check($clientApiKey);

$userID = $_GET['user_id'] ?? null;
$homeworkID = $_GET['homework_id'] ?? null;

if ($userID === null || $homeworkID === null) {
    throw new ValidationException('user_idとhomework_idは必須です。');
}

try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $resultRepository = new MySQLResultRepository($connection);
    $resultUseCase = new ResultUseCase($resultRepository);
    $result = $resultUseCase->fetchResultWithHomeworkIDAndUserID($homeworkID, $userID);
    if ($result === null) {
        Response::send('success', "指定されたユーザーIDと宿題IDの結果が見つかりません。", 200);
    }

    Response::send('success', '結果の取得に成功しました。', 200, $result);
} catch(AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}
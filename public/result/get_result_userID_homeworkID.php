<?php
// 学生用のapplicationからのリクエストのみ
require __DIR__ . '/../../vendor/autoload.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

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

try {


    if (!in_array($_SERVER['REQUEST_METHOD'], ['GET'])) {
        Response::send('fail', 'Method not allowed. Use GET', 405, null, 'validation_error');
    }

    // API KEY Validation
    $headers = getallheaders();
    $clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    $userID = ApiKeyValidator::check($clientApiKey);

    $passedUserID = $_GET['user_id'] ?? null;
    $homeworkID = $_GET['homework_id'] ?? null;

    if ($passedUserID === null || $homeworkID === null) {
        throw new ValidationException('user_idとhomework_idは必須です。');
    }

    if($userID != $passedUserID) {
        throw new ValidationException('トークンのユーザーIDと渡されたユーザーIDが一致しません。他のユーザーの情報を操作することはできません。');
    }

    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $resultRepository = new MySQLResultRepository($connection);
    $resultUseCase = new ResultUseCase($resultRepository);
    $resultFetched = $resultUseCase->fetchResultWithHomeworkIDAndUserID($homeworkID, $passedUserID);

    if ($resultFetched === null) {
        Response::send('success', "指定されたユーザーIDと宿題IDの結果が見つかりません。", 200, data: []);
    }

    $result[] = $resultFetched;

    Response::send('success', '結果の取得に成功しました。', 200, $result);
} catch(AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}
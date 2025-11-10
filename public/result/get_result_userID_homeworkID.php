<?php

require __DIR__ . '/../../vendor/autoload.php';

use App\Application\CustomExceptions\AppException;
use Application\UseCases\ResultUseCase;
use Helpers\ApiKeyValidator;
use Helpers\Response;
use Infrastructure\Persistence\MySQLResultRepository;


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET'])) {
    Response::send('error', 'Method not allowed. Use GET', 405);
}


$headers = getallheaders();
// API KEY Validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::check($clientApiKey);

$userID = $_GET['user_id'] ?? null;
$homeworkID = $_GET['homework_id'] ?? null;

if ($userID === null || $homeworkID === null) {
    Response::send('error', 'user_idとhomework_idは必須です。', 400);
}

try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $resultRepository = new MySQLResultRepository($connection);
    $resultUseCase = new ResultUseCase($resultRepository);
    $result = $resultUseCase->fetchResultWithHomeworkIDAndUserID($homeworkID, $userID);
    if ($result === null) {
        Response::send('success', "指定されたユーザーIDと宿題IDの結果が見つかりません。", 200);
    }

    Response::send('success', '結果の取得に成功しました。', 200, json_encode($result));
} catch(AppException $e) {
    Response::send('error', $e->getMessage(), $e->getStatusCode());
} catch (Throwable $e) {
    Response::send('error', $e->getMessage(), 500);
}
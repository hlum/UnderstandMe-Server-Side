<?php

require __DIR__ . '/../../vendor/autoload.php';
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
$year = $_GET['year'] ?? null;

if ($userID === null || $year === null) {
    Response::send('error', 'user_idとyearは必須です。', 400);
}

try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $resultRepository = new MySQLResultRepository($connection);
    $resultUseCase = new ResultUseCase($resultRepository);
    $results = $resultUseCase->fetchResultsByUserID($userID, $year);

    Response::send('success', '結果の取得に成功しました。', 200, json_encode($results));
} catch (Throwable $e) {
    Response::send('error', $e->getMessage(), 500);
}
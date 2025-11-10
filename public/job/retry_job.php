<?php

use App\Application\CustomExceptions\AppException;
use Application\UseCases\JobUseCase;
use Infrastructure\Persistence\MySQLJobRepository;
use Infrastructure\Persistence\MySQLProjectRepository;
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


require __DIR__ . '/../../vendor/autoload.php';

use Helpers\ApiKeyValidator;
use Helpers\Response;


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['PATCH'])) {
    Response::send('error', 'Method not allowed. Use PATCH', 405);
}


$headers = getallheaders();
// API KEY Validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::check($clientApiKey);

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    Response::send('error', '無効なJSONデータです。', 400);
}

$homeworkID = $input['homework_id'] ?? null;
$userID = $input['user_id'] ?? null;

if (!isset($homeworkID) || !isset($userID)) {
    Response::send('error', 'homework_id と user_id は必須です。', 400);
}


try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $jobRepo = new MySQLJobRepository($connection);
    $projectRepo = new MySQLProjectRepository($connection);
    $jobUseCase = new JobUseCase($jobRepo, $projectRepo);

    $jobUseCase->retryJob($homeworkID, $userID);

    Response::send('success', 'リトライが完了しました。', 200);

} catch(AppException $e) {
    Response::send('error', $e->getMessage(), $e->getStatusCode());
} catch (Throwable $e) {
    Response::send('error', 'リトライに失敗しました。', 500);
}
<?php

use Application\UseCases\JobUseCase;
use Infrastructure\Persistence\MySQLJobRepository;
use Infrastructure\Persistence\MySQLProjectRepository;
use Infrastructure\Persistence\MySQLUserRepository;
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


require __DIR__ . '/../../vendor/autoload.php';

use Helpers\ApiKeyValidator;
use Helpers\Response;
use Infrastructure\Persistence\MySQLQuestionsAndChoicesRepository;
use Application\UseCases\QuestionsAndChoicesUseCase;


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

$homeworkID = $_GET['homework_id'] ?? null;
$userID = $_GET['user_id'] ?? null;

if (!isset($homeworkID) || !isset($userID)) {
    Response::send('error', 'homework_id と user_id は必須です。', 400);
}


try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $userRepo = new MySQLUserRepository($connection);
    $jobRepo = new MySQLJobRepository($connection);
    $projectRepo = new MySQLProjectRepository($connection);
    $jobUseCase = new JobUseCase($jobRepository, $userRepo, $projectRepo);

    $jobUseCase->retryJob($homeworkID, $userID);
} catch (Throwable $e) {
    Response::send('error', 'リトライに失敗しました。', 500);
}
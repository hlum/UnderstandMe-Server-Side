<?php
// 提出したProjectとJobを削除し、Homeworkの提出を取り消す


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


require __DIR__ . '/../../vendor/autoload.php';
use Application\UseCases\JobUseCase;
use Application\UseCases\ProjectUseCase;
use Helpers\Response;
use Infrastructure\Persistence\MySQLHomeworkRepository;
use Infrastructure\Persistence\MySQLJobRepository;
use Infrastructure\Persistence\MySQLProjectRepository;
use Helpers\ApiKeyValidator;
use Infrastructure\Persistence\MySQLUserRepository;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::send('error', 'Method Not Allowed. Use DELETE', 405);
}


// API KEY Validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::check($clientApiKey);


$input = json_decode(file_get_contents('php://input'), true);
$userID = $_GET['user_id'] ?? $input['user_id'] ?? null;
$homeworkID = $_GET['homework_id'] ?? $input['homework_id'] ?? null;

if (!$userID || !$homeworkID) {
    Response::send('error', 'Missing parameters', 400);
}

try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $jobRepository = new MySQLJobRepository($connection);
    $projectRepository = new MySQLProjectRepository($connection);
    $homeworkRepository = new MySQLHomeworkRepository($connection);
    $userRepository = new MySQLUserRepository($connection);


    $jobUseCase = new JobUseCase($jobRepository, $projectRepository);
    $projectUseCase = new ProjectUseCase($projectRepository, $userRepository, $homeworkRepository);

    // Jobを先に削除する
    $jobUseCase->deleteByHomeworkID($homeworkID, $userID);


    // 次にProjectを削除する
    $projectUseCase->deleteByHomeworkID($homeworkID, $userID);
    Response::send('success', '提出された宿題を削除しました', 200);
} catch (Throwable $e) {
    Response::send('error', $e->getMessage(), 500);
}
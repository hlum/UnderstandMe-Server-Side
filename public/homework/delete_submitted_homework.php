<?php
// 提出したProjectとJobを削除し、Homeworkの提出を取り消す

require_once __DIR__ . '/../../vendor/autoload.php';
use Application\UseCases\JobUseCase;
use Application\UseCases\ProjectUseCase;
use Helpers\Response;
use Infrastructure\ExternalServices\GithubSnippetsRepo;
use Infrastructure\Persistence\MySQLHomeworkRepository;
use Infrastructure\Persistence\MySQLJobRepository;
use Infrastructure\Persistence\MySQLProjectRepository;
use Infrastructure\Persistence\MySQLUserRepository;


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE'])) {
    Response::send('error', 'Method Not Allowed. Use DELETE', 405);
}


$userID = $_GET['user_id'] ?? null;
$homeworkID = $_GET['homework_id'] ?? null;

if (!isset($userID) || !isset($homeworkID)) {
    Response::send('error', 'user_idとhomework_idは必須です。', 400);
}

try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $jobRepository = new MySQLJobRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $projectRepository = new MySQLProjectRepository($connection);
    $snippetsRepo = new GithubSnippetsRepo();
    $homeworkRepository = new MySQLHomeworkRepository($connection);


    $jobUseCase = new JobUseCase($jobRepository, $userRepository, $projectRepository);
    $projectUseCase = new ProjectUseCase($projectRepository, $snippetsRepo, $userRepository, $homeworkRepository);

    // Jobを先に削除する
    $jobUseCase->deleteByHomeworkID($homeworkID, $userID);


    // 次にProjectを削除する
    $projectUseCase->deleteByHomeworkID($homeworkID, $userID);

} catch (Throwable $e) {
    Response::send('error', $e->getMessage(), 500);
}
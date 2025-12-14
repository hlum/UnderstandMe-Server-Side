<?php

require __DIR__ . '/../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
use Helpers\ApiKeyValidator;
use Helpers\Response;
use Infrastructure\Persistence\MySQLProjectRepository;
use Infrastructure\Persistence\MySQLUserRepository;
use Infrastructure\ExternalServices\SnippetRepositoryImpl;
use Infrastructure\Persistence\MySQLHomeworkRepository;
use Application\UseCases\ProjectUseCase;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET'])) {
    Response::send('fail', 'Method not allowed. Use GET', 405, null, 'validation_error');
}


// API KEY Validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::check($clientApiKey);

/* Possible queries
by project_id
by homework_id
by user_id
*/
$project_id = $_GET['id'] ?? null;
$homework_id = $_GET['homework_id'] ?? null;
$user_id = $_GET['user_id'] ?? null;


try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    $projectRepository = new MySQLProjectRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $homeworkRepository = new MySQLHomeworkRepository($connection);

    $projectUseCase = new ProjectUseCase(
        $projectRepository,
        $userRepository,
        $homeworkRepository
    );

} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}


try {
    $projects = [];


    // Query parametersに基づいてプロジェクトを検索
    if (isset($project_id)) {
        // Project IDでの検索
        $project = $projectUseCase->findById($project_id);
        $projects[] = $project;
    } elseif (isset($homework_id, $user_id)) {
        // Homework IDでの検索
        $project = $projectUseCase->findByHomeworkId($homework_id, $user_id);
        if ($project !== null) {
            $projects[] = $project;
        }
    } elseif (isset($user_id)) {
        // User IDでの検索
        $projects[] = $projectUseCase->findByUserId($user_id);
    } else {
        // どのクエリパラメータも指定されていない場合はエラーを返す
        throw new ValidationException('少なくとも1つのクエリパラメータを指定する必要があります。');
    }

    // 検索結果をJSON形式で返す
    Response::send('success', 'projectの取得に成功しました。', 200, $projects);

} catch(AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}
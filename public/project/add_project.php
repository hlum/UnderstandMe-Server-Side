<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


use Helpers\RepoLinkValidator;
ignore_user_abort(true); // continue even if user closes the connection


require __DIR__ . '/../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\UnSupportedRepoURL;
use Application\CustomExceptions\ValidationException;
use Application\UseCases\JobUseCase;
use Helpers\Response;
use Helpers\ApiKeyValidator;
use Infrastructure\ExternalServices\OllamaQuestionGenerator;
use Infrastructure\Persistence\MySQLChoiceRepository;
use Infrastructure\Persistence\MySQLJobRepository;
use Infrastructure\Persistence\MySQLProjectRepository;
use Application\UseCases\ProjectUseCase;
use Domain\Entities\Project;
use Infrastructure\Persistence\MySQLQuestionRepository;
use Infrastructure\Persistence\MySQLUserRepository;
use Infrastructure\Persistence\MySQLHomeworkRepository;
use Domain\Entities\Job;
use Domain\Entities\Status;


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['PATCH'])) {
    Response::send('fail', 'Method not allowed. Use PATCH', 405, null, 'validation_error');
}


// API Key validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::check($clientApiKey);


/* Expected JSON structure
{
    user_id: String,
    homework_id: String,
    github_file_link: String
}
*/

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new ValidationException('無効なJSONデータです。');
    }

    $user_id = $input['user_id'] ?? null;
    $homework_id = $input['homework_id'] ?? null;
    $github_file_link = $input['github_file_link'] ?? null;

    if (!isset($user_id)) {
        throw new ValidationException('ユーザーIDは必須です。');
    }
    if ($user_id == null || !is_string($user_id)) {
        throw new ValidationException('無効なユーザーID形式です。');
    }

    if (!isset($homework_id)) {
        throw new ValidationException('課題IDは必須です。');
    }
    if ($homework_id == null || !is_string($homework_id)) {
        throw new ValidationException('無効な課題ID形式です。');
    }
    if (!isset($github_file_link)) {
        throw new ValidationException('GitHubファイルリンクは必須です。');
    }
    if ($github_file_link == null || !is_string($github_file_link) || !filter_var($github_file_link, FILTER_VALIDATE_URL)) {
        throw new UnSupportedRepoURL('無効なファイルリンク形式です。GithubまたはGoogle Driveのリンクを使用してください。');
    }



    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    $projectRepository = new MySQLProjectRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $homeworkRepository = new MySQLHomeworkRepository($connection);
    $jobRepository = new MySQLJobRepository($connection);
    $questionRepository = new MySQLQuestionRepository($connection);
    $choiceRepository = new MySQLChoiceRepository($connection);
    $questionGenerator = new OllamaQuestionGenerator();
    $repoLinkValidator = new RepoLinkValidator();

    // URLを検証し、適切なエラーをスルーする
    $repoLinkValidator->validate($github_file_link);

    $projectUseCase = new ProjectUseCase(
        $projectRepository,
        $userRepository,
        $homeworkRepository
    );

    $project = Project::createNew($user_id, $homework_id, $github_file_link);
    $projectUseCase->add($project);


    $jobUseCase = new JobUseCase($jobRepository, $projectRepository);

    $job = Job::createNew($project->id, Status::from('pending'));
    $jobUseCase->add($job);
    Response::send('success', 'プロジェクトが正常に追加され、ジョブがキューに登録されました。', 200);
} catch(AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}
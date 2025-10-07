<?php

require __DIR__ . '/../../vendor/autoload.php';
use Application\UseCases\JobUseCase;
use Application\UseCases\ProcessPendingJobsUseCase;
use Helpers\Response;
use Helpers\ApiKeyValidator;
use Infrastructure\ExternalServices\OllamaQuestionGenerator;
use Infrastructure\Persistence\MySQLChoiceRepository;
use Infrastructure\Persistence\MySQLJobRepository;
use Infrastructure\Persistence\MySQLProjectRepository;
use Infrastructure\ExternalServices\GithubSnippetsRepo;
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

if(!in_array($_SERVER['REQUEST_METHOD'], ['PATCH'])) {
    Response::send('error', 'Method not allowed. Use UPDATE', 405);
}

$headers = getallheaders();

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

$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    Response::send('error', '無効なJSONデータです。', 400);
}

$user_id = $input['user_id'] ?? null;
$homework_id = $input['homework_id'] ?? null;
$github_file_link = $input['github_file_link'] ?? null;

if (!isset($user_id)) {
    Response::send('error', 'ユーザーIDは必須です。', 400);
}
if($user_id == null || !is_string($user_id)){
    Response::send('error', '無効なユーザーID形式です。', 400);
}

if (!isset($homework_id)) {
    Response::send('error', '課題IDは必須です。', 400);
}
if($homework_id == null || !is_string($homework_id)){
    Response::send('error', '無効な課題ID形式です。', 400);
}
if (!isset($github_file_link)) {
    Response::send('error', 'GitHubファイルリンクは必須です。', 400);
}
if($github_file_link == null || !is_string($github_file_link) || !filter_var($github_file_link, FILTER_VALIDATE_URL)){
    Response::send('error', '無効なGitHubファイルリンク形式です。', 400);
}


try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    $projectRepository = new MySQLProjectRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $snippetsRepository = new GithubSnippetsRepo();
    $homeworkRepository = new MySQLHomeworkRepository($connection);
    $jobRepository = new MySQLJobRepository($connection);
    $questionRepository = new MySQLQuestionRepository($connection);
    $choiceRepository = new MySQLChoiceRepository($connection);
    $questionGenerator = new OllamaQuestionGenerator();


    $projectUseCase = new ProjectUseCase(
        $projectRepository,
        $snippetsRepository,
        $userRepository,
        $homeworkRepository
    );

    $project = Project::createNew($user_id, $homework_id, $github_file_link);
    $projectUseCase->add($project);


    $jobUseCase = new JobUseCase($jobRepository, $userRepository, $projectRepository);

    $job = Job::createNew($project->id, Status::from('pending'));
    $jobUseCase->add($job);

    

    $jobProcessor = new ProcessPendingJobsUseCase(
        $jobRepository,
        $questionRepository,
        $choiceRepository,
        $questionGenerator,
        $snippetsRepository,
        $projectRepository
    );


    $processingJobExist = count($jobUseCase->getJobsByStatus(Status::from('processing'))) > 0;

    if($processingJobExist) {
        Response::send('info', 'プロジェクトが追加されましたが、現在別のプロジェクトの問題生成処理中です。少々お待ちください。', 202);
    }

    try {
        $jobProcessor->process($job);
        $jobUseCase->updateStatus($job->id, Status::from('done'));
        // TODO : Userに問題生成が終了したことを知らせる。
    } catch (Throwable $e) {
        // Log the error but do not fail the entire request
        error_log("Jobの処理失敗 (Job ID {$job->id}): " . $e->getMessage());
    }


    Response::send('success', 'プロジェクトが正常に追加されました。問題が生成されました。', 200);

} catch (Throwable $e) {
    Response::send('error',  $e->getMessage(), 500);
}
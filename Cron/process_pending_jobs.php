<?php

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use Application\UseCases\FCMTokenUseCase;
use Application\UseCases\JobUseCase;
use Application\UseCases\NotificationUseCase;
use Application\UseCases\ProcessPendingJobsUseCase;
use Application\UseCases\ProjectUseCase;
use Domain\Entities\Job;
use Domain\Entities\Status;
use Helpers\NotificationHandler;
use Infrastructure\ExternalServices\GithubSnippetsRepo;
use Infrastructure\ExternalServices\OllamaQuestionGenerator;
use Infrastructure\Persistence\MySQLJobRepository;
use Infrastructure\Persistence\MySQLProjectRepository;
use Infrastructure\Persistence\MySQLQuestionRepository;
use Infrastructure\Persistence\MySQLUserRepository;
use Infrastructure\Persistence\MySQLChoiceRepository;
use Infrastructure\Persistence\MySQLFCMTokenRepository;
use Infrastructure\Persistence\MySQLHomeworkRepository;

// Initialize dependencies
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$jobRepository = new MySQLJobRepository($mysqli);
$questionRepository = new MySQLQuestionRepository($mysqli);
$choiceRepository = new MySQLChoiceRepository($mysqli);
$projectRepository = new MySQLProjectRepository($mysqli);
$userRepository = new MySQLUserRepository($mysqli);
$fcmTokenRepository = new MySQLFCMTokenRepository($mysqli);
$homeworkRepository = new MySQLHomeworkRepository($mysqli);

$questionGenerator = new OllamaQuestionGenerator();
$snippetsRepository = new GithubSnippetsRepo();
$notificationHandler = new NotificationHandler(FIREBASE_PROJECT_ID);

// Initialize use cases
$jobUseCase = new JobUseCase($jobRepository, $projectRepository);
$fcmTokenUseCase = new FCMTokenUseCase($fcmTokenRepository, $userRepository);
$notificationUseCase = new NotificationUseCase($notificationHandler, $fcmTokenUseCase);
$projectUseCase = new ProjectUseCase($projectRepository, $userRepository, $homeworkRepository);
$processPendingJobsUseCase = new ProcessPendingJobsUseCase(
    $jobRepository,
    $questionRepository,
    $choiceRepository,
    $questionGenerator,
    $snippetsRepository,
    $projectRepository
);

const MAX_RETRY_COUNT = 10;

function getNextJob(JobUseCase $jobUseCase): ?Job
{
    $failedJobs = $jobUseCase->getJobsByStatus(Status::from('failed'));
    if (!empty($failedJobs)) {
        echo "前回失敗したJobを再処理します。JobID: {$failedJobs[0]->id}\n";
        return $failedJobs[0];
    }

    $pendingJobs = $jobUseCase->getJobsByStatus(Status::from('pending'));
    if (empty($pendingJobs)) {
        echo "処理待ちのJobがありません。\n";
        return null;
    }

    echo "新しいJobを処理します。JobID: {$pendingJobs[0]->id}\n";
    return $pendingJobs[0];
}

function sendNotificationSafely(
    NotificationUseCase $notificationUseCase,
    ProjectUseCase $projectUseCase,
    Job $job,
    string $title,
    string $body
): array {
    try {
        $project = $projectUseCase->findById($job->projectID);
        $userID = $project->userID;
        
        echo "ユーザーに通知を送信します。UserID: {$userID}\n";
        
        return $notificationUseCase->sendNotification(
            $userID,
            $title,
            $body,
            $project->homeworkID
        );
    } catch (Throwable $e) {
        error_log("通知の送信に失敗しました: " . $e->getMessage());
        return [];
    }
}

function logFailedDevices(array $failedDeviceFCMTokens): void
{
    if (empty($failedDeviceFCMTokens)) {
        return;
    }
    
    echo "以下のデバイスへの通知送信に失敗しました:\n";
    foreach ($failedDeviceFCMTokens as $token) {
        echo "- {$token->deviceType}\n";
    }
}

// Main processing loop
$currentRetry = 0;

while ($currentRetry < MAX_RETRY_COUNT) {
    $job = getNextJob($jobUseCase);
    
    if ($job === null) {
        exit(0);
    }

    try {
        $processPendingJobsUseCase->process($job);
        
        echo "Jobの処理が完了しまし、問題生成されました。\n";
        
        $failedDevices = sendNotificationSafely(
            $notificationUseCase,
            $projectUseCase,
            $job,
            "問題生成完了のお知らせ",
            "あなたのプロジェクトの問題生成が完了しました。"
        );
        
        logFailedDevices($failedDevices);
        break;

    } catch (Throwable $e) {
        $currentRetry++;
        error_log("Jobの処理失敗 (Attempt $currentRetry): " . $e->getMessage());
        
        if ($currentRetry >= MAX_RETRY_COUNT) {
            error_log("リトライのカウントを超えました。JobID: {$job->id}. Marking as failed.");
            $jobRepository->updateStatus($job->id, Status::from('failed'));
            
            $failedDevices = sendNotificationSafely(
                $notificationUseCase,
                $projectUseCase,
                $job,
                "問題生成失敗のお知らせ",
                "あなたのプロジェクトの問題生成が失敗しました。再度お試しください。"
            );
            
            logFailedDevices($failedDevices);
            break;
        }
        
        echo "Retrying... ($currentRetry/" . MAX_RETRY_COUNT . ")\n";
        $jobRepository->updateStatus($job->id, Status::from('pending'));
        sleep(5);
    }
}
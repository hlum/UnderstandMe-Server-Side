<?php

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use Application\UseCases\ClassUseCase;
use Application\UseCases\FCMTokenUseCase;
use Application\UseCases\HomeworkUseCase;
use Application\UseCases\JobUseCase;
use Application\UseCases\NotificationUseCase;
use Application\UseCases\ProcessPendingJobsUseCase;
use Application\UseCases\ProjectUseCase;
use Application\UseCases\UserUseCase;
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
use Infrastructure\Persistence\MySQLClassRepository;
use Infrastructure\Persistence\MySQLStudentClassEnrollmentRepository;

// Initialize dependencies
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$jobRepository = new MySQLJobRepository($mysqli);
$questionRepository = new MySQLQuestionRepository($mysqli);
$choiceRepository = new MySQLChoiceRepository($mysqli);
$projectRepository = new MySQLProjectRepository($mysqli);
$userRepository = new MySQLUserRepository($mysqli);
$fcmTokenRepository = new MySQLFCMTokenRepository($mysqli);
$homeworkRepository = new MySQLHomeworkRepository($mysqli);
$classRepository = new MySQLClassRepository($mysqli);
$studentClassEnrollmentRepo = new MySQLStudentClassEnrollmentRepository($mysqli);

$questionGenerator = new OllamaQuestionGenerator();
$snippetsRepository = new GithubSnippetsRepo();
$notificationHandler = new NotificationHandler(FIREBASE_PROJECT_ID);

// Initialize use cases
$jobUseCase = new JobUseCase($jobRepository, $projectRepository);
$fcmTokenUseCase = new FCMTokenUseCase($fcmTokenRepository, $userRepository);
$classUseCase = new ClassUseCase($classRepository, $userRepository, $studentClassEnrollmentRepo);
$userUseCase = new UserUseCase($userRepository);
$homeworkUseCase = new HomeworkUseCase($homeworkRepository, $userRepository, $classRepository);
$projectUseCase = new ProjectUseCase($projectRepository, $userRepository, $homeworkRepository);
$notificationUseCase = new NotificationUseCase(
    $notificationHandler, 
    $fcmTokenUseCase,
    $classUseCase,
    $userUseCase,
    $homeworkUseCase,
    $projectUseCase
);
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
    Job $job,
    bool $success
): array {
    try {
        echo "通知を送信します。JobID: {$job->id}, Success: " . ($success ? 'true' : 'false') . "\n";
        
        return $notificationUseCase->notifyJobResult($job, $success);
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
            $job,
            true
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
                $job,
                false
            );
            
            logFailedDevices($failedDevices);
            break;
        }
        
        echo "Retrying... ($currentRetry/" . MAX_RETRY_COUNT . ")\n";
        $jobRepository->updateStatus($job->id, Status::from('pending'));
        sleep(5);
    }
}
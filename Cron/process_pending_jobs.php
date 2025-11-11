<?php

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use Application\UseCases\FCMTokenUseCase;
use Application\UseCases\JobUseCase;
use Application\UseCases\NotificationUseCase;
use Application\UseCases\ProcessPendingJobsUseCase;
use Application\UseCases\ProjectUseCase;
use Domain\Entities\Job;
use Infrastructure\ExternalServices\GithubSnippetsRepo;
use Infrastructure\ExternalServices\OllamaQuestionGenerator;
use Infrastructure\Persistence\MySQLJobRepository;
use Infrastructure\Persistence\MySQLProjectRepository;
use Infrastructure\Persistence\MySQLQuestionRepository;
use Infrastructure\Persistence\MySQLUserRepository;
use Infrastructure\Persistence\MySQLChoiceRepository;
use Domain\Entities\Status;
use Helpers\NotificationHandler;
use Infrastructure\Persistence\MySQLFCMTokenRepository;
use Infrastructure\Persistence\MySQLHomeworkRepository;

// Dependencies
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$jobRepository = new MySQLJobRepository($mysqli);
$questionRepository = new MySQLQuestionRepository($mysqli);
$choiceRepository = new MySQLChoiceRepository($mysqli);
$questionGenerator = new OllamaQuestionGenerator();
$snippetsRepository = new GithubSnippetsRepo();
$projectRepository = new MySQLProjectRepository($mysqli);

$jobUseCase = new JobUseCase($jobRepository, $projectRepository);

// Use Case
$processPendingJobsUseCase = new ProcessPendingJobsUseCase(
    $jobRepository,
    $questionRepository,
    $choiceRepository,
    $questionGenerator,
    $snippetsRepository,
    $projectRepository
);

$notificationHandler = new NotificationHandler(FIREBASE_PROJECT_ID);
$fcmTokenRepository = new MySQLFCMTokenRepository($mysqli);
$userRepository = new MySQLUserRepository($mysqli);
$fcmTokenUseCase = new FCMTokenUseCase($fcmTokenRepository, $userRepository);

$notificationUseCase = new NotificationUseCase($notificationHandler, $fcmTokenUseCase);
$homeworkRepository = new MySQLHomeworkRepository($mysqli);
$projectUseCase = new ProjectUseCase($projectRepository, $userRepository, $homeworkRepository);

$maxRetryCounts = 10;
$currentRetry = 0;



while ($currentRetry < $maxRetryCounts) {

    // Strict Type にするため、初期　
    $jobToProcess = Job::createNew(
        projectId: '',
        status: Status::from('pending')
    );

    try {
        $failedJobs = $jobUseCase->getJobsByStatus(Status::from('failed'));

        if (!empty($failedJobs)) {
            $jobToProcess = $failedJobs[0];
            echo "前回失敗したJobを再処理します。JobID: {$jobToProcess->id}\n";
        } else {
            $pendingJobs = $jobUseCase->getJobsByStatus(Status::from('pending'));
            if (empty($pendingJobs)) {
                echo "処理待ちのJobがありません。\n";
                exit(0);
            } else {
                echo "新しいJobを処理します。JobID: {$pendingJobs[0]->id}\n";
                $jobToProcess = $pendingJobs[0];
            }
        }

        $processPendingJobsUseCase->process($jobToProcess);

        echo "Jobの処理が完了しまし、問題生成されました。\n";

        // UserID を取得
        $project = $projectUseCase->findById($jobToProcess->projectID);
        $userID = $project->userID;

        // userに通知を送信
        echo "ユーザーに通知を送信します。UserID: {$userID}\n";

        $failedDeviceFCMTokens = $notificationUseCase->sendNotification(
            $userID,
            "問題生成完了のお知らせ",
            "あなたのプロジェクトの問題生成が完了しました。",
            $project->homeworkID
        );
        break;


    } catch (Throwable $e) {
        $currentRetry++;
        error_log("Jobの処理失敗 (Attempt $currentRetry): " . $e->getMessage());
        if ($currentRetry >= $maxRetryCounts) {
            error_log("リトライのカウントを超えました。JobID: {$jobToProcess->id}. Marking as failed.");
            $jobRepository->updateStatus($jobToProcess->id, Status::from('failed'));

            // Userに失敗したことを通知で知らせる
            $failedDeviceFCMTokens = $notificationUseCase->sendNotification(
                $userID,
                "問題生成失敗のお知らせ",
                "あなたのプロジェクトの問題生成が失敗しました。再度お試しください。",
                $project->homeworkID
            );

        } else {
            echo "Retrying... ($currentRetry/$maxRetryCounts)\n";
            $jobRepository->updateStatus($jobToProcess->id, Status::from('pending'));
            sleep(5); // Wait before retrying
        }
    } finally {
        // 失敗したデバイスのログを表示
        if (!empty($failedDeviceFCMTokens)) {
            echo "以下のデバイスへの通知送信に失敗しました:\n";
            foreach ($failedDeviceFCMTokens as $token) {
                echo "- {$token->deviceType}\n";
            }
        }
    }
}
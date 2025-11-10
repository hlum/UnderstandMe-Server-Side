<?php

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use Application\UseCases\JobUseCase;
use Application\UseCases\ProcessPendingJobsUseCase;
use Infrastructure\ExternalServices\GithubSnippetsRepo;
use Infrastructure\ExternalServices\OllamaQuestionGenerator;
use Infrastructure\Persistence\MySQLJobRepository;
use Infrastructure\Persistence\MySQLProjectRepository;
use Infrastructure\Persistence\MySQLQuestionRepository;
use Infrastructure\Persistence\MySQLUserRepository;
use Infrastructure\Persistence\MySQLChoiceRepository;
use Domain\Entities\Status;

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



$maxRetryCounts = 10;
$currentRetry = 0;

while ($currentRetry < $maxRetryCounts) {
    $jobToProcess = null;
    try {
        $failedJobs = $jobUseCase->getJobsByStatus(Status::from('failed'));

        if (!empty($failedJobs)) {
            $jobToProcess = $failedJobs[0];
        } else {
            $pendingJobs = $jobUseCase->getJobsByStatus(Status::from('pending'));
            if (empty($pendingJobs)) {
                echo "処理待ちのJobがありません。\n";
                exit(0);
            } else {
                $jobToProcess = $pendingJobs[0];
            }
        }

        $processPendingJobsUseCase->process($jobToProcess);

        echo "Jobの処理が完了しまし、問題生成されました。\n";
        // TODO : Userに問題生成が終了したことを知らせる。
        break;


    } catch (Throwable $e) {
        $currentRetry++;
        error_log("Jobの処理失敗 (Attempt $currentRetry): " . $e->getMessage());
        if ($currentRetry >= $maxRetryCounts) {
            error_log("リトライのカウントを超えました。JobID: {$jobToProcess->id}. Marking as failed.");
            $jobRepository->updateStatus($jobToProcess->id, Status::from('failed'));
            // TODO : Userに失敗したことを通知で知らせる
        } else {
            echo "Retrying... ($currentRetry/$maxRetryCounts)\n";
            $jobRepository->updateStatus($jobToProcess->id, Status::from('pending'));
            sleep(5); // Wait before retrying
        }
    }
}
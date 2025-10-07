<?php

namespace Application\UseCases;

use Domain\Entities\Job;
use Domain\Repositories\ChoiceRepositoryInterface;
use Domain\Repositories\JobRepositoryInterface;
use Domain\Repositories\ProjectRepositoryInterface;
use Domain\Repositories\QuestionGeneratorInterface;
use Domain\Repositories\QuestionRepositoryInterface;
use Domain\Repositories\SnippetsRepo;
use Domain\Repositories\UserRepositoryInterface;
use Domain\Entities\Status;


class ProcessPendingJobsUseCase {
    private JobRepositoryInterface $jobRepository;
    private UserRepositoryInterface $userRepository;
    private QuestionRepositoryInterface $questionRepository;
    private ChoiceRepositoryInterface $choiceRepository;
    private QuestionGeneratorInterface $questionGenerator;
    private SnippetsRepo $snippetsRepository;

    private ProjectRepositoryInterface $projectRepository;


    public function __construct(
        JobRepositoryInterface $jobRepository,
        UserRepositoryInterface $userRepository,
        QuestionRepositoryInterface $questionRepository,
        ChoiceRepositoryInterface $choiceRepository,
        QuestionGeneratorInterface $questionGenerator,
        SnippetsRepo $snippetsRepository,
        ProjectRepositoryInterface $projectRepository
    ) {
        $this->jobRepository = $jobRepository;
        $this->userRepository = $userRepository;
        $this->questionRepository = $questionRepository;
        $this->choiceRepository = $choiceRepository;
        $this->questionGenerator = $questionGenerator;
        $this->snippetsRepository = $snippetsRepository;
        $this->projectRepository = $projectRepository;
    }

    public function process(): void {
        if ($this->processingJobExists()) {
            throw new \Exception('処理中のJobがあるため少々お待ちください。');
        }

        $pendingJob = $this->getPendingJob();
        if (!isset($pendingJob)) {
            throw new \Exception('保留中のJobがありません。');
        }

        $this->jobRepository->updateStatus($pendingJob->id, Status::from('processing'));

        try {
            $project = $this->projectRepository->findById($pendingJob->projectId);
            $codeSnippet = $this->snippetsRepository->getRandomCodeSnippet(
                $project->githubFileLink,
                50
            );


            if (!isset($codeSnippet)) {
                throw new \Exception("コードの取得に失敗しました。");
            }

            $generatedQAndChoices = $this->questionGenerator->generateQuestions(
                $pendingJob->id,
                5,
                $codeSnippet
            );

            foreach ($generatedQAndChoices as $qAndChoices) {
                $this->questionRepository->insert($qAndChoices->question);
                foreach ($qAndChoices->choices as $choice) {
                    $this->choiceRepository->insert($choice);
                }
            }

            $this->jobRepository->updateStatus($pendingJob->id, Status::from('done'));

        } catch (\Exception $e) {
            $this->jobRepository->updateStatus($pendingJob->id, Status::from('failed'));
            throw $e;
        }
    }


    // 生成中のJobがあるかどうか
    private function processingJobExists(): bool {
        $processingJobs = $this->jobRepository->getJobsByStatus(Status::from('processing'),1);
        return !empty($processingJobs);
    }

    private function getPendingJob(): ?Job {
        $failedJob = $this->jobRepository->getJobsByStatus(Status::from('failed'),1);
        
        if (!empty($failedJob)) {
            return $failedJob[0];
        }

        $pendingJob = $this->jobRepository->getJobsByStatus(Status::from('pending'), 1);

        if(empty($pendingJob)) {
            return null;
        }

        return $pendingJob[0];
    }
}

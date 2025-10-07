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
    private QuestionRepositoryInterface $questionRepository;
    private ChoiceRepositoryInterface $choiceRepository;
    private QuestionGeneratorInterface $questionGenerator;
    private SnippetsRepo $snippetsRepository;

    private ProjectRepositoryInterface $projectRepository;


    public function __construct(
        JobRepositoryInterface $jobRepository,
        QuestionRepositoryInterface $questionRepository,
        ChoiceRepositoryInterface $choiceRepository,
        QuestionGeneratorInterface $questionGenerator,
        SnippetsRepo $snippetsRepository,
        ProjectRepositoryInterface $projectRepository
    ) {
        $this->jobRepository = $jobRepository;
        $this->questionRepository = $questionRepository;
        $this->choiceRepository = $choiceRepository;
        $this->questionGenerator = $questionGenerator;
        $this->snippetsRepository = $snippetsRepository;
        $this->projectRepository = $projectRepository;
    }

    public function process(Job $job) {
        $job = $this->jobRepository->findById($job->id);
    

        // If the job is already being processed, skip it
        if ($job->status == Status::from('processing')) {
            return;
        }

        $this->jobRepository->updateStatus($job->id, Status::from('processing'));

        try {
            $project = $this->projectRepository->findById($job->projectId);
            $codeSnippet = $this->snippetsRepository->getRandomCodeSnippet(
                $project->githubFileLink,
                50
            );


            if (!isset($codeSnippet)) {
                throw new \Exception("コードの取得に失敗しました。");
            }

            $generatedQAndChoices = $this->questionGenerator->generateQuestions(
                $job->id,
                5,
                $codeSnippet
            );

            foreach ($generatedQAndChoices as $qAndChoices) {
                $this->questionRepository->insert($qAndChoices->question);
                foreach ($qAndChoices->choices as $choice) {
                    $this->choiceRepository->insert($choice);
                }
            }

            $this->jobRepository->updateStatus($job->id, Status::from('done'));

        } catch (\Exception $e) {
            throw $e;
        }
    }
}

<?php

namespace Application\UseCases;

use Domain\Entities\Job;
use Domain\Repositories\ChoiceRepositoryInterface;
use Domain\Repositories\JobRepositoryInterface;
use Domain\Repositories\ProjectRepositoryInterface;
use Domain\Repositories\QuestionGeneratorInterface;
use Domain\Repositories\QuestionRepositoryInterface;
use Domain\Repositories\SnippetsRepo;
use Domain\Entities\Status;


class ProcessPendingJobsUseCase
{
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

    public function process(Job $job, int $snippetLineCount)
    {
        $job = $this->jobRepository->findById($job->id);


        // If the job is already being processed, skip it
        if ($job->status == Status::from('processing')) {
            return;
        }

        $this->jobRepository->updateStatus($job->id, Status::from('processing'));

        try {
            $project = $this->projectRepository->findById($job->projectID);
            $codeSnippet = $this->snippetsRepository->getRandomCodeSnippet(
                $project->githubFileLink,
                $snippetLineCount
            );


            if (!isset($codeSnippet)) {
                throw new \Exception("コードの取得に失敗しました。");
            }

            $generatedQAndChoices = $this->questionGenerator->generateQuestions(
                $job->id,
                5,
                $codeSnippet
            );

            if (!isset($generatedQAndChoices) || count($generatedQAndChoices) === 0) {
                throw new \Exception("問題の生成に失敗しました。");
            }

            foreach ($generatedQAndChoices as $qAndChoices) {
                $this->questionRepository->insert($qAndChoices->question);
                
                shuffle($qAndChoices->choices);

                $this->choiceRepository->insertBatch($qAndChoices->choices);
            }

            $this->jobRepository->updateStatus($job->id, Status::from('done'));

        } catch (\Exception $e) {
            throw $e;
        }
    }
}

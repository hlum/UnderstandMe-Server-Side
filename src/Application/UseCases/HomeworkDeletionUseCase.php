<?php

namespace Application\UseCases;
use Domain\Repositories\HomeworkRepositoryInterface;
use Domain\Repositories\JobRepositoryInterface;
use Domain\Repositories\ProjectRepositoryInterface;

class HomeworkDeletionUseCase
{
    private HomeworkRepositoryInterface $homeworkRepository;
    private ProjectRepositoryInterface $projectRepository;
    private JobRepositoryInterface $jobRepository;

    public function __construct(
        HomeworkRepositoryInterface $homeworkRepository,
        ProjectRepositoryInterface $projectRepository,
        JobRepositoryInterface $jobRepository
    ) {
        $this->homeworkRepository = $homeworkRepository;
        $this->projectRepository = $projectRepository;
        $this->jobRepository = $jobRepository;
    }

    public function execute(string $homeworkID, string $studentID): void
    {
        // Delete associated projects
        $this->projectRepository->deleteByHomeworkID($homeworkID, $studentID);

        // Delete associated jobs
        $this->jobRepository->deleteByHomeworkID($homeworkID, $studentID);

        // Finally, delete the homework
        $this->homeworkRepository->deleteById($homeworkID);
    }
}
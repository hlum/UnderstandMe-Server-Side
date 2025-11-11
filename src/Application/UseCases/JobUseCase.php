<?php

namespace Application\UseCases;
use Application\CustomExceptions\NotFoundException;
use Application\CustomExceptions\ValidationException;
use Domain\Entities\Job;
use Domain\Entities\Status;
use Domain\Repositories\JobRepositoryInterface;
use Domain\Repositories\ProjectRepositoryInterface;


class JobUseCase
{
    private JobRepositoryInterface $jobRepository;
    private ProjectRepositoryInterface $projectRepository;

    public function __construct(
        JobRepositoryInterface $jobRepository,
        ProjectRepositoryInterface $projectRepository
    ) {
        $this->jobRepository = $jobRepository;
        $this->projectRepository = $projectRepository;
    }


    public function add(Job $job)
    {
        $this->validateJob($job);
        $this->jobRepository->insert($job);
    }


    public function updateStatus(string $id, Status $status): void
    {
        $job = $this->jobRepository->findById($id);
        if ($job === null) {
            throw new NotFoundException("指定されたIDのジョブが存在しません。");
        }
        $this->jobRepository->updateStatus($id, $status);
    }


    public function getJobsByStatus(Status $status): array
    {
        return $this->jobRepository->getJobsByStatus($status);
    }


    public function retryJob(string $homeworkID, string $userID)
    {
        $project = $this->projectRepository->findByHomeworkId($homeworkID, $userID);
        if ($project === null) {
            throw new NotFoundException("指定されたHomeworkIDのプロジェクトが存在しません。");
        }

        $job = $this->jobRepository->findByProjectId($project->id);

        if ($job === null) {
            throw new NotFoundException("指定されたProjectIDのジョブが存在しません。");
        }

        $this->jobRepository->updateStatus($job->id, Status::from('pending'));
    }


    public function deleteByHomeworkID(string $homeworkID, string $studentID): void
    {
        $projectInDB = $this->projectRepository->findByHomeworkId($homeworkID, $studentID);
        if ($projectInDB === null) {
            return;
        }
        $jobInDB = $this->jobRepository->findByProjectId($projectInDB->id);
        if ($jobInDB === null) {
            return;
        }

        $this->jobRepository->deleteByHomeworkID($homeworkID, $studentID);
    }

    private function validateJob(Job $job): void
    {

        if (empty($job->id) || empty($job->projectID)) {
            throw new ValidationException("ProjectIDは必須です。");
        }

        $project = $this->projectRepository->findById($job->projectID);
        if ($project === null) {
            throw new NotFoundException("指定されたProjectIDのプロジェクトが存在しません。");
        }
    }

}
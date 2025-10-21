<?php

namespace Application\UseCases;
use Domain\Entities\Job;
use Domain\Entities\User;
use Domain\Entities\Project;
use Domain\Entities\Status;
use Domain\Repositories\JobRepositoryInterface;
use Domain\Repositories\ProjectRepositoryInterface;
use Domain\Repositories\UserRepositoryInterface;


class JobUseCase
{
    private JobRepositoryInterface $jobRepository;
    private ProjectRepositoryInterface $projectRepository;
    private UserRepositoryInterface $userRepository;

    public function __construct(
        JobRepositoryInterface $jobRepository,
        UserRepositoryInterface $userRepository,
        ProjectRepositoryInterface $projectRepository
    ) {
        $this->jobRepository = $jobRepository;
        $this->userRepository = $userRepository;
        $this->projectRepository = $projectRepository;
    }


    public function add(Job $job)
    {
        $this->validateJob($job);
        $this->jobRepository->insert($job);
    }

    public function getAllJobs(int $limit = 100, int $offset = 0): array
    {
        return $this->jobRepository->getAllJobs($limit, $offset);
    }


    public function updateStatus(string $id, Status $status): void
    {
        $job = $this->jobRepository->findById($id);
        if ($job === null) {
            throw new \InvalidArgumentException("指定されたIDのジョブが存在しません。");
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
            throw new \InvalidArgumentException("指定されたHomeworkIDのプロジェクトが存在しません。");
        }

        $job = $this->jobRepository->findByProjectId($project->id);

        $this->jobRepository->updateStatus($job->id, Status::from('pending'));
    }


    public function deleteById(string $id): void
    {
        $job = $this->jobRepository->findById($id);
        if ($job === null) {
            throw new \InvalidArgumentException("指定されたIDのジョブが存在しません。");
        }
        $this->jobRepository->deleteById($id);
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


    public function findById(string $id): Job
    {
        $job = $this->jobRepository->findById($id);
        if ($job === null) {
            throw new \InvalidArgumentException("指定されたIDのジョブが存在しません。");
        }
        return $job;
    }


    public function findByUserId(string $userId): array
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new \InvalidArgumentException("指定されたUserIDのユーザーが存在しません。");
        }

        $projects = $this->projectRepository->findByUserId($userId);
        if (empty($projects)) {
            throw new \InvalidArgumentException("指定されたUserIDのプロジェクトが存在しません。");
        }

        $jobs = [];
        foreach ($projects as $project) {
            $job = $this->jobRepository->findByProjectId($project->id);
            if ($job !== null) {
                $jobs[] = $job;
            }
        }

        if (empty($jobs)) {
            throw new \InvalidArgumentException("指定されたUserIDのジョブが存在しません。");
        }

        return $jobs;
    }

    private function validateJob(Job $job): void
    {

        if (empty($job->id) || empty($job->projectId)) {
            throw new \InvalidArgumentException("ProjectIDは必須です。");
        }

        $project = $this->projectRepository->findById($job->projectId);
        if ($project === null) {
            throw new \InvalidArgumentException("指定されたProjectIDのプロジェクトが存在しません。");
        }
    }

}
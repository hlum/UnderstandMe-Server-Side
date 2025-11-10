<?php

namespace Domain\Repositories;
use Domain\Entities\Job;
use Domain\Entities\Status;

interface JobRepositoryInterface
{
    public function insert(Job $job): void;
    public function findById(string $id): ?Job;
    public function findByProjectId(string $projectId): ?Job;
    public function updateStatus(string $id, Status $status): void;
    /**
     * @return Job[]
     */
    public function getJobsByStatus(Status $status, int $limit = 10, int $offset = 0): array;
    public function deleteByHomeworkID(string $homeworkID, string $studentID): void;
}
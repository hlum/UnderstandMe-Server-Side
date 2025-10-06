<?php

namespace Domain\Repositories;
use Domain\Entities\Job;
use Domain\Entities\Status;

interface JobRepositoryInterface {
    public function insert(Job $job): void;
    public function findById(string $id): ?Job;
    public function findByProjectId(string $projectId): ?Job;
    public function updateStatus(string $id, Status $status): void;
    public function getAllJobs(int $limit = 100, int $offset = 0): array;
    public function getPendingJobs(int $limit = 10, int $offset = 0): array;
    public function deleteById(string $id): void;
}
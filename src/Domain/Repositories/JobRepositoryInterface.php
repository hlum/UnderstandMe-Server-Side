<?php

namespace Domain\Entities;
use Domain\Entities\Job;
use Domain\Entities\Status;

interface JobRepositoryInterface {
    public function insert(Job $job): void;
    public function findById(string $id): ?Job;
    public function findByUserId(string $userId): array;
    public function findByProjectId(string $projectId): ?Job;
    public function updateStatus(string $id, Status $status): void;

    public function deleteById(string $id): void;
}
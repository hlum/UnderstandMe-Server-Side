<?php

namespace Domain\Repositories;
use Domain\Entities\Project;


interface ProjectRepositoryInterface
{
    public function insert(Project $project): void;
    public function findById(string $id): ?Project;
    public function findByUserId(string $user_id): array;
    public function findByHomeworkId(string $homework_id, string $student_id): ?Project;
    public function deleteByHomeworkID(string $homeworkID, string $studentID);
}
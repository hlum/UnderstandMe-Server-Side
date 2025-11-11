<?php
namespace Domain\Repositories;
use Domain\Entities\Question;

interface QuestionRepositoryInterface
{
    public function insert(Question $question): void;


    public function findById(string $id): ?Question;

    
    /**
     * @return Question[]
     */
    public function findByJobId(string $jobId): array;
}
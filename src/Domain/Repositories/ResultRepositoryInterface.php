<?php

namespace Domain\Repositories;
use Domain\Entities\Result;

interface ResultRepositoryInterface
{
    public function insertResult(Result $result);
    public function fetchResultWithHomeworkIDAndUserID(string $homeworkID, string $userID): ?Result;

    /**
     * @return Result[]
     */
    public function fetchResultsByUserID(string $userID, int $year): array;
    public function updateResult(string $resultID, int $score, int $correctAnswers);
}
<?php

namespace Domain\Repositories;
use Domain\Entities\Result;

interface ResultRepositoryInterface
{
    public function insertResult(Result $result);
    public function fetchResultWithHomeworkIDAndUserID(string $homeworkID, string $userID): ?Result;
    public function updateResult(string $resultID, int $score, int $correctAnswers);
}
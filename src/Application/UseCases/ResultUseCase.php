<?php
namespace Application\UseCases;

use Application\CustomExceptions\NotFoundException;
use Domain\Repositories\ResultRepositoryInterface;
use Domain\Entities\Result;

class ResultUseCase
{
    private ResultRepositoryInterface $resultRepository;

    public function __construct(ResultRepositoryInterface $resultRepository)
    {
        $this->resultRepository = $resultRepository;
    }


    public function getResult(string $resultID): Result
    {
        return $this->getResult($resultID);
    }

    public function fetchResultsByUserID(string $userID, int $year): array
    {
        return $this->resultRepository->fetchResultsByUserID($userID, $year);
    }


    public function fetchResultWithHomeworkIDAndUserID(string $homeworkID, string $userID): ?Result
    {
        return $this->resultRepository->fetchResultWithHomeworkIDAndUserID($homeworkID, $userID);
    }


    public function saveNewResult(Result $result)
    {
        $this->resultRepository->insertResult($result);
    }


    public function updateResult(string $resultID, int $score, int $correctAnswers): void
    {
        $this->resultRepository->updateResult($resultID, $score, $correctAnswers);
    }
}
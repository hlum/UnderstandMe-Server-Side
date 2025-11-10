<?php
namespace Application\UseCases;

use App\Application\CustomExceptions\NotFoundException;
use Domain\Repositories\ResultRepositoryInterface;
use Domain\Entities\Result;

class ResultUseCase
{
    private ResultRepositoryInterface $resultRepository;

    public function __construct(ResultRepositoryInterface $resultRepository)
    {
        $this->resultRepository = $resultRepository;
    }

    public function fetchResultWithHomeworkIDAndUserID(string $homeworkID, string $userID): Result
    {
        $result = $this->resultRepository->fetchResultWithHomeworkIDAndUserID($homeworkID, $userID);
        if ($result === null) {
            throw new NotFoundException("指定されたHomeworkIDとUserIDの結果が存在しません。");
        }
        return $result;
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
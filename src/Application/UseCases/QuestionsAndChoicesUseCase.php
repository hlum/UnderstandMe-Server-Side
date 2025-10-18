<?php
namespace Application\UseCases;
use Infrastructure\Persistence\MySQLQuestionsAndChoicesRepository;


class QuestionsAndChoicesUseCase
{
    private MySQLQuestionsAndChoicesRepository $repository;

    public function __construct(MySQLQuestionsAndChoicesRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getQuestionsAndChoicesByHomeworkId(string $homeworkId, string $userID): array
    {
        return $this->repository->getQuestionsAndChoicesByHomeworkId($homeworkId, $userID);
    }
}
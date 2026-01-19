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
        $questionsAndChoices = $this->repository->getQuestionsAndChoicesByHomeworkId($homeworkId, $userID);
        // Shuffle the choices arrange
        foreach($questionsAndChoices as $qc) {
            shuffle($qc->choices);
        }
        return $questionsAndChoices;
    }
}
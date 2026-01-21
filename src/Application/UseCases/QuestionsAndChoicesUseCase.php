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


    public function getQuestionsAndChoicesByHomeworkIdWithNoCorrectChoiceData(string $homeworkId, string $userID): array
    {
        $questionsAndChoices = $this->repository->getQuestionsAndChoicesByHomeworkIdWithNoCorrectChoiceData($homeworkId, $userID);

        // Shuffle the choices in place
        foreach($questionsAndChoices as &$qc) {
            shuffle($qc['choices']);
        }

        return $questionsAndChoices;
    }

    public function getQuestionsAndChoicesByHomeworkId(string $homeworkId, string $userID): array
    {
        $questionsAndChoices = $this->repository->getQuestionsAndChoicesByHomeworkId($homeworkId, $userID);

        // Shuffle the choices in place
        foreach($questionsAndChoices as &$qc) {
            shuffle($qc['choices']);
        }

        return $questionsAndChoices;
    }

}
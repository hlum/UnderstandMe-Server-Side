<?php

namespace Application\UseCases;

use Application\CustomExceptions\ValidationException;
use Domain\Repositories\AnswerRepositoryInterface;
use Domain\Repositories\QuestionRepositoryInterface;
use Domain\Entities\Answer;
use Domain\Repositories\UserRepositoryInterface;


class AnswerUseCase
{
    private AnswerRepositoryInterface $answerRepo;
    private UserRepositoryInterface $userRepo;
    private QuestionRepositoryInterface $questionRepo;


    public function __construct(
        AnswerRepositoryInterface $answerRepo,
        UserRepositoryInterface $userRepo,
        QuestionRepositoryInterface $questionRepo
    ) {
        $this->answerRepo = $answerRepo;
        $this->userRepo = $userRepo;
        $this->questionRepo = $questionRepo;
    }

    public function addAnswer(Answer $answer)
    {
        $this->validateAnswer($answer);
        $this->answerRepo->addAnswer($answer);
    }

    /**
     * 
     * @param string $questionID
     * @param string $userID
     * @return Answer[]
     */
    public function findAnswer(string $questionID, string $userID): array
    {
        return $this->answerRepo->getAnswers($questionID, $userID);
    }


    public function findAnswersForHomework(string $homeworkID, string $userID): array
    {
        return $this->answerRepo->getAnswersForHomework($homeworkID, $userID);
    }



    private function validateAnswer(Answer $answer)
    {
        $user = $this->userRepo->findById($answer->userID);
        if ($user === null) {
            throw new ValidationException('指定されたuser_idが存在しません。');
        }

        $question = $this->questionRepo->findById($answer->questionID);
        if ($question === null) {
            throw new ValidationException('指定されたquestion_idが存在しません。');
        }
    }
}
<?php
namespace Domain\Repositories;
use Domain\Entities\Answer;

interface AnswerRepositoryInterface
{
    public function addAnswer(Answer $answer): void;
    public function getAnswers(string $questionID, string $userID): array;
}
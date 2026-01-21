<?php

namespace Domain\Repositories;

interface QuestionsAndChoicesRepositoryInterface
{
    public function getQuestionsAndChoicesByHomeworkId(string $homeworkId, string $userID): array;
    public function getQuestionsAndChoicesByHomeworkIdWithNoCorrectChoiceData(string $homeworkId, string $userID): array;
}
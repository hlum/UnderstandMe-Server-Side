<?php

namespace Domain\Repositories;

use Domain\Entities\GeneratedQuestionsAndChoices;

interface QuestionGeneratorInterface
{

    /**
     * @return GeneratedQuestionsAndChoices[]
     */
    public function generateQuestions(string $jobId, int $numQuestions, string $codeSnippet): array;
}
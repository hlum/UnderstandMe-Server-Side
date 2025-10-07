<?php

namespace Domain\Repositories;

interface QuestionGeneratorInterface {
    public function generateQuestions(string $jobId, int $numQuestions, string $codeSnippet): array;
}
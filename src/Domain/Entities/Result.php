<?php

namespace Domain\Entities;
use DateTimeImmutable;
use JsonSerializable;

class Result implements JsonSerializable
{
    public string $id;
    public string $userID;
    public string $homeworkID;
    public int $totalQuestions;
    public int $correctAnswers;
    public int $score;

    private function __construct(
        string $id,
        string $userID,
        string $homeworkID,
        int $totalQuestions,
        int $correctAnswers,
        int $score
    ) {
        $this->id = $id;
        $this->userID = $userID;
        $this->homeworkID = $homeworkID;
        $this->totalQuestions = $totalQuestions;
        $this->correctAnswers = $correctAnswers;
        $this->score = $score;
    }


    public static function createNew(
        string $userID,
        string $homeworkID,
        int $totalQuestions,
        int $correctAnswers,
        int $score
    ): self {
        return new self(
            id: uniqid('result_', true),
            homeworkID: $homeworkID,
            userID: $userID,
            totalQuestions: $totalQuestions,
            correctAnswers: $correctAnswers,
            score: $score
        );
    }

    public static function fromDBRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['user_id'],
            $row['homework_id'],
            $row['total_questions'],
            $row['correct_answers'],
            $row['score']
        );
    }



    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userID,
            'homework_id' => $this->homeworkID,
            'total_questions' => $this->totalQuestions,
            'correct_answers' => $this->correctAnswers,
            'score' => $this->score
        ];
    }

}
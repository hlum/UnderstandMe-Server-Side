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
    public DateTimeImmutable $evaluatedAt;

    private function __construct(
        string $id,
        string $userID,
        string $homeworkID,
        int $totalQuestions,
        int $correctAnswers,
        int $score,
        DateTimeImmutable $evaluatedAt = new DateTimeImmutable()
    ) {
        $this->id = $id;
        $this->userID = $userID;
        $this->homeworkID = $homeworkID;
        $this->totalQuestions = $totalQuestions;
        $this->correctAnswers = $correctAnswers;
        $this->score = $score;
        $this->evaluatedAt = $evaluatedAt;
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
            score: $score,
            evaluatedAt: new DateTimeImmutable()
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
            $row['score'],
            new DateTimeImmutable($row['evaluated_at'])
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
            'score' => $this->score,
            'evaluated_at' => $this->evaluatedAt->format('Y-m-d H:i:s'),
        ];
    }

}
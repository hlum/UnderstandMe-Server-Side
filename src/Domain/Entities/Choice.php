<?php


// Question 1個には５個の選択肢がある
namespace Domain\Entities;
use DateTimeImmutable;
use JsonSerializable;

class Choice implements JsonSerializable
{
    public string $id;
    public string $questionId;
    public string $choiceText;
    public bool $isCorrect;


    private function __construct(
        string $id,
        string $questionId,
        string $choiceText,
        bool $isCorrect
    ) {
        $this->id = $id;
        $this->questionId = $questionId;
        $this->choiceText = $choiceText;
        $this->isCorrect = $isCorrect;
    }

    public static function createNew(
        string $questionId,
        string $choiceText,
        bool $isCorrect
    ): self {
        return new self(
            id: uniqid('cho_', true),
            questionId: $questionId,
            choiceText: $choiceText,
            isCorrect: $isCorrect
        );
    }

    public static function fromDBRow(array $row): self
    {
        return new self(
            id: $row['id'],
            questionId: $row['question_id'],
            choiceText: $row['choice_text'],
            isCorrect: (bool) $row['is_correct']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'question_id' => $this->questionId,
            'choice_text' => $this->choiceText,
            'is_correct' => $this->isCorrect,
        ];
    }
}
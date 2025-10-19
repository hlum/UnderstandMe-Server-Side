<?php
namespace Domain\Entities;
use DateTimeImmutable;
use JsonSerializable;

class Answer implements JsonSerializable
{
    public string $id;
    public string $questionID;
    public string $userID;
    public string $selectedChoiceID;
    public DateTimeImmutable $createdAt;

    private function __construct(
        string $id,
        string $questionID,
        string $userID,
        string $selectedChoiceID,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->questionID = $questionID;
        $this->userID = $userID;
        $this->selectedChoiceID = $selectedChoiceID;
        $this->createdAt = $createdAt;
        $this->answeredAt = $createdAt;
    }


    public static function createNew(
        string $questionID,
        string $userID,
        string $selectedChoiceID
    ): self {
        return new self(
            id: uniqid('ans_', true),
            questionID: $questionID,
            userID: $userID,
            selectedChoiceID: $selectedChoiceID,
            createdAt: new DateTimeImmutable()
        );
    }


    public static function fromDBRow(array $row): self
    {
        return new self(
            id: $row['id'],
            questionID: $row['question_id'],
            userID: $row['user_id'],
            selectedChoiceID: $row['selected_choice_id'],
            createdAt: new DateTimeImmutable($row['created_at'])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'question_id' => $this->questionID,
            'user_id' => $this->userID,
            'selected_choice_id' => $this->selectedChoiceID,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
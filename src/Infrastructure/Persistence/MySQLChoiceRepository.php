<?php

namespace Infrastructure\Persistence;
use Domain\Entities\Choice;
use Domain\Repositories\ChoiceRepositoryInterface;
use mysqli;
use mysqli_result;

class MySQLChoiceRepository implements ChoiceRepositoryInterface
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function insert(Choice $choice): void
    {
        $query = "INSERT INTO choices (id, question_id, choice_text, is_correct) VALUES (?, ?, ?, ?)";
        $types = 'sssi';
        $params = [$choice->id, $choice->questionId, $choice->choiceText, $choice->isCorrect ? 1 : 0];
        $errorMessage = 'Choice保存に失敗しました。';
        $this->executeQuery($query, $types, $params, $errorMessage);
    }


    public function findById(string $id): ?Choice
    {
        $query = "SELECT * FROM choices WHERE id = ?";
        $types = 's';
        $params = [$id];
        $errorMessage = 'IdによるChoice検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $row = $result->fetch_assoc();

        if ($row === null) {
            return null;
        }

        return Choice::fromDBRow($row);
    }


    public function findByQuestionId(string $questionId): array
    {
        $query = "SELECT * FROM choices WHERE question_id = ?";
        $types = 's';
        $params = [$questionId];
        $errorMessage = 'QuestionIDによるChoice検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $choices = [];

        while ($row = $result->fetch_assoc()) {
            $choices[] = Choice::fromDBRow($row);
        }

        return $choices;
    }


    private function executeQuery(
        string $query,
        string $types,
        array $params,
        string $error_message
    ): mysqli_result|bool {
        $stmt = $this->connection->prepare($query);

        if ($stmt === false) {
            throw new \RuntimeException('ステートメントの準備に失敗しました。詳細: ' . $this->connection->error);
        }

        $stmt->bind_param($types, ...$params);
        if ($stmt === false) {
            throw new \RuntimeException('パラメータのバインドに失敗しました。詳細: ' . $this->connection->error);
        }

        if (!$stmt->execute()) {
            throw new \RuntimeException('クエリの実行に失敗しました。詳細: ' . $stmt->error);
        }

        return $stmt->get_result();
    }
}
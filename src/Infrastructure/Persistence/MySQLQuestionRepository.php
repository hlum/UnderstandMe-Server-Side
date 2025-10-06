<?php

namespace Infrastructure\Persistence;
use Domain\Entities\Question;
use Domain\Repositories\QuestionRepositoryInterface;
use mysqli_result;
use mysqli;


class MySQLQuestionRepository implements QuestionRepositoryInterface {
    private mysqli $connection;

    public function __construct(mysqli $connection) {
        $this->connection = $connection;
    }


    public function insert(Question $question): void {
        $query = "INSERT INTO questions (id, job_id, text, created_at) VALUES (?, ?, ?, ?)";
        $types = 'ssss';
        $params = [$question->id, $question->jobId, $question->text, $question->createdAt->format('Y-m-d H:i:s')];
        $errorMessage = 'Question保存に失敗しました。';
        $this->executeQuery($query, $types, $params, $errorMessage);
    }


    public function findById(string $id): ?Question {
        $query = "SELECT * FROM questions WHERE id = ?";
        $types = 's';
        $params = [$id];
        $errorMessage = 'IdによるQuestion検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $row = $result->fetch_assoc();

        if($row === null) {
            return null;
        }

        return Question::fromDBRow($row);
    }


    public function findByJobId(string $jobId): array {
        $query = "SELECT * FROM questions WHERE job_id = ?";
        $types = 's';
        $params = [$jobId];
        $errorMessage = 'JobIDによるQuestion検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $questions = [];

        while($row = $result->fetch_assoc()) {
            $questions[] = Question::fromDBRow($row);
        }

        return $questions;
    }



    private function executeQuery(
        string $query,
        string $types,
        array $params,
        string $error_message): mysqli_result|bool {
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
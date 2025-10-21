<?php
namespace Infrastructure\Persistence;

use Domain\Repositories\ResultRepositoryInterface;
use mysqli;
use mysqli_result;
use Domain\Entities\Result;

class MySQLResultRepository implements ResultRepositoryInterface
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }


    public function fetchResultWithHomeworkIDAndUserID(string $homeworkID, string $userID): ?Result
    {
        $query = 'SELECT * FROM results WHERE homework_id = ? AND user_id = ?';
        $types = 'ss';
        $params = [$homeworkID, $userID];
        $errorMessage = 'homeworkIDとuserIDによる検索は失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $row = $result->fetch_assoc();
        if ($row === null) {
            return null;
        }
        return Result::fromDBRow($row);
    }

    public function fetchResult(string $resultID): ?Result
    {
        $query = 'SELECT * FROM results WHERE id = ?';
        $types = 's';
        $params = [$resultID];
        $errorMessage = 'resultIDによる検索は失敗しました。';
        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $row = $result->fetch_assoc();

        if ($row === null) {
            return null;
        }

        return Result::fromDBRow($row);
    }


    public function fetchResultsByUserID(string $userID, int $year): array
    {
        $startDate = sprintf('%04d-04-01 00:00:00', $year);
        $endDate = sprintf('%04d-03-31 23:59:59', $year + 1);

        $query = 'SELECT r.* FROM results r
                  JOIN homeworks h ON r.homework_id = h.id
                  WHERE r.user_id = ? AND h.due_date BETWEEN ? AND ?';
        $types = 'sss';
        $params = [$userID, $startDate, $endDate];
        $errorMessage = 'userIDと年度によるResults検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $results = [];

        while ($row = $result->fetch_assoc()) {
            $results[] = Result::fromDBRow($row);
        }

        return $results;
    }


    public function insertResult(Result $result)
    {
        $query = 'INSERT results (id, user_id, homework_id, total_questions, correct_answers, score) VALUES (?,?,?,?,?,?)';
        $types = 'sssiii';
        $params = [$result->id, $result->userID, $result->homeworkID, $result->totalQuestions, $result->correctAnswers, $result->score];
        $errorMessage = 'Resultの保存に失敗しました。';
        $this->executeQuery($query, $types, $params, $errorMessage);
    }


    public function updateResult(string $resultID, int $score, int $correctAnswers)
    {
        $query = "UPDATE results SET score = ?, correct_answers = ? WHERE id = ?";
        $types = 'iis';
        $params = [$score, $correctAnswers, $resultID];
        $errorMessage = 'Resultの更新に失敗しました。';
        $this->executeQuery($query, $types, $params, $errorMessage);
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
<?php

namespace Infrastructure\Persistence;
use mysqli;
use mysqli_result;
use Domain\Repositories\AnswerRepositoryInterface;
use Domain\Entities\Answer;

class MySQLAnswerRepository implements AnswerRepositoryInterface
{

    private mysqli $connection;
    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function addAnswer(Answer $answer): void
    {
        $query = "INSERT INTO answers (id, question_id, user_id, selected_choice_id) VALUES (?, ?, ?, ?)";
        $types = 'ssss';
        $params = [$answer->id, $answer->questionID, $answer->userID, $answer->selectedChoiceID];
        $errorMessage = 'Answer保存に失敗しました。';
        $this->executeQuery($query, $types, $params, $errorMessage);
    }


    /**
     * Summary of getAnswers
     * @param string $questionID
     * @param string $userID
     * @return Answer[]
     */
    public function getAnswers(string $questionID, string $userID): array
    {
        $query = 'SELECT * FROM answers WHERE question_id = ? AND user_id = ?';
        $types = 'ss';
        $params = [$questionID, $userID];
        $errorMessage = 'questionIDとuserIDによるAnswer検索に失敗しました。';
        $result = $this->executeQuery($query, $types, $params, $errorMessage);

        $answers = [];

        while ($row = $result->fetch_assoc()) {
            $answers[] = Answer::fromDBRow($row);
        }

        return $answers;
    }


    /**
     * fetch answers by homeworkID and userID
     * @param string $homeworkID
     * @param string $userID
     * @return Answer[]
     */
    public function getAnswersForHomework(string $homeworkID, string $userID): array
    {
        $query = '
            SELECT a.* FROM answers a
            JOIN questions q ON a.question_id = q.id
            JOIN jobs j ON q.job_id = j.id
            JOIN projects p ON j.project_id = p.id
            WHERE p.homework_id = ? AND a.user_id = ?
        ';
        $types = 'ss';
        $params = [$homeworkID, $userID];
        $errorMessage = 'homeworkIDとuserIDによるAnswer検索に失敗しました。';
        $result = $this->executeQuery($query, $types, $params, $errorMessage);

        $answers = [];

        while ($row = $result->fetch_assoc()) {
            $answers[] = Answer::fromDBRow($row);
        }

        return $answers;
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
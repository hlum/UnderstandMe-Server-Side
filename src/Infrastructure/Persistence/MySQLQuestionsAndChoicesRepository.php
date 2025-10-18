<?php

namespace Infrastructure\Persistence;
use mysqli_result;
use mysqli;
use Domain\Repositories\QuestionsAndChoicesRepositoryInterface;

class MySQLQuestionsAndChoicesRepository implements QuestionsAndChoicesRepositoryInterface
{

    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->$connection = $$connection;
    }

    public function getQuestionsAndChoicesByHomeworkId(string $homeworkId, string $userID): array
    {
        $query = "SELECT * FROM questions_with_choices WHERE user_id = ? AND homework_id = ? ORDER BY question_id, choice_id";
        $types = 'ss';
        $params = [$userID, $homeworkId];
        $errorMessage = 'HomeworkIDとUserIDによるQuestionsAndChoices検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);

        $questionsAndChoices = [];

        while ($row = $result->fetch_assoc()) {
            $questionId = $row['question_id'];

            // Create new question entry if not already added
            if (!isset($questionsAndChoices[$questionId])) {
                $questionsAndChoices[$questionId] = [
                    'question_id' => $row['question_id'],
                    'job_id' => $row['job_id'],
                    'project_id' => $row['project_id'],
                    'homework_id' => $row['homework_id'],
                    'user_id' => $row['user_id'],
                    'question_text' => $row['question_text'],
                    'created_at' => $row['created_at'],
                    'choices' => [] // initialize choices array
                ];
            }

            // Add choice if available
            if (!empty($row['choice_id'])) {
                $questionsAndChoices[$questionId]['choices'][] = [
                    'choice_id' => $row['choice_id'],
                    'choice_text' => $row['choice_text'],
                    'is_correct' => (bool) $row['is_correct']
                ];
            }
        }

        // Reindex array (convert associative to numeric)
        return array_values($questionsAndChoices);
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
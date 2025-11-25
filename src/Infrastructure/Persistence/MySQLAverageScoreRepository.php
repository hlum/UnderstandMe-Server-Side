<?php
namespace Infrastructure\Persistence;
use Domain\Entities\AverageScore;
use Domain\Repositories\AverageScoreRepositoryInterface;
use mysqli_result;
use mysqli;


class MySQLAverageScoreRepository implements AverageScoreRepositoryInterface
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }


    public function fetch(string $userID): array
{
    $query = "
        SELECT 
            c.name AS class_name,
            FLOOR(AVG(r.score)) AS average_score,
            COUNT(r.id) AS finished_homework_count,
            (
                SELECT COUNT(*)
                FROM homeworks h2
                WHERE h2.class_id = c.id
            ) AS total_homework_count
        FROM classes c
        LEFT JOIN homeworks h ON h.class_id = c.id
        LEFT JOIN results r 
            ON r.homework_id = h.id
           AND r.user_id = ?
        WHERE c.id IN (
            SELECT cl.id
            FROM classes cl
            JOIN users u ON u.id = ?
            WHERE cl.class_code IS NULL
              AND cl.major_code = u.major_code
              AND cl.admission_year = u.admission_year

            UNION

            SELECT e.class_id
            FROM student_class_enrollments e
            WHERE e.student_id = ?
        )
        GROUP BY c.id, c.name
        ORDER BY c.name ASC
    ";

    $types = 'sss';
    $params = [$userID, $userID, $userID];

    $result = $this->executeQuery($query, $types, $params);

    $averageScores = [];

    while ($row = $result->fetch_assoc()) {
        $averageScores[] = new AverageScore(
            $row['class_name'],
            (int)$row['average_score'] ?? 0,
            (int)$row['finished_homework_count'],
            (int)$row['total_homework_count']
        );
    }

    return $averageScores;
}



    private function executeQuery(
        string $query,
        string $types,
        array $params,
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
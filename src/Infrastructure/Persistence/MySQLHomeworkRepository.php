<?php

namespace Infrastructure\Persistence;

use Domain\Entities\Homework;
use Domain\Repositories\HomeworkRepositoryInterface;
use mysqli;
use mysqli_result;


class MySQLHomeworkRepository implements HomeworkRepositoryInterface {
    private mysqli $connection;

    public function __construct(mysqli $connection) {
        $this->connection = $connection;
    }

    public function insert(Homework $homework): void {
        $query = "INSERT INTO homeworks (id, teacher_id, major_id, title, description, due_date) VALUES (?, ?, ?, ?, ?, ?)";
        $types = 'ssssss';
        $dueDateString = $homework->dueDate->format('Y-m-d H:i:s');
        $params = [$homework->id, $homework->teacherID, $homework->majorID, $homework->title, $homework->description, $dueDateString];
        $errorMessage = 'Homework保存に失敗しました。';
        $this->executeQuery($query, $types, $params, $errorMessage);
    }


    public function findById(string $id): ?Homework{
        $query = "SELECT * FROM homeworks WHERE id = ?";
        $types = 's';
        $params = [$id];
        $errorMessage = 'IdによるHomework検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $row = $result->fetch_assoc();

        if($row === null) {
            return null;
        }

        return Homework::fromDBRow($row);
    }


    public function findByMajorId(string $majorId): array {
        $query = "SELECT * FROM homeworks WHERE major_id = ?";
        $types = 's';
        $params = [$majorId];
        $errorMessage = 'MajorIDによるHomework検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $homeworks = [];

        while($row = $result->fetch_assoc()) {
            $homeworks[] = Homework::fromDBRow($row);
        }

        return $homeworks;
        
    }


    public function findByTeacherId(string $teacherId): array {
        $query = "SELECT * FROM homeworks WHERE teacher_id = ?";
        $types = 's';
        $params = [$teacherId];
        $errorMessage = 'MajorIDによるHomework検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $homeworks = [];

        while($row = $result->fetch_assoc()) {
            $homeworks[] = Homework::fromDBRow($row);
        }

        return $homeworks;
        
    }


    public function deleteById(string $id): void {
        $query = "DELETE FROM homeworks WHERE id = ?";
        $types = 's';
        $params = [$id];
        $errorMessage = 'IDによるHomework削除に失敗しました。';

        $this->executeQuery($query, $types, $params, $errorMessage);
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
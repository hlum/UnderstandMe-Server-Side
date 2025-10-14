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
        $query = "INSERT INTO homeworks (id, teacher_id, class_id, title, description, due_date) VALUES (?, ?, ?, ?, ?, ?)";
        $types = 'ssssss';
        $dueDateString = $homework->dueDate->format('Y-m-d H:i:s');
        $params = [$homework->id, $homework->teacherID, $homework->classID, $homework->title, $homework->description, $dueDateString];
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


    public function findByClassID(string $classID): array {
        $query = "SELECT * FROM homeworks WHERE class_id = ?";
        $types = 's';
        $params = [$classID];
        $errorMessage = 'ClassIDによるHomework検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $homeworks = [];

        while($row = $result->fetch_assoc()) {
            $homeworks[] = Homework::fromDBRow($row);
        }

        return $homeworks;
        
    }


    public function findByIDWithStatus(string $homeworkID): array {
        $query = "SELECT h.id AS homework_id, h.teacher_id AS teacher_id, h.class_id AS class_id, h.title, h.description, h.due_date, h.created_at, p.id AS project_id, j.status AS submission_status
        FROM homeworks h
        LEFT JOIN projects p ON p.homework_id = h.id
        LEFT JOIN jobs j ON j.project_id = p.id WHERE h.id = ? ORDER BY h.created_at DESC";
        $types = 's';
        $params = [$homeworkID];
        $errorMessage = 'HomeworkIDによるHomeworkとその提出状況の検索に失敗しました。';
        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $homeworksWithStatus = [];
        while($row = $result->fetch_assoc()) {
            $homework = Homework::fromDBRow([
                'id' => $row['homework_id'],
                'teacher_id' => $row['teacher_id'],
                'class_id' => $row['class_id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'due_date' => $row['due_date'],
                'created_at' => $row['created_at']
            ]);
            $homeworksWithStatus[] = [
                'homework' => $homework,
                'project_id' => $row['project_id'],
                'submission_status' => $row['submission_status']
            ];
        }
        return $homeworksWithStatus;
    }


    public function findByStudentIDWithStatus(string $studentId): array {
        $query = "SELECT h.id AS homework_id, h.title, h.description, h.due_date, h.created_at, p.id AS project_id, j.status AS submission_status
        FROM homeworks h
        LEFT JOIN projects p ON p.homework_id = h.id AND p.user_id = ?
        LEFT JOIN jobs j ON j.project_id = p.id ORDER BY h.created_at DESC";
        $types = 's';
        $params = [$studentId];
        $errorMessage = '学生IDによるHomeworkとその提出状況の検索に失敗しました。';
        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $homeworksWithStatus = [];
        while($row = $result->fetch_assoc()) {
            $homework = Homework::fromDBRow([
                'id' => $row['homework_id'],
                'teacher_id' => '', // Not needed for this context
                'class_id' => '',   // Not needed for this context
                'title' => $row['title'],
                'description' => $row['description'],
                'due_date' => $row['due_date'],
                'created_at' => $row['created_at']
            ]);
            $homeworksWithStatus[] = [
                'homework' => $homework,
                'project_id' => $row['project_id'],
                'submission_status' => $row['submission_status']
            ];
        }

        return $homeworksWithStatus;
    }


    public function findByClassIDWithStatus(string $classID, string $studentID): array {
        $query = "SELECT h.id AS homework_id, h.title, h.description, h.due_date, h.created_at, p.id AS project_id, j.status AS submission_status
        FROM homeworks h
        LEFT JOIN projects p ON p.homework_id = h.id AND p.user_id = ?
        LEFT JOIN jobs j ON j.project_id = p.id WHERE h.class_id = ? ORDER BY h.created_at DESC";
        $types = 'ss';
        $params = [$studentID, $classID];
        $errorMessage = 'ClassIDとStudentIDによるHomeworkとその提出状況の検索に失敗しました。';
        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $homeworksWithStatus = [];
        while($row = $result->fetch_assoc()) {
            $homework = Homework::fromDBRow([
                'id' => $row['homework_id'],
                'teacher_id' => '', // Not needed for this context
                'class_id' => $classID,
                'title' => $row['title'],
                'description' => $row['description'],
                'due_date' => $row['due_date'],
                'created_at' => $row['created_at']
            ]);
            $homeworksWithStatus[] = [
                'homework' => $homework,
                'project_id' => $row['project_id'],
                'submission_status' => $row['submission_status']
            ];
        }
        return $homeworksWithStatus;
    }


    public function findByTeacherId(string $teacherId): array {
        $query = "SELECT * FROM homeworks WHERE teacher_id = ?";
        $types = 's';
        $params = [$teacherId];
        $errorMessage = 'teacher_id によるHomework検索に失敗しました。';

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
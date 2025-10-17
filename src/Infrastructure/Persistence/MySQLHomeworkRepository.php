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


    public function findByIDWithStatus(string $homeworkID, string $student_id): array {
        $query = "SELECT 
            homework_id,
            homework_title,
            due_date,
            description,
            class_id,
            github_file_link,
            job_status,
            submission_state
            FROM homework_submission_status_per_user
            WHERE homework_id = ? AND user_id = ?;
            ";
            
        $types = 'ss';
        $params = [$homeworkID, $student_id];
        $errorMessage = 'HomeworkIDによるHomeworkとその提出状況の検索に失敗しました。';
        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $homeworksWithStatus = [];
        while($row = $result->fetch_assoc()) {
            $homeworksWithStatus[] = [
                'id' => $row['homework_id'],
                'title' => $row['homework_title'],
                'description' => $row['description'],
                'class_id' => $row['class_id'],
                'due_date' => $row['due_date'],
                'github_file_link' => $row['github_file_link'],
                'job_status' => $row['job_status'],
                'submission_state' => $row['submission_state']
            ];
        }
        return $homeworksWithStatus;
    }


    public function findByStudentIDWithStatus(string $studentId): array {
        $query = "SELECT 
            homework_id,
            homework_title,
            due_date,
            description,
            class_id,
            github_file_link,
            job_status,
            submission_state
            FROM homework_submission_status_per_user
            WHERE user_id = ?;
            ";

        $types = 's';
        $params = [$studentId];
        $errorMessage = 'StudentIDによるHomeworkとその提出状況の検索に失敗しました。';
        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $homeworksWithStatus = [];
        while($row = $result->fetch_assoc()) {
            $homeworksWithStatus[] = [
                'id' => $row['homework_id'],
                'title' => $row['homework_title'],
                'class_id' => $row['class_id'],
                'description' => $row['description'],
                'due_date' => $row['due_date'],
                'github_file_link' => $row['github_file_link'],
                'job_status' => $row['job_status'],
                'submission_state' => $row['submission_state']
            ];
        }
        return $homeworksWithStatus;
    }



    public function findByClassIDWithStatus(string $classID, string $studentID): array {
        $query = "SELECT 
            homework_id,
            homework_title,
            due_date,
            class_id,
            description,
            github_file_link,
            job_status,
            submission_state
            FROM homework_submission_status_per_user
            WHERE user_id = ? AND class_id = ?;
            ";
        $types = 'ss';
        $params = [$studentID, $classID];
        $errorMessage = 'ClassIDとStudentIDによるHomeworkとその提出状況の検索に失敗しました。';
        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $homeworksWithStatus = [];
        while($row = $result->fetch_assoc()) {
            $homeworksWithStatus[] = [
                'id' => $row['homework_id'],
                'title' => $row['homework_title'],
                'class_id' => $row['class_id'],
                'description' => $row['description'],
                'due_date' => $row['due_date'],
                'github_file_link' => $row['github_file_link'],
                'job_status' => $row['job_status'],
                'submission_state' => $row['submission_state']
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
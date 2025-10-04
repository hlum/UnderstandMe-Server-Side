<?php

namespace Infrastructure\Persistence;

use Domain\Entities\Project;
use Domain\Repositories\ProjectRepositoryInterface;
use mysqli;
use mysqli_result;

class MySQLProjectRepository implements ProjectRepositoryInterface {
    private mysqli $connection;

    public function __construct(mysqli $connection) {
        $this->connection = $connection;
    }



    function insert(Project $project): void {
        $query = "INSERT INTO projects (id, homework_id, user_id, github_file_link) VALUES (?, ?, ?, ?)";
        $types = 'ssss';
        $params = [$project->id, $project->homeworkId, $project->userId, $project->githubFileLink];
        $errorMessage = 'Project保存に失敗しました。';
        $this->executeQuery($query, $types, $params, $errorMessage);
    }


    function findByHomeworkId(string $homework_id): ?Project {
        $query = "SELECT * FROM projects WHERE homework_id = ?";
        $types = 's';
        $params = [$homework_id];
        $errorMessage = 'HomeworkIDによるProject検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $row = $result->fetch_assoc();

        if($row === null) {
            return null;
        }

        return Project::fromDBRow($row);
    }


    function findById(string $id): ?Project {
        $query = "SELECT * FROM projects WHERE id = ?";
        $types = 's';
        $params = [$id];
        $errorMessage = 'IdによるProject検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $row = $result->fetch_assoc();

        if($row === null) {
            return null;
        }

        return Project::fromDBRow($row);
    }

    function findByUserId(string $user_id): array {
        $query = "SELECT * FROM projects WHERE user_id = ?";
        $types = 's';
        $params = [$user_id];
        $errorMessage = 'UserIDによるProject検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $projects = [];

        while ($row = $result->fetch_assoc()) {
            $projects[] = Project::fromDBRow($row);
        }
        
        return $projects;
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
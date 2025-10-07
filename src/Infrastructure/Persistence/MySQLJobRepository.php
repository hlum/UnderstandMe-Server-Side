<?php

namespace Infrastructure\Persistence;
use Domain\Repositories\JobRepositoryInterface;
use Domain\Entities\Job;
use Domain\Entities\Status;
use mysqli;
use mysqli_result;

class MySQLJobRepository implements JobRepositoryInterface {
    private mysqli $connection;

    public function __construct(mysqli $connection) {
        $this->connection = $connection;
    }

    public function insert(Job $job): void {
        $query = "INSERT INTO jobs (id, project_id, status) VALUES (?, ?, ?)";
        $types = 'sss';
        $params = [$job->id, $job->projectId, $job->status->getValue()];
        $errorMessage = 'Job保存に失敗しました。';
        $this->executeQuery($query, $types, $params, $errorMessage);
    }

    public function getAllJobs(int $limit = 100, int $offset = 0): array {
        $query = "SELECT * FROM jobs LIMIT ? OFFSET ?";
        $types = 'ii';
        $params = [$limit, $offset];
        $errorMessage = '全部のJobs取得に失敗しました。';
        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        
        while($row = $result->fetch_assoc()) {
            $jobs[] = Job::fromDBRow($row);
        }

        return $jobs;
    }

    public function findById(string $id): ?Job {
        $query = "SELECT * FROM jobs WHERE id = ?";
        $types = 's';
        $params = [$id];
        $errorMessage = 'IdによるJob検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $row = $result->fetch_assoc();

        if($row === null) {
            return null;
        }

        return Job::fromDBRow($row);
    }


    public function findByProjectId(string $projectId): ?Job {
        $query = "SELECT * FROM jobs WHERE project_id = ?";
        $types = 's';
        $params = [$projectId];
        $errorMessage = 'ProjectIDによるJob検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $row = $result->fetch_assoc();

        if($row === null) {
            return null;
        }

        return Job::fromDBRow($row);
    }

    public function getJobsByStatus(Status $status, int $limit = 10, int $offset = 0): array {
        $query = "SELECT * FROM jobs WHERE status = ? LIMIT ? OFFSET ?";
        $types = 'sii';
        $params = [$status->getValue(), $limit, $offset];
        $errorMessage = '保留中のJobs取得に失敗しました。';
        $result = $this->executeQuery($query, $types, $params, $errorMessage);

        $jobs = [];
        while($row = $result->fetch_assoc()) {
            $jobs[] = Job::fromDBRow($row);
        }

        return $jobs;
    }

    public function updateStatus(string $id, Status $status): void {
        $query = "UPDATE jobs SET status = ? WHERE id = ?";
        $types = 'ss';
        $params = [$status->getValue(), $id];
        $errorMessage = 'Jobのステータス更新に失敗しました。';
        $this->executeQuery($query, $types, $params, $errorMessage);
    }


    public function deleteById(string $id): void {
        $query = "DELETE FROM jobs WHERE id = ?";
        $types = 's';
        $params = [$id];
        $errorMessage = 'Jobの削除に失敗しました。';
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
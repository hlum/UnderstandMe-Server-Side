<?php

namespace Infrastructure\Persistence;

use mysqli;
use mysqli_result;
use Domain\Repositories\MajorRepositoryInterface;
use Domain\Entities\Major;


class MySQLMajorRepository implements MajorRepositoryInterface {
    private mysqli $connection;

    public function __construct(mysqli $connection) {
        $this->connection = $connection;
    }


    public function insert(Major $major): void {
        $query = "INSERT INTO majors (id, name, admission_year, class_name) VALUES (?, ?, ?, ?)";
        $types = 'ssiss';
        $params = [$major->id, $major->name, $major->admissionYear, $major->className];
        $errorMessage = 'Major保存に失敗しました。';
        $this->executeQuery($query, $types, $params, $errorMessage);
    }

    public function findById(string $id): ?Major {
        $query = "SELECT * FROM majors WHERE id = ?";
        $types = 's';
        $params = [$id];
        $errorMessage = 'IdによるMajor検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $row = $result->fetch_assoc();

        if ($row === null) {
            return null;
        }

        return Major::fromDBRow($row);
    }


    public function findByClassNameAndAdmissionYear(string $className, int $admissionYear): array {
        $query = "SELECT * FROM majors WHERE class_name = ? AND admission_year = ?";
        $types = 'si';
        $params = [$className, $admissionYear];
        $errorMessage = 'ClassNameとAdmissionYearによるMajor検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $majors = [];
        while ($row = $result->fetch_assoc()) {
            $majors[] = Major::fromDBRow($row);
        }

        return $majors;
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
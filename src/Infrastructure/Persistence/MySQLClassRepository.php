<?php

namespace Infrastructure\Persistence;

use mysqli;
use mysqli_result;
use Domain\Repositories\ClassRepositoryInterface;
use Domain\Entities\ClassEntity;


class MySQLClassRepository implements ClassRepositoryInterface {
    private mysqli $connection;

    public function __construct(mysqli $connection) {
        $this->connection = $connection;
    }


    public function insert(ClassEntity $class): void {
        $query = "INSERT INTO classes (id, teacher_id, name, admission_year, major_code) VALUES (?, ?, ?, ?, ?)";
        $types = 'sssis';
        $params = [$class->id, $class->teacher_id, $class->name, $class->admissionYear, $class->majorCode];
        $errorMessage = 'Class保存に失敗しました。';
        $this->executeQuery($query, $types, $params, $errorMessage);
    }

    public function findById(string $id): ?ClassEntity {
        $query = "SELECT * FROM classes WHERE id = ?";
        $types = 's';
        $params = [$id];
        $errorMessage = 'IdによるClass検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $row = $result->fetch_assoc();

        if ($row === null) {
            return null;
        }

        return ClassEntity::fromDBRow($row);
    }


    public function findByTeacherId(string $teacherId): array {
        $query = "SELECT * FROM classes WHERE teacher_id = ?";
        $types = 's';
        $params = [$teacherId];
        $errorMessage = 'TeacherIdによるClass検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $classes = [];
        while ($row = $result->fetch_assoc()) {
            $classes[] = ClassEntity::fromDBRow($row);
        }

        return $classes;
    }


    public function findByMajorCodeAndAdmissionYear(string $majorCode, int $admissionYear): array {
        $query = "SELECT * FROM classes WHERE major_code = ? AND admission_year = ?";
        $types = 'si';
        $params = [$majorCode, $admissionYear];
        $errorMessage = 'MajorCodeとAdmissionYearによるClass検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $classes = [];
        while ($row = $result->fetch_assoc()) {
            $classes[] = ClassEntity::fromDBRow($row);
        }

        return $classes;
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
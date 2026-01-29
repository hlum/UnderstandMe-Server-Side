<?php

namespace Infrastructure\Persistence;

use mysqli;
use mysqli_result;
use Domain\Repositories\ClassRepositoryInterface;
use Domain\Entities\ClassEntity;


class MySQLClassRepository implements ClassRepositoryInterface
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }


    public function insert(ClassEntity $class): void
    {
        $query = "INSERT INTO classes (id, teacher_id, name, admission_year, major_code, class_code) VALUES (?, ?, ?, ?, ?, ?)";
        $types = 'sssiss';
        $params = [$class->id, $class->teacher_id, $class->name, $class->admissionYear, $class->majorCode, $class->classCode];
        $errorMessage = 'Class保存に失敗しました。';
        $this->executeQuery($query, $types, $params, $errorMessage);
    }

    public function findById(string $id): ?ClassEntity
    {
        $query = "SELECT classes.*, users.name AS teacher_name FROM classes JOIN users ON classes.teacher_id = users.id WHERE classes.id = ?";
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


    public function deleteById(string $id): void
    {
        $query = "DELETE FROM classes WHERE id = ?";
        $types = 's';
        $params = [$id];
        $errorMessage = "IDによる科目削除に失敗しました。";

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
    }


    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return []; // no IDs, return empty array
        }

        // Prepare placeholders for prepared statement (?, ?, ?, ...)
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "SELECT classes.*, users.name AS teacher_name FROM classes JOIN users ON classes.teacher_id = users.id WHERE classes.id IN ($placeholders)";

        // All IDs are strings
        $types = str_repeat('s', count($ids));
        $params = $ids;

        $errorMessage = 'IDsによるClass検索に失敗しました。';
        $result = $this->executeQuery($query, $types, $params, $errorMessage);

        $classes = [];
        while ($row = $result->fetch_assoc()) {
            $classes[] = ClassEntity::fromDBRow($row);
        }

        return $classes;
    }



    public function findByClassCode(string $classCode): ?ClassEntity
    {
        $query = "SELECT classes.*, users.name AS teacher_name FROM classes JOIN users ON classes.teacher_id = users.id WHERE classes.class_code = ?";
        $types = 's';
        $params = [$classCode];
        $errorMessage = "ClassCodeによるClass検索に失敗しました。";
        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $row = $result->fetch_assoc();

        if($row === null) {
            return null;
        }

        return ClassEntity::fromDBRow($row);
    }


    public function findByTeacherId(string $teacherId): array
    {
        $query = "SELECT classes.*, users.name AS teacher_name FROM classes JOIN users ON classes.teacher_id = users.id WHERE classes.teacher_id = ?";
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

    /**
     * (class_codeがついている（選択科目）は取得しない)
     * @param string $majorCode
     * @param int $admissionYear
     * @return ClassEntity[]
     */
    public function findByMajorCodeAndAdmissionYear(string $majorCode, int $admissionYear): array
    {
        $query = "SELECT classes.*, users.name AS teacher_name FROM classes JOIN users ON classes.teacher_id = users.id WHERE classes.major_code = ? AND classes.admission_year = ?";
        $types = 'si';
        $params = [$majorCode, $admissionYear];
        $errorMessage = 'MajorCodeとAdmissionYearによるClass検索に失敗しました。';

        $result = $this->executeQuery($query, $types, $params, $errorMessage);
        $classes = [];
        while ($row = $result->fetch_assoc()) {
            $class = ClassEntity::fromDBRow($row);
            if($class->classCode === null) {
                $classes[] = $class;
            }
        }

        return $classes;
    }


    public function update(ClassEntity $class): void
    {
        $query = "UPDATE classes SET teacher_id = ?, name = ?, admission_year = ?, major_code = ?, class_code = ? WHERE id = ?";
        $types = 'ssisss';
        $params = [$class->teacher_id, $class->name, $class->admissionYear, $class->majorCode, $class->classCode, $class->id];
        $errorMessage = 'Classの更新に失敗しました。';
        $this->executeQuery($query, $types, $params, $errorMessage);
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
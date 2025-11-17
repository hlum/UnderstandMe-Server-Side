<?php 

namespace Infrastructure\Persistence;

use mysqli_result;
use mysqli;
use Domain\Repositories\StudentClassEnrollmentRepositoryInterface;
use Domain\Entities\StudentClassEnrollment;


class MySQLStudentClassEnrollmentRepository implements StudentClassEnrollmentRepositoryInterface
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function insert(StudentClassEnrollment $enrollment): void
    {
        $query = "INSERT INTO student_class_enrollments (id, student_id, class_id) VALUES (?, ?, ?)";
        $types = 'sss';
        $params = [
            $enrollment->id,
            $enrollment->studentID,
            $enrollment->classID,
        ];

        $this->executeQuery($query, $types, $params);
    }


    public function findClassIDsByStudentID(string $studentID): array
    {
        $query = "SELECT class_id FROM student_class_enrollments WHERE student_id = ?";
        $types = 's';
        $params = [$studentID];

        $result = $this->executeQuery($query, $types, $params);
        $classIDs = [];
        while ($row = $result->fetch_assoc()) {
            $classIDs[] = $row['class_id'];
        }

        return $classIDs;
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
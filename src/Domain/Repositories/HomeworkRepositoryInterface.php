<?php

namespace Domain\Repositories;

use Domain\Entities\Homework;

interface HomeworkRepositoryInterface
{
    public function insert(Homework $homework): void;
    public function findById(string $id): ?Homework;
    public function findByTeacherId(string $teacherId): array;
    public function findByClassID(string $classID): array;
    public function deleteById(string $id): void;
    public function findByStudentIDWithStatus(string $studentId): array;
    public function findByIDWithStatus(string $homeworkID, string $student_id): array;
    public function findByClassIDWithStatus(string $classID, string $studentID): array;
    public function fetchHomeworkStatusListForAllStudents(string $homeworkID): array;
}
<?php

namespace Domain\Repositories;

use Domain\Entities\Homework;
use Domain\Entities\HomeworkWithStatus;

interface HomeworkRepositoryInterface
{

    
    public function insert(Homework $homework): void;


    public function findById(string $id): ?Homework;


    /**
     * @return Homework[]
     */
    public function findByTeacherId(string $teacherId): array;


    /**
     * @return Homework[]
     */
    public function findByClassID(string $classID): array;


    public function deleteById(string $id): void;


    /**
     * @return HomeworkWithStatus[]
     */
    public function findByStudentIDWithStatus(string $studentId): array;


    /**
     * @return HomeworkWithStatus[]
     */
    public function findByIDWithStatus(string $homeworkID, string $student_id): array;


    /**
     * @return HomeworkWithStatus[]
     */
    public function findByClassIDWithStatus(string $classID, string $studentID): array;


     /**
     * @return HomeworkWithStatus[]
     */
    public function fetchHomeworkStatusListForAllStudents(string $homeworkID): array;
}
<?php 
namespace Domain\Repositories;

use Domain\Entities\StudentClassEnrollment;

interface StudentClassEnrollmentRepositoryInterface
{
    /**
     * @param StudentClassEnrollment $enrollment
     * @return void
     */
    public function insert(StudentClassEnrollment $enrollment): void;

    /**
     * @return string[]
     */
    public function findClassIDsByStudentID(string $studentID): array;
}
<?php
namespace Src\Application\UseCases;

use Application\CustomExceptions\NotFoundException;
use Domain\Repositories\StudentClassEnrollmentRepositoryInterface;
use Application\CustomExceptions\ValidationException;
use Domain\Entities\StudentClassEnrollment;
use Domain\Repositories\ClassRepositoryInterface;
use Domain\Repositories\UserRepositoryInterface;

class StudentClassEnrollmentUseCase
{
    private StudentClassEnrollmentRepositoryInterface $enrollmentRepository;
    private UserRepositoryInterface $userRepository;
    private ClassRepositoryInterface $classRepository;

    public function __construct(
        StudentClassEnrollmentRepositoryInterface $enrollmentRepository,
        UserRepositoryInterface $userRepository,
        ClassRepositoryInterface $classRepository
        )
    {
        $this->enrollmentRepository = $enrollmentRepository;
        $this->userRepository = $userRepository;
        $this->classRepository = $classRepository;
    }

    public function enrollStudent(string $studentID, string $classCode): void
    {
        if (empty($studentID) || empty($classID)) {
            throw new ValidationException("学生IDまたはクラスIDが無効です。");
        }

        $student = $this->userRepository->findById($studentID);
        if ($student === null) {
            throw new ValidationException("指定された学生IDのユーザーが存在しません。");
        }   

        if ($student->role->getValue() !== 'student') {
            throw new ValidationException("指定されたIDのユーザーは学生ではありません。");
        }


        $class = $this->classRepository->findByClassCode($classCode);
        if(!$class) {
            throw new NotFoundException("指定した科目は存在しません。");
        }

        $enrollment = StudentClassEnrollment::createNew($studentID, $class->id);
        $this->enrollmentRepository->insert($enrollment);
    }
}
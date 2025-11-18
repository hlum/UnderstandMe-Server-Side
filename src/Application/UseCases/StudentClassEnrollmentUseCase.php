<?php
namespace Application\UseCases;

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
        if (empty($studentID)) {
            throw new ValidationException("student_idが無効です。");
        }

        if (empty($classCode)) {
            throw new ValidationException("class_codeが無効です。");
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
            throw new NotFoundException("指定したclass_codeの学科は存在しません。");
        }

        $existingEnrollments = $this->enrollmentRepository->findClassIDsByStudentID($studentID);
        if (in_array($class->id, $existingEnrollments, true)) {
            throw new ValidationException("学生は既にこのクラスに登録されています。");
        }

        $enrollment = StudentClassEnrollment::createNew($studentID, $class->id);
        $this->enrollmentRepository->insert($enrollment);
    }
}
<?php

namespace Application\UseCases;

use App\Application\CustomExceptions\NotFoundException;
use App\Application\CustomExceptions\UnAuthorizedException;
use App\Application\CustomExceptions\ValidationException;
use Domain\Entities\ClassEntity;
use Domain\Repositories\ClassRepositoryInterface;
use Domain\Repositories\UserRepositoryInterface;


class ClassUseCase
{
    private ClassRepositoryInterface $classRepository;
    private UserRepositoryInterface $userRepository;
    public function __construct(
        ClassRepositoryInterface $classRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->classRepository = $classRepository;
        $this->userRepository = $userRepository;
    }

    public function add(ClassEntity $class)
    {
        $this->validateClass($class);
        $this->classRepository->insert($class);
    }

    public function findById(string $id): ClassEntity
    {
        $class = $this->classRepository->findById($id);
        if ($class === null) {
            throw new NotFoundException("指定されたIDのクラスが存在しません。");
        }
        return $class;
    }


    public function findByMajorCodeAndAdmissionYear(string $majorCode, int $admissionYear): array
    {
        $classes = $this->classRepository->findByMajorCodeAndAdmissionYear($majorCode, $admissionYear);
        return $classes;
    }

    public function getClassesByStudentId(string $studentId): array
    {
        $user = $this->userRepository->findById($studentId);
        if ($user === null) {
            throw new ValidationException("指定された学生IDのユーザーが存在しません。");
        }
        if ($user->role->getValue() !== 'student') {
            throw new ValidationException("指定されたIDのユーザーは学生ではありません。");
        }


        return $this->classRepository->findByMajorCodeAndAdmissionYear($user->majorCode, $user->admissionYear) ?? null;
    }

    public function getClassesByTeacherId(string $teacherId): array
    {
        if (!$this->isTeacher($teacherId)) {
            throw new UnAuthorizedException("指定されたIDのユーザーは教師ではありません。");
        }
        return $this->classRepository->findByTeacherId($teacherId);
    }



    public function updateClass(ClassEntity $updatedClass): void
    {
        $existingClass = $this->classRepository->findById($updatedClass->id);
        if ($existingClass === null) {
            throw new NotFoundException("指定されたIDのクラスが存在しません。");
        }


        $this->validateClass($updatedClass);

        // Update the class
        $this->classRepository->update($updatedClass);
    }

    private function isTeacher(string $userId): bool
    {
        $user = $this->userRepository->findById($userId);
        return $user !== null && $user->role->getValue() === 'teacher';
    }

    private function validateClass(ClassEntity $class): void
    {
        if (empty($class->id) || empty($class->name) || empty($class->admissionYear) || empty($class->majorCode)) {
            throw new ValidationException("Classの全てのフィールドは必須です。");
        }

        if (!$this->isTeacher($class->teacher_id)) {
            throw new UnAuthorizedException("指定された教師IDは教師ではありません。");
        }
    }
}
<?php

namespace Application\UseCases;
use Domain\Entities\ClassEntity;
use Domain\Repositories\ClassRepositoryInterface;
use Domain\Repositories\UserRepositoryInterface;


class ClassUseCase {
    private ClassRepositoryInterface $majorRepository;
    private UserRepositoryInterface $userRepository;
    public function __construct(
        ClassRepositoryInterface $majorRepository,
        UserRepositoryInterface $userRepository
        ) {
        $this->majorRepository = $majorRepository;
        $this->userRepository = $userRepository;
    }

    public function add(ClassEntity $class) {
        $this->validateClass($class);
        $this->majorRepository->insert($class);
    }

    public function findById(string $id): ClassEntity {
        $class = $this->majorRepository->findById($id);
        if ($class === null) {
            throw new \InvalidArgumentException("指定されたIDのクラスが存在しません。");
        }
        return $class;
    }


    public function findByMajorCodeAndAdmissionYear(string $majorCode, int $admissionYear): array {
        $classes = $this->majorRepository->findByMajorCodeAndAdmissionYear($majorCode, $admissionYear);
        if (empty($classes)) {
            throw new \InvalidArgumentException("指定された専攻コードと入学年度のクラスが存在しません。");
        }
        return $classes;
    }

    public function getClassesByStudentId(string $studentId): array {
        $user = $this->userRepository->findById($studentId);
        if ($user === null) {
            throw new \InvalidArgumentException("指定された学生IDのユーザーが存在しません。");
        }
        if ($user->role->getValue() !== 'student') {
            throw new \InvalidArgumentException("指定されたIDのユーザーは学生ではありません。");
        }

        
        return $this->majorRepository->findByMajorCodeAndAdmissionYear($user->className, $user->admissionYear) ?? null;
    }

    public function getClassesByTeacherId(string $teacherId): array {
        if (!$this->isTeacher($teacherId)) {
            throw new \InvalidArgumentException("指定されたIDのユーザーは教師ではありません。");
        }
        return $this->majorRepository->findByTeacherId($teacherId);
    }

    private function isTeacher(string $userId): bool {
        $user = $this->userRepository->findById($userId);
        return $user !== null && $user->role->getValue() === 'teacher';
    }

    private function validateClass(ClassEntity $class): void {
        $classInDb = $this->majorRepository->findById($class->id);
        if ($classInDb !== null) {
            throw new \InvalidArgumentException("指定されたIDのクラスは既に存在します。");
        }

        if (empty($class->id) || empty($class->name) || empty($class->admissionYear) || empty($class->majorCode)) {
            throw new \InvalidArgumentException("Classの全てのフィールドは必須です。");
        }

        if(!$this->isTeacher($class->teacher_id)) {
            throw new \InvalidArgumentException("指定された教師IDは教師ではありません。");
        }
    }
}
<?php

namespace Application\UseCases;

use Application\CustomExceptions\NotFoundException;
use Application\CustomExceptions\UnAuthorizedException;
use Application\CustomExceptions\ValidationException;
use Domain\Entities\ClassEntity;
use Domain\Repositories\ClassRepositoryInterface;
use Domain\Repositories\StudentClassEnrollmentRepositoryInterface;
use Domain\Repositories\UserRepositoryInterface;


class ClassUseCase
{
    private ClassRepositoryInterface $classRepository;
    private UserRepositoryInterface $userRepository;
    private StudentClassEnrollmentRepositoryInterface $studentClassEnrollmentRepository;

    public function __construct(
        ClassRepositoryInterface $classRepository,
        UserRepositoryInterface $userRepository,
        StudentClassEnrollmentRepositoryInterface $studentClassEnrollmentRepository
    ) {
        $this->classRepository = $classRepository;
        $this->userRepository = $userRepository;
        $this->studentClassEnrollmentRepository = $studentClassEnrollmentRepository;
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


    /**
     * @return string[]
     */
    public function getAllStudentIDsInExtensionClass(string $classID): array{
        $class = $this->classRepository->findById($classID);
        if(!$class) {
            throw new ValidationException("指定したclassIDの科目はありません。");
        }
        if($class->classCode === null) {
            throw new ValidationException("指定したclassIDは選択科目ではありません。");
        }

        $studentIDs = $this->studentClassEnrollmentRepository->findStudentIDsByClassID($classID);

        return $studentIDs ?? [];
    }


    public function findByMajorCodeAndAdmissionYear(string $majorCode, int $admissionYear): array
    {
        $classes = $this->classRepository->findByMajorCodeAndAdmissionYear($majorCode, $admissionYear);
        return $classes;
    }

    public function getClassesByStudentId(string $studentID): array
    {
        $user = $this->userRepository->findById($studentID);
        if ($user === null) {
            throw new ValidationException("指定された学生IDのユーザーが存在しません。");
        }
        if ($user->role->getValue() !== 'student') {
            throw new ValidationException("指定されたIDのユーザーは学生ではありません。");
        }

        // 普通の科目の取得
        $classes = $this->classRepository->findByMajorCodeAndAdmissionYear($user->majorCode, $user->admissionYear) ?? [];

        // 選択科目の取得
        $optionalClassIDs = $this->studentClassEnrollmentRepository->findClassIDsByStudentID($studentID);
        
        if(!empty($optionalClassIDs)) {
            $optionalClasses = $this->classRepository->findByIDs($optionalClassIDs);
            $classes = array_merge($classes, $optionalClasses);
        }

        // 重複があったら削除
        $classes = array_unique($classes, SORT_REGULAR);

        return $classes;
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


    public function findByClassCode(string $classCode): ?ClassEntity
    {
        return $this->classRepository->findByClassCode($classCode);
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
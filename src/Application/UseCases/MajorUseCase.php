<?php

namespace Application\UseCases;
use Domain\Entities\Major;
use Domain\Repositories\MajorRepositoryInterface;


class MajorUseCase {
    private MajorRepositoryInterface $majorRepository;

    public function __construct(MajorRepositoryInterface $majorRepository) {
        $this->majorRepository = $majorRepository;
    }

    public function add(Major $major) {
        $this->validateMajor($major);
        $this->majorRepository->insert($major);
    }

    public function findById(string $id): Major {
        $major = $this->majorRepository->findById($id);
        if ($major === null) {
            throw new \InvalidArgumentException("指定されたIDの学科が存在しません。");
        }
        return $major;
    }

    public function findByClassNameAndAdmissionYear(string $className, int $admissionYear): array {
        $majors = $this->majorRepository->findByClassNameAndAdmissionYear($className, $admissionYear);
        if (empty($majors)) {
            throw new \InvalidArgumentException("指定されたクラス名と入学年度の学科が存在しません。");
        }
        return $majors;
    }
    public function getMajorsByTeacherId(string $teacherId): array {
        if (!$this->isTeacher($teacherId)) {
            throw new \InvalidArgumentException("指定されたIDのユーザーは教師ではありません。");
        }
        return $this->majorRepository->findByTeacherId($teacherId);
    }

    private function isTeacher(string $userId): bool {
        $user = $this->userRepository->findById($userId);
        return $user !== null && $user->role->getValue() === 'teacher';
    }

    private function validateMajor(Major $major): void {
        $majorInDb = $this->majorRepository->findById($major->id);
        if ($majorInDb !== null) {
            throw new \InvalidArgumentException("指定されたIDの学科は既に存在します。");
        }

        if (empty($major->id) || empty($major->name) || empty($major->admissionYear) || empty($major->className)) {
            throw new \InvalidArgumentException("Majorの全てのフィールドは必須です。");
        }

        if(!$this->isTeacher($major->teacher_id)) {
            throw new \InvalidArgumentException("指定された教師IDは教師ではありません。");
        }
    }
}
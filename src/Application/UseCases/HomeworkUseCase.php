<?php

namespace Application\UseCases;

use DateTimeImmutable;
use Domain\Entities\Homework;
use Domain\Entities\Major;
use Domain\Repositories\UserRepositoryInterface;
use Domain\Repositories\MajorRepositoryInterface;

use Domain\Repositories\HomeworkRepositoryInterface;

class HomeworkUseCase {
    private HomeworkRepositoryInterface $homeworkRepository;
    private UserRepositoryInterface $userRepository;
    private MajorRepositoryInterface $majorRepository;

    public function __construct(
        HomeworkRepositoryInterface $homeworkRepository,
        UserRepositoryInterface $userRepository,
        MajorRepositoryInterface $majorRepository
        ) {
        $this->homeworkRepository = $homeworkRepository;
        $this->userRepository = $userRepository;
        $this->majorRepository = $majorRepository;
    }

    public function add(Homework $homework) {
        $this->validateHomework($homework);
        $this->homeworkRepository->insert($homework);
    }


    public function findById(string $id): Homework {
        $homework = $this->homeworkRepository->findById($id);
        if ($homework === null) {
            throw new \InvalidArgumentException("指定されたIDの宿題が存在しません。");
        }
        return $homework;
    }

    public function findByMajorId(string $majorId): array {
        $this->validateMajor($majorId);
        $homework = $this->homeworkRepository->findByMajorId($majorId);
        if($homework === null) {
            throw new \InvalidArgumentException("指定されたMajorIDの宿題が存在しません。");
        }
        return $homework;
    }


    public function findByTeacherId(string $teacherId): array {
        $this->validateTeacher($teacherId);
        $homework = $this->homeworkRepository->findByTeacherId($teacherId);
        if($homework === null) {
            throw new \InvalidArgumentException("指定されたTeacherIDの宿題が存在しません。");
        }
        return $homework;
    }

    public function findByStudentId(string $studentId): array {
        $student = $this->userRepository->findById($studentId);
        if ($student === null || $student->role->getValue() !== 'student') {
            throw new \InvalidArgumentException("指定されたStudentIDの学生が存在しません。");
        }

        if ($student->className === null || $student->admissionYear === null) {
            throw new \InvalidArgumentException("学生のclassNameまたはadmissionYearが設定されていません。");
        }
        
        $majors = $this->majorRepository->findByClassNameAndAdmissionYear($student->className, $student->admissionYear);
        if (empty($majors)) {
            throw new \InvalidArgumentException("学生の専攻が見つかりません。");
        }

        $homeworks = [];
        foreach ($majors as $major) {
            $homeworks = array_merge($homeworks, $this->homeworkRepository->findByMajorId($major->id));
        }
        return $homeworks;
    }

    private function validateHomework(Homework $homework): void {
       if(str_word_count($homework->title) > 100) {
            throw new \InvalidArgumentException("タイトルが長すぎます。100文字以内にしてください。");
        }
        if(str_word_count($homework->title) < 1) {
            throw new \InvalidArgumentException("タイトルが短すぎます。1文字以上にしてください。");
        }

        $existingHomework = $this->homeworkRepository->findById($homework->id);

        if($existingHomework !== null) {
            throw new \InvalidArgumentException("このIDの宿題は既に存在します。");
        }

        $this->validateTeacher($homework->teacherID);
        $this->validateMajor($homework->majorID);
        $this->validateDueDate($homework->dueDate);
    }

    private function validateMajor(string $majorId): Major {
        $major = $this->majorRepository->findById($majorId);
        if ($major === null) {
            throw new \InvalidArgumentException("指定されたMajorIDの専攻が存在しません。");
        }
        return $major;
    }

    private function validateTeacher(string $teacherId): void {
        $teacher = $this->userRepository->findById($teacherId);
        if ($teacher === null || $teacher->role !== 'teacher') {
            throw new \InvalidArgumentException("指定されたTeacherIDの教師が存在しません。");
        }
    }

    private function validateDueDate(DateTimeImmutable $dueDate): void {
        $now = new \DateTime();
        if ($dueDate <= $now) {
            throw new \InvalidArgumentException("締め切り日は現在日時よりも未来である必要があります。");
        }
    }


}
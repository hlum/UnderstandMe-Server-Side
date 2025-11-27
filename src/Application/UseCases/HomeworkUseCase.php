<?php

namespace Application\UseCases;

use Application\CustomExceptions\NotFoundException;
use Application\CustomExceptions\ValidationException;
use DateTimeImmutable;
use Domain\Entities\Homework;
use Domain\Entities\ClassEntity;
use Domain\Repositories\UserRepositoryInterface;
use Domain\Repositories\ClassRepositoryInterface;

use Domain\Repositories\HomeworkRepositoryInterface;

class HomeworkUseCase
{
    private HomeworkRepositoryInterface $homeworkRepository;
    private UserRepositoryInterface $userRepository;
    private ClassRepositoryInterface $classRepository;

    public function __construct(
        HomeworkRepositoryInterface $homeworkRepository,
        UserRepositoryInterface $userRepository,
        ClassRepositoryInterface $classRepository
    ) {
        $this->homeworkRepository = $homeworkRepository;
        $this->userRepository = $userRepository;
        $this->classRepository = $classRepository;
    }

    public function add(Homework $homework)
    {
        $this->validateHomework($homework);
        $this->homeworkRepository->insert($homework);
    }


    public function findById(string $id): Homework
    {
        $homework = $this->homeworkRepository->findById($id);
        if ($homework === null) {
            throw new NotFoundException("指定されたIDの宿題が存在しません。");
        }
        return $homework;
    }

    public function findByClassId(string $classID): array
    {
        $this->validateClass($classID);
        $homework = $this->homeworkRepository->findByClassID($classID);
        return $homework;
    }


    public function findByTeacherId(string $teacherId): array
    {
        $this->validateTeacher($teacherId);
        $homework = $this->homeworkRepository->findByTeacherId($teacherId);
        return $homework;
    }


    public function findByStudentIDWithStatus(string $studentId): array
    {
        $student = $this->userRepository->findById($studentId);
        if ($student === null || $student->role->getValue() !== 'student') {
            throw new ValidationException("指定されたStudentIDの学生が存在しません。");
        }

        return $this->homeworkRepository->findByStudentIDWithStatus($studentId);
    }

    public function findByIDWithStatus(string $homeworkID, string $studentID): array
    {
        $homework = $this->homeworkRepository->findByIDWithStatus($homeworkID, $studentID);
        if ($homework === null) {
            throw new NotFoundException("指定されたIDの宿題が存在しません。");
        }
        return $homework;
    }


    public function findByClassIDWithStatus(string $classID, string $studentID): array
    {
        $this->validateClass($classID);

        $student = $this->userRepository->findById($studentID);
        if ($student === null || $student->role->getValue() !== 'student') {
            throw new ValidationException("指定されたStudentIDの学生が存在しません。");
        }

        return $this->homeworkRepository->findByClassIDWithStatus($classID, $studentID);
    }

    // 教師の管理画面から全生徒の宿題とステータスを取得
    public function fetchHomeworksStatusListForAllStudents(string $homeworkID): array
    {
        return $this->homeworkRepository->fetchHomeworkStatusListForAllStudents($homeworkID);
    }

    private function validateHomework(Homework $homework): void
    {
        if (str_word_count($homework->title) > 100) {
            throw new ValidationException("タイトルが長すぎます。100文字以内にしてください。");
        }
        $length = mb_strlen($homework->title);

        if ($length > 100) {
            throw new ValidationException("タイトルが長すぎます。100文字以内にしてください。");
        }

        if ($length < 1) {
            throw new ValidationException("タイトルが短すぎます。1文字以上にしてください。");
        }


        $existingHomework = $this->homeworkRepository->findById($homework->id);

        if ($existingHomework !== null) {
            throw new ValidationException("このIDの宿題は既に存在します。");
        }

        $this->validateTeacher($homework->teacherID);
        $this->validateClass($homework->classID);
        $this->validateDueDate($homework->dueDate);
    }

    private function validateClass(string $classID): ClassEntity
    {
        $class = $this->classRepository->findById($classID);
        if ($class === null) {
            throw new ValidationException("指定されたClassIDのクラスが存在しません。");
        }
        return $class;
    }

    private function validateTeacher(string $teacherId): void
    {
        $teacher = $this->userRepository->findById($teacherId);
        if ($teacher === null || $teacher->role->getValue() !== 'teacher') {
            throw new ValidationException("指定されたTeacherIDの教師が存在しません。");
        }
    }

    private function validateDueDate(DateTimeImmutable $dueDate): void
    {
        $now = new \DateTime();
        if ($dueDate <= $now) {
            throw new ValidationException("締め切り日は現在日時よりも未来である必要があります。");
        }
    }


}
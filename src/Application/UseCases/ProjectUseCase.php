<?php

namespace Application\UseCases;

use Application\CustomExceptions\NotFoundException;
use Application\CustomExceptions\ValidationException;
use Domain\Entities\Project;
use Infrastructure\Persistence\MySQLHomeworkRepository;
use Infrastructure\Persistence\MySQLProjectRepository;
use Infrastructure\Persistence\MySQLUserRepository;

class ProjectUseCase
{
    private MySQLProjectRepository $projectRepository;

    private MySQLUserRepository $userRepository;
    private MySQLHomeworkRepository $homeworkRepository;
    public function __construct(
        MySQLProjectRepository $projectRepository,
        MySQLUserRepository $userRepository,
        MySQLHomeworkRepository $homeworkRepository
    ) {
        $this->projectRepository = $projectRepository;
        $this->userRepository = $userRepository;
        $this->homeworkRepository = $homeworkRepository;
    }


    public function add(Project $project)
    {
        $this->validateProject($project);
        $this->projectRepository->insert($project);
    }


    public function findById(string $id): Project
    {
        $project = $this->projectRepository->findById($id);
        if ($project === null) {
            throw new NotFoundException("指定されたIDのプロジェクトが存在しません。");
        }
        return $project;
    }


    public function findByHomeworkId(string $homeworkId, string $userID): ?Project
    {
        $homework = $this->homeworkRepository->findById($homeworkId);
        if ($homework === null) {
            throw new NotFoundException("指定されたHomeworkIDの課題が存在しません。");
        }

        $project = $this->projectRepository->findByHomeworkId($homeworkId, $userID);
        return $project;
    }


    public function findByUserId(string $userId): array
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new NotFoundException("指定されたUserIDのユーザーが存在しません。");
        }

        $projects = $this->projectRepository->findByUserId($userId);
        return $projects;
    }


    public function deleteByHomeworkID(string $homeworkID, string $studentID): void
    {
        $projectInDB = $this->projectRepository->findByHomeworkId($homeworkID, $studentID);
        if ($projectInDB === null) {
            throw new NotFoundException("指定されたHomeworkIDのプロジェクトが存在しません。");
        }

        $this->projectRepository->deleteByHomeworkID($homeworkID, $studentID);
    }


    private function validateProject(Project $project)
    {
        if (empty($project->id) || empty($project->homeworkID) || empty($project->userID) || empty($project->githubFileLink)) {
            throw new ValidationException("Projectの全てのフィールドは必須です。");
        }


        $user = $this->userRepository->findById($project->userID);
        if ($user === null) {
            throw new ValidationException("指定されたUserIDのユーザーが存在しません。");
        }

        $homework = $this->homeworkRepository->findById($project->homeworkID);
        if ($homework === null) {
            throw new ValidationException("指定されたHomeworkIDの課題が存在しません。");
        }

        $projectWithSameHomeworkId = $this->projectRepository->findByHomeworkId($homework->id, $user->id);
        if ($projectWithSameHomeworkId !== null) {
            throw new ValidationException("指定されたHomeworkIDのプロジェクトは既に提出されています。");
        }
    }
}
<?php

namespace Application\UseCases;
use Domain\Entities\Project;
use Domain\Repositories\SnippetsRepo;
use Infrastructure\Persistence\MySQLHomeworkRepository;
use Infrastructure\Persistence\MySQLProjectRepository;
use Infrastructure\Persistence\MySQLUserRepository;

class ProjectUseCase
{
    private MySQLProjectRepository $projectRepository;
    private SnippetsRepo $snippetsRepo;
    private MySQLUserRepository $userRepository;
    private MySQLHomeworkRepository $homeworkRepository;
    public function __construct(
        MySQLProjectRepository $projectRepository,
        SnippetsRepo $snippetsRepo,
        MySQLUserRepository $userRepository,
        MySQLHomeworkRepository $homeworkRepository
    ) {
        $this->projectRepository = $projectRepository;
        $this->snippetsRepo = $snippetsRepo;
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
            throw new \InvalidArgumentException("指定されたIDのプロジェクトが存在しません。");
        }
        return $project;
    }


    public function findByHomeworkId(string $homeworkId): Project
    {
        $homework = $this->homeworkRepository->findById($homeworkId);
        if ($homework === null) {
            throw new \InvalidArgumentException("指定されたHomeworkIDの課題が存在しません。");
        }

        $project = $this->projectRepository->findByHomeworkId($homeworkId);
        if ($project === null) {
            throw new \InvalidArgumentException("指定されたHomeworkIDのプロジェクトが存在しません。");
        }
        return $project;
    }


    public function findByUserId(string $userId): array
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new \InvalidArgumentException("指定されたUserIDのユーザーが存在しません。");
        }

        $projects = $this->projectRepository->findByUserId($userId);
        if (empty($projects)) {
            throw new \InvalidArgumentException("指定されたUserIDのプロジェクトが存在しません。");
        }
        return $projects;
    }


    public function getRandomCodeSnippet(
        string $projectId,
        int $lines = SnippetsRepo::DEFAULT_SNIPPET_LINES
    ): ?string {
        $project = $this->findById($projectId);
        return $this->snippetsRepo->getRandomCodeSnippet($project->githubFileLink, $lines);
    }


    public function deleteByHomeworkID(string $homeworkID, string $studentID): void
    {
        $projectInDB = $this->projectRepository->findByHomeworkId($homeworkID, $studentID);
        if ($projectInDB === null) {
            return;
        }

        $this->projectRepository->deleteByHomeworkID($homeworkID, $studentID);
    }


    private function validateProject(Project $project)
    {
        if (empty($project->id) || empty($project->homeworkId) || empty($project->userId) || empty($project->githubFileLink)) {
            throw new \InvalidArgumentException("Projectの全てのフィールドは必須です。");
        }


        $user = $this->userRepository->findById($project->userId);
        if ($user === null) {
            throw new \InvalidArgumentException("指定されたUserIDのユーザーが存在しません。");
        }

        $homework = $this->homeworkRepository->findById($project->homeworkId);
        if ($homework === null) {
            throw new \InvalidArgumentException("指定されたHomeworkIDの課題が存在しません。");
        }

        $projectWithSameHomeworkId = $this->projectRepository->findByHomeworkId($homework->id, $user->id);
        if ($projectWithSameHomeworkId !== null) {
            throw new \InvalidArgumentException("指定されたHomeworkIDのプロジェクトは既に提出されています。");
        }
    }
}
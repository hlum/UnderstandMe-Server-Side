<?php

namespace Application\UseCases;
use Domain\Entities\Project;
use Domain\Repositories\SnippetsRepo;
use Infrastructure\Persistence\MySQLProjectRepository;

class ProjectUseCase {
    private MySQLProjectRepository $projectRepository;
    private SnippetsRepo $snippetsRepo;
    public function __construct(
        MySQLProjectRepository $projectRepository,
        SnippetsRepo $snippetsRepo
        ) {
        $this->projectRepository = $projectRepository;
        $this->snippetsRepo = $snippetsRepo;
    }


    public function add(Project $project) {
        $this->validateProject($project);
        $this->projectRepository->insert($project);
    }


    public function findById(string $id): Project {
        $project = $this->projectRepository->findById($id);
        if ($project === null) {
            throw new \InvalidArgumentException("指定されたIDのプロジェクトが存在しません。");
        }
        return $project;
    }


    public function findByHomeworkId(string $homeworkId): Project {
        $project = $this->projectRepository->findByHomeworkId($homeworkId);
        if ($project === null) {
            throw new \InvalidArgumentException("指定されたHomeworkIDのプロジェクトが存在しません。");
        }
        return $project;
    }


    public function findByUserId(string $userId): Project {
        $project = $this->projectRepository->findByUserId($userId);
        if ($project === null) {
            throw new \InvalidArgumentException("指定されたUserIDのプロジェクトが存在しません。");
        }
        return $project;
    }


    public function getRandomCodeSnippet(
        string $projectId,
        int $lines = SnippetsRepo::DEFAULT_SNIPPET_LINES
    ): ?string {
        $project = $this->findById($projectId);
        return $this->snippetsRepo->getRandomCodeSnippet($project->githubFileLink, $lines);
    }


    private function validateProject(Project $project) {
        if (empty($project->id) || empty($project->homeworkId) || empty($project->userId) || empty($project->githubFileLink)) {
            throw new \InvalidArgumentException("Projectの全てのフィールドは必須です。");
        }
    }
}
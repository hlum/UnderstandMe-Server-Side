<?php

namespace Application\UseCases;
use Domain\Entities\Question;
use Domain\Repositories\JobRepositoryInterface;
use Domain\Repositories\QuestionRepositoryInterface;

class QuestionUseCase
{
    private QuestionRepositoryInterface $questionRepository;
    private JobRepositoryInterface $jobRepository;
    public function __construct__(
        QuestionRepositoryInterface $questionRepository,
        JobRepositoryInterface $jobRepository
    ) {
        $this->questionRepository = $questionRepository;
        $this->jobRepository = $jobRepository;
    }

    public function add(Question $question)
    {
        $this->validateQuestion($question);
        $this->questionRepository->insert($question);
    }

    public function findById(string $id): Question
    {
        $question = $this->questionRepository->findById($id);
        if ($question === null) {
            throw new \InvalidArgumentException("指定されたIDの質問が存在しません。");
        }
        return $question;
    }


    public function findByJobId(string $jobId): array
    {
        $job = $this->jobRepository->findById($jobId);
        if ($job === null) {
            throw new \InvalidArgumentException("指定されたJobIDの職種が存在しません。");
        }

        return $this->questionRepository->findByJobId($jobId);
    }


    private function validateQuestion(Question $question)
    {
        if (empty($question->text)) {
            throw new \InvalidArgumentException("質問文は必須です。");
        }

        if (empty($question->jobId)) {
            throw new \InvalidArgumentException("職種IDは必須です。");
        }

        if (empty($question->id)) {
            throw new \InvalidArgumentException("質問IDは必須です。");
        }

        $questionInDb = $this->questionRepository->findById($question->id);
        if ($questionInDb !== null) {
            throw new \InvalidArgumentException("この質問IDは既に登録されています。");
        }

        $job = $this->jobRepository->findById($question->jobId);
        if ($job === null) {
            throw new \InvalidArgumentException("指定されたJobIDの職種が存在しません。");
        }
    }
}
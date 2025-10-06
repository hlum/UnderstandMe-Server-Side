<?php

namespace Application\UseCases;
use Domain\Entities\Choice;
use Domain\Repositories\ChoiceRepositoryInterface;
use Domain\Repositories\QuestionRepositoryInterface;


class ChoiceUseCase {
    private ChoiceRepositoryInterface $choiceRepository;
    private QuestionRepositoryInterface $questionRepository;

    public function __construct(
        ChoiceRepositoryInterface $choiceRepository,
        QuestionRepositoryInterface $questionRepository
    ) {
        $this->choiceRepository = $choiceRepository;
        $this->questionRepository = $questionRepository;
    }


    public function add(Choice $choice) {
        $this->validateChoice($choice);
        $this->choiceRepository->insert($choice);
    }


    public function findById(string $id): Choice {
        $choice = $this->choiceRepository->findById($id);
        if ($choice === null) {
            throw new \InvalidArgumentException("指定されたIDの選択肢が存在しません。");
        }
        return $choice;
    }


    public function findByQuestionId(string $questionId): array {
        $question = $this->questionRepository->findById($questionId);
        if ($question === null) {
            throw new \InvalidArgumentException("指定されたQuestionIDの質問が存在しません。");
        }

        return $this->choiceRepository->findByQuestionId($questionId);
    }


    private function validateChoice(Choice $choice) {
        if (empty($choice->text)) {
            throw new \InvalidArgumentException("選択肢のテキストは必須です。");
        }

        if (empty($choice->questionId)) {
            throw new \InvalidArgumentException("質問IDは必須です。");
        }

        if (empty($choice->id)) {
            throw new \InvalidArgumentException("選択肢IDは必須です。");
        }

        $choiceInDb = $this->choiceRepository->findById($choice->id);
        if ($choiceInDb !== null) {
            throw new \InvalidArgumentException("この選択肢IDは既に登録されています。");
        }

        $question = $this->questionRepository->findById($choice->questionId);
        if ($question === null) {
            throw new \InvalidArgumentException("指定された質問IDの質問が存在しません。");
        }

    }
}
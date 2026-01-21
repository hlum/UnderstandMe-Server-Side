<?php

namespace Application\UseCases;

use Application\CustomExceptions\NotFoundException;
use Application\CustomExceptions\ValidationException;
use Domain\Entities\Choice;
use Domain\Repositories\ChoiceRepositoryInterface;
use Domain\Repositories\QuestionRepositoryInterface;


class ChoiceUseCase
{
    private ChoiceRepositoryInterface $choiceRepository;
    private QuestionRepositoryInterface $questionRepository;

    public function __construct(
        ChoiceRepositoryInterface $choiceRepository,
        QuestionRepositoryInterface $questionRepository
    ) {
        $this->choiceRepository = $choiceRepository;
        $this->questionRepository = $questionRepository;
    }


    public function add(Choice $choice)
    {
        $this->validateChoice($choice);
        $this->choiceRepository->insert($choice);
    }


    public function findById(string $id): Choice
    {
        $choice = $this->choiceRepository->findById($id);
        if ($choice === null) {
            throw new ValidationException("指定されたIDの選択肢が存在しません。");
        }
        return $choice;
    }

    public function fetchCorrectChoiceByQuestionId(string $questionId): Choice
    {
        $questionExists = $this->questionRepository->findById($questionId);
        if ($questionExists === null) {
            throw new ValidationException("指定されたQuestionIDの質問が存在しません。");
        }

        $choices =  $this->choiceRepository->findCorrectChoiceByQuestionId($questionId);
        if (count($choices) === 0) {
            throw new NotFoundException("指定されたQuestionIDの正解の選択肢が存在しません。");
        }

        return $choices[0];
    }


    public function findByQuestionId(string $questionId): array
    {
        $question = $this->questionRepository->findById($questionId);
        if ($question === null) {
            throw new ValidationException("指定されたQuestionIDの質問が存在しません。");
        }

        return $this->choiceRepository->findByQuestionId($questionId);
    }


    public function updateCorrectChoice(string $correctChoiceID, string $questionID) {
        $choiceExists = $this->choiceRepository->findById($correctChoiceID);
        if ($choiceExists === null) {
            throw new ValidationException("指定されたIDの選択肢が存在しません。");
        }
        $question = $this->questionRepository->findById($questionID);
        if ($question === null) {
            throw new ValidationException("指定されたQuestionIDの質問が存在しません。");
        }
        $this->choiceRepository->updateCorrectAnswer($correctChoiceID, $questionID);
    }


    private function validateChoice(Choice $choice)
    {
        if (empty($choice->text)) {
            throw new ValidationException("選択肢のテキストは必須です。");
        }

        if (empty($choice->questionId)) {
            throw new ValidationException("質問IDは必須です。");
        }

        if (empty($choice->id)) {
            throw new ValidationException("選択肢IDは必須です。");
        }

        $choiceInDb = $this->choiceRepository->findById($choice->id);
        if ($choiceInDb !== null) {
            throw new ValidationException("この選択肢IDは既に登録されています。");
        }

        $question = $this->questionRepository->findById($choice->questionId);
        if ($question === null) {
            throw new ValidationException("指定された質問IDの質問が存在しません。");
        }

    }
}
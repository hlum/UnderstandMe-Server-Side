<?php

namespace Domain\Repositories;
use Domain\Entities\Choice;

interface ChoiceRepositoryInterface
{
    public function insert(Choice $choice): void;

    /**
     * @param Choice[] $choices
     */
    public function insertBatch(array $choices): void;
    public function findById(string $id): ?Choice;
    
    /**
     * @return Choice[]
     */
    public function findByQuestionId(string $questionId): array;

    /**
     * 正解の選択肢を変更する
     * @param string $newCorrectChoiceID
     * @param  string $questionID
     * @return void
     */
    public function updateCorrectAnswer(string $newCorrectChoiceID, string $questionID);
}
<?php

namespace Domain\Repositories;
use Domain\Entities\Choice;

interface ChoiceRepositoryInterface {
    public function insert(Choice $choice): void;
    public function findById(string $id): ?Choice;
    public function findByQuestionId(string $questionId): array;
}
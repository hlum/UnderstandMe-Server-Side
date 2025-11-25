<?php

namespace Application\UseCases;

use Domain\Repositories\AverageScoreRepositoryInterface;
use Domain\Entities\AverageScore;


class AverageScoreUseCase {
    private AverageScoreRepositoryInterface $averageScoreRepository;

    public function __construct(AverageScoreRepositoryInterface $averageScoreRepository) {
        $this->averageScoreRepository = $averageScoreRepository;
    }

    /**
     * 一人のユーザーの科目ごとの平均スコアを取得する
     * @param string $userID
     * @return AverageScore[]
     */
    public function fetch(string $userID): array {
        return $this->averageScoreRepository->fetch($userID);
    }
}
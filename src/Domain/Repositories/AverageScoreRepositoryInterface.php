<?php
namespace Domain\Repositories;
use Domain\Entities\AverageScore;


interface AverageScoreRepositoryInterface {
    /**
     * 一人のユーザーの科目ごとの平均スコアを取得する
     * @param string $userID
     * @return AverageScore[]
     */
    public function fetch(string $userID): array;
}
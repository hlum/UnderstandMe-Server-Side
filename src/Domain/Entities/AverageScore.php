<?php
namespace Domain\Entities;

use JsonSerializable;

// 科目ごとに平均点を表すエンティティ
class AverageScore implements JsonSerializable
{
    public string $className;
    public int $averageScore;
    public int $finishedHomeworkCount;
    public int $totalHomeworkCount;


    function __construct(
        string $className,
        int $averageScore,
        int $finishedHomeworkCount,
        int $totalHomeworkCount
    ) {
        $this->className = $className;
        $this->averageScore = $averageScore;
        $this->finishedHomeworkCount = $finishedHomeworkCount;
        $this->totalHomeworkCount = $totalHomeworkCount;
    }


    public function jsonSerialize(): array
    {
        return [
            'class_name' => $this->className,
            'average_score' => $this->averageScore,
            'finished_homework_count' => $this->finishedHomeworkCount,
            'total_homework_count' => $this->totalHomeworkCount,
        ];
    }
}
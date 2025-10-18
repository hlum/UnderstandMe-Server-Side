<?php

namespace Domain\Entities;
use Domain\Entities\Choice;
use Domain\Entities\Question;


class GeneratedQuestionsAndChoices
{
    public Question $question;
    /** @var Choice[] */
    public array $choices;

    private function __construct(Question $question, array $choices)
    {
        $this->question = $question;
        $this->choices = $choices;
    }


    public static function createNew(
        Question $question,
        array $choices
    ) {
        return new self($question, $choices);
    }
}
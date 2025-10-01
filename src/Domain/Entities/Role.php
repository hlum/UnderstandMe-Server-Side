<?php

namespace Domain\Entities;

class Role {
    private string $value;

    public function __construct(string $value) {
        $this->value = $value;
    }

    public function getValue(): string {
        return $this->value;
    }

    public static function from(string $value): self {
        if ($value === 'teacher') {
            return new self('teacher');
        } elseif ($value === 'student') {
            return new self('student');
        } else {
            throw new \InvalidArgumentException("Invalid role value: $value");
        }
    }
}
<?php

namespace Domain\Entities;

class Status
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public static function from(string $value): self
    {
        if ($value === 'pending') {
            return new self('pending');
        } elseif ($value === 'processing') {
            return new self('processing');
        } elseif ($value === 'done') {
            return new self('done');
        } elseif ($value === 'failed') {
            return new self('failed');
        } else {
            throw new \InvalidArgumentException("Invalid status value: $value");
        }
    }
}
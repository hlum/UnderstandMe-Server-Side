<?php

namespace Domain\Repositories;

interface SnippetsRepo {
    public const DEFAULT_SNIPPET_LINES = 30;
    public function getRandomCodeSnippet(int $lines = self::DEFAULT_SNIPPET_LINES): ?string;
}
<?php

namespace Domain\Repositories;

interface SnippetsRepo {
    public const DEFAULT_SNIPPET_LINES = 30;
    public function getRandomCodeSnippet(
        string $repo_url,
        int $lines = self::DEFAULT_SNIPPET_LINES
        ): ?string;
}
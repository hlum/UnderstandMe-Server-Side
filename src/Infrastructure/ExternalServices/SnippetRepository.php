<?php

namespace Infrastructure\ExternalServices;

use Domain\Repositories\SnippetsRepo;
use InvalidArgumentException;
use RuntimeException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;
use Infrastructure\ExternalServices\DownloaderFactory;

class SnippetRepository implements SnippetsRepo
{
    private const CODE_EXTENSIONS = [
        'php', 'java', 'kt', 'swift', 'cpp', 'c', 'py', 'js', 'jsx', 'ts', 'tsx', 'rb', 'go', 'rs'
    ];

    private const IGNORE_EXTENSIONS = [
        'json', 'xml', 'yml', 'yaml', 'md', 'txt', 'lock', 'log', 'csv'
    ];

    private const IGNORE_DIRS = [
        'vendor', 'node_modules', '.git', 'build', 'dist', 'out', 'target', '.idea', '.vscode'
    ];

    private const IGNORE_FILES = [
        '.gitignore', '.env', '.env.example', 'composer.lock', 'package-lock.json'
    ];

    /**
     * リポジトリからコードスニペットを取得する
     */
    function getRandomCodeSnippet(string $repo_url, int $lines = self::DEFAULT_SNIPPET_LINES): ?string
    {
        $downloaderFactory = new DownloaderFactory();
        $downloader = $downloaderFactory->create($repo_url);

        $cloneDir = null;

        if ($lines <= 0) {
            throw new InvalidArgumentException('Linesの指定は 1 以上にしてください。');
        }

        try {
            $cloneDir = $downloader->download($repo_url);
            $snippet = $this->extractSnippet($lines, $cloneDir);
            $this->cleanup($cloneDir);
            return $snippet;
        } finally {
            $this->cleanup($cloneDir);
        }
    }

    /**
     * Repositoryからコードを抽出する
     */
    private function extractSnippet(int $snippetLines, string $cloneDir): ?string
    {
        $files = $this->getCodeFiles($cloneDir);
        $rankedFiles = $this->rankFiles($files);

        if (empty($rankedFiles)) {
            return null;
        }

        return $this->extractMergedSnippets($rankedFiles, $snippetLines);
    }

    /**
     * 複数のファイルからスニペットをマージして必要な行数を満たす
     */
    private function extractMergedSnippets(array $rankedFiles, int $targetLines): string
    {
        $mergedSnippet = [];
        $currentLineCount = 0;

        foreach ($rankedFiles as $fileData) {
            if ($currentLineCount >= $targetLines) {
                break;
            }

            $remainingLines = $targetLines - $currentLineCount;
            
            // ファイルの実際の行数（空行除去後）を取得
            $allLines = file($fileData['file'], FILE_IGNORE_NEW_LINES);
            if (empty($allLines)) {
                continue;
            }
            
            $nonEmptyFileLines = $this->removeEmptyLines(implode("\n", $allLines));
            $fileLineCount = count($nonEmptyFileLines);
            
            // ファイル全体が必要な行数より少ない場合は全体を含める
            if ($fileLineCount <= $remainingLines) {
                $snippetLines = $nonEmptyFileLines;
            } else {
                // ランダムな部分を抽出
                $snippet = $this->extractRandomSnippet($fileData['file'], $remainingLines);
                if ($snippet === null) {
                    continue;
                }
                $snippetLines = $this->removeEmptyLines($snippet);
            }
            
            if (!empty($snippetLines)) {
                // ファイル名をコメントとして追加（オプション）
                $filename = basename($fileData['file']);
                $mergedSnippet[] = "// File: {$filename}";
                $mergedSnippet = array_merge($mergedSnippet, $snippetLines);
                $mergedSnippet[] = ""; // ファイル間の区切り
                
                $currentLineCount = count($mergedSnippet);
            }
        }

        // 最終的な行数調整
        $mergedSnippet = array_slice($mergedSnippet, 0, $targetLines);

        return implode("\n", $mergedSnippet);
    }

    /**
     * 文字列またはスニペットから空行を除去する
     */
    private function removeEmptyLines(string $snippet): array
    {
        $lines = explode("\n", $snippet);
        
        // 空行と空白のみの行を除去
        $nonEmptyLines = array_filter($lines, function($line) {
            return trim($line) !== '';
        });

        return array_values($nonEmptyLines);
    }

    /**
     * ファイルを難しさやサイズでランク付けする
     */
    private function rankFiles(array $files): array
    {
        $ranked = [];

        foreach ($files as $file) {
            $fileData = $this->analyzeFile($file);
            if ($fileData !== null) {
                $ranked[] = $fileData;
            }
        }

        // スコアで降順にソート
        usort($ranked, fn($a, $b) => $b['score'] <=> $a['score']);

        return $ranked;
    }

    /**
     * ファイルを解析して行数と複雑さを評価する
     */
    private function analyzeFile(string $file): ?array
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (!in_array($ext, self::CODE_EXTENSIONS, true)) {
            return null;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return null;
        }

        $lines = substr_count($content, "\n") + 1;
        $complexity = $this->estimateComplexity($content, $ext);
        $score = $lines + ($complexity * 2);

        return [
            'file' => $file,
            'lines' => $lines,
            'complexity' => $complexity,
            'score' => $score
        ];
    }

    /**
     * ファイルからランダムなコードスニペットを抽出する
     */
    private function extractRandomSnippet(string $file, int $snippetLines): ?string
    {
        $allLines = file($file, FILE_IGNORE_NEW_LINES);
        
        if (empty($allLines)) {
            return null;
        }

        $total = count($allLines);

        if ($total <= $snippetLines) {
            return implode("\n", $allLines);
        }

        $start = random_int(0, $total - $snippetLines);
        $snippet = array_slice($allLines, $start, $snippetLines);

        return implode("\n", $snippet);
    }

    /**
     * ディレクトリからコードファイルを再帰的に取得する
     */
    private function getCodeFiles(string $dir): array
    {
        if (!is_dir($dir)) {
            throw new RuntimeException("ディレクトリが存在しません: {$dir}");
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isDir() || !$this->shouldIncludeFile($file)) {
                continue;
            }
            $files[] = $file->getPathname();
        }

        return $files;
    }

    /**
     * ファイルが解析に適しているかどうかを判断する
     */
    private function shouldIncludeFile(\SplFileInfo $file): bool
    {
        $filePath = $file->getPathname();
        $basename = $file->getBasename();

        // Skip ignored files
        if (in_array($basename, self::IGNORE_FILES, true)) {
            return false;
        }

        // Skip ignored directories
        foreach (self::IGNORE_DIRS as $ignoreDir) {
            if (str_contains($filePath, DIRECTORY_SEPARATOR . $ignoreDir . DIRECTORY_SEPARATOR)) {
                return false;
            }
        }

        // Skip test/spec files
        if (preg_match('/\b(?:test|spec|__tests__|\.test\.|\.spec\.)\b/i', $basename)) {
            return false;
        }

        // Skip ignored extensions
        $extension = strtolower($file->getExtension());
        if (in_array($extension, self::IGNORE_EXTENSIONS, true)) {
            return false;
        }

        return true;
    }

    /**
     * ファイルのコードの複雑さを推定する
     */
    private function estimateComplexity(string $code, string $ext): int
    {
        $patterns = [
            'php' => ['function ', 'class ', 'interface ', 'trait ', 'abstract ', 'namespace '],
            'java' => ['class ', 'interface ', 'implements ', 'extends ', 'abstract '],
            'kt' => ['fun ', 'class ', 'interface ', 'suspend ', 'sealed ', 'data class'],
            'swift' => ['func ', 'class ', 'struct ', 'enum ', 'protocol ', 'extension '],
            'cpp' => ['class ', 'template', 'virtual ', 'namespace ', 'struct '],
            'c' => ['struct ', 'typedef ', 'union '],
            'py' => ['def ', 'class ', 'async def', 'lambda '],
            'js' => ['function ', 'class ', '=>', 'async ', 'export '],
            'jsx' => ['function ', 'class ', '=>', 'async ', 'export ', 'const '],
            'ts' => ['function ', 'class ', 'interface ', 'type ', 'enum '],
            'tsx' => ['function ', 'class ', 'interface ', 'type ', 'enum ', 'const '],
            'rb' => ['def ', 'class ', 'module ', 'lambda'],
            'go' => ['func ', 'type ', 'struct ', 'interface '],
            'rs' => ['fn ', 'struct ', 'impl ', 'trait ', 'enum '],
        ];

        $keywords = $patterns[$ext] ?? ['function', 'class'];
        $count = 0;

        foreach ($keywords as $keyword) {
            $count += substr_count($code, $keyword);
        }

        return $count;
    }

    /**
     * クローンしたリポジトリの一時ディレクトリを削除する
     */
    private function cleanup(string $cloneDir): void
    {
        if (!isset($cloneDir) || !is_dir($cloneDir)) {
            return;
        }

        // Platformによって異なる削除方法
        if (DIRECTORY_SEPARATOR === '\\') {
            // Windows
            exec(sprintf('rd /s /q %s 2>&1', escapeshellarg($cloneDir)));
        } else {
            // Unix-like
            exec(sprintf('rm -rf %s 2>&1', escapeshellarg($cloneDir)));
        }
    }
}
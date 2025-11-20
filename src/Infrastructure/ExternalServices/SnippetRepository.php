<?php
namespace Infrastructure\ExternalServices;
require_once __DIR__ . '/../../../config/config.php';
use Domain\Repositories\SnippetsRepo;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use InvalidArgumentException;

class SnippetRepository implements SnippetsRepo
{


     private const CODE_EXTENSIONS = [
        'php',
        'js',
        'ts',
        'tsx',
        'jsx',
        'java',
        'kt',
        'swift',
        'cpp',
        'c',
        'cs',
        'rb',
        'py',
        'go',
        'rs',
        'vue',
        'scala',
        'ino',
        'h'
    ];

    private const IGNORE_DIRS = [
        'node_modules',
        'vendor',
        'Pods',
        'build',
        'dist',
        'target',
        '.git',
        '.idea',
        '.gradle',
        '.vscode',
        '__pycache__',
        'coverage',
        '.next'
    ];

    private const IGNORE_EXTENSIONS = [
        'xcodeproj',
        'build',
        'vscode',
        'xcassets',
        'readme',
        'plist',
        'json',
        'mp3',
        'png',
        'jpg',
        'jpeg',
        'gif',
        'svg',
        'lock',
        'log',
        'bundle',
        'mp4',
        'zip',
        'jar',
        'wav',
        'sh',
        'md',
        'xml',
        'yml',
        'yaml',
        'toml',
        'ico',
        'woff',
        'woff2',
        'ttf'
    ];

    private const IGNORE_FILES = [
        'README.md',
        'LICENSE',
        '.gitignore',
        'composer.json',
        'package.json',
        'yarn.lock',
        'package-lock.json',
        'Podfile',
        'CMakeLists.txt',
        'Gemfile',
        'Makefile'
    ];

    /**
     * ランダムなコードをリポジトリから取得する
     * 
     * @param string $repoUrl リポジトリのURL
     * @param int $lines 何行のコードを取得するか
     * @return string|null コードを返すか、適切なファイルが見つからない場合はnullを返す
     * @throws RuntimeException クローンまたは処理に失敗した場合
     */
    public function getRandomCodeSnippet(
        string $repoUrl,
        int $lines = SnippetsRepo::DEFAULT_SNIPPET_LINES
    ): ?string {
        $downloaderFactory = new DownloaderFactory();
        $downloader = $downloaderFactory->create($repoUrl);

        if ($lines <= 0) {
            throw new InvalidArgumentException('Linesの指定は 1 以上にしてください。');
        }

        try {
            $cloneDir = $downloader->download($repoUrl);
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

        // 最も高くランク付けされたファイルを選択
        $topFile = $rankedFiles[0]['file'];

        return $this->extractRandomSnippet($topFile, $snippetLines);
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

        if ($lines < MIN_FILE_LINES) {
            return null;
        }

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
    private function extractRandomSnippet(string $file, int $snippetLines): string
    {
        $allLines = file($file, FILE_IGNORE_NEW_LINES);
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
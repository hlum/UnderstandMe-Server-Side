<?php

namespace Infrastructure\ExternalServices;

use Domain\Repositories\SnippetsRepo;
use InvalidArgumentException;
use RuntimeException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;
use Infrastructure\ExternalServices\DownloaderFactory;
use Throwable;

class SnippetRepository implements SnippetsRepo
{
private const CODE_EXTENSIONS = [
        // Web - Frontend
        'php', 'js', 'jsx', 'ts', 'tsx', 'vue', 'svelte', 'html', 'htm', 'css', 'scss', 'sass', 'less', 'styl',
        
        // Web - Backend
        'asp', 'aspx', 'jsp', 'erb', 'ejs', 'hbs', 'handlebars', 'twig', 'blade',
        
        // Mobile
        'java', 'kt', 'kts', 'swift', 'dart', 'm', 'mm',
        
        // Systems Programming
        'c', 'cpp', 'cc', 'cxx', 'h', 'hpp', 'hxx', 'rs', 'go', 'zig',
        
        // Scripting
        'py', 'rb', 'pl', 'pm', 'lua', 'sh', 'bash', 'zsh', 'fish', 'ps1', 'psm1', 'bat', 'cmd',
        
        // Functional/Academic
        'hs', 'lhs', 'ml', 'mli', 'fs', 'fsi', 'fsx', 'ex', 'exs', 'erl', 'hrl', 'clj', 'cljs', 'cljc', 'scala', 'sc',
        
        // JVM Languages
        'groovy', 'gradle', 'kts',
        
        // .NET
        'cs', 'vb', 'fs',
        
        // Other
        'r', 'jl', 'nim', 'v', 'sol', 'move', 'cairo'
    ];

    private const IGNORE_EXTENSIONS = [
        // Data/Config
        'json', 'xml', 'yml', 'yaml', 'toml', 'ini', 'cfg', 'conf', 'config', 'properties', 'env',
        
        // Documentation
        'md', 'markdown', 'rst', 'txt', 'adoc', 'asciidoc', 'textile',
        
        // Logs
        'log', 'logs', 'out', 'err',
        
        // Lock files
        'lock',
        
        // Data files
        'csv', 'tsv', 'dat', 'data', 'sql', 'db', 'sqlite', 'sqlite3',
        
        // Build/Package
        'map', 'min', 'bundle', 'chunk',
        
        // Archives
        'zip', 'tar', 'gz', 'bz2', '7z', 'rar', 'tgz',
        
        // Images
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'ico', 'webp', 'tiff', 'tif', 'psd', 'ai',
        
        // Media
        'mp3', 'mp4', 'avi', 'mov', 'wmv', 'flv', 'wav', 'ogg', 'webm',
        
        // Fonts
        'ttf', 'otf', 'woff', 'woff2', 'eot',
        
        // Documents
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        
        // Certificates
        'pem', 'crt', 'key', 'p12', 'pfx', 'cer',
        
        // Other
        'bak', 'tmp', 'temp', 'cache', 'swp', 'swo', 'DS_Store'
    ];

    private const IGNORE_DIRS = [
        // Dependencies
        'vendor', 'node_modules', 'bower_components', 'jspm_packages', 'packages',
        
        // Version Control
        '.git', '.svn', '.hg', '.bzr',
        
        // Build/Output
        'build', 'dist', 'out', 'target', 'bin', 'obj', 'output', 'release', 'debug',
        
        // Cache
        'cache', '.cache', '.parcel-cache', '.next', '.nuxt', '.vuepress', '.docusaurus',
        '__pycache__', '.pytest_cache', '.mypy_cache', '.tox', '.eggs',
        
        // IDE/Editor
        '.idea', '.vscode', '.vs', '.eclipse', '.settings', '.metadata', '.netbeans',
        
        // Logs
        'logs', 'log',
        
        // Coverage
        'coverage', '.coverage', '.nyc_output', 'htmlcov',
        
        // Platform Specific
        '.gradle', '.m2', '.ivy2', '.bundle', '.terraform', '.serverless',
        
        // Mobile
        '.expo', '.expo-shared', 'ios/Pods', 'android/.gradle',
        
        // Testing
        '.pytest_cache', '.phpunit.result.cache', 'TestResults',
        
        // Documentation
        'docs/_build', 'site', '_site', 'public',
        
        // Other
        'backup', 'backups', '.sass-cache', '.turbo', 'out-tsc'
    ];

    private const IGNORE_FILES = [
        // Version Control
        '.gitignore', '.gitattributes', '.gitmodules', '.gitkeep', '.hgignore', '.svnignore',
        
        // Environment
        '.env', '.env.*', '.envrc', '.env.local', '.env.development', '.env.production', '.env.test',
        
        // Lock Files
        'composer.lock', 'package-lock.json', 'yarn.lock', 'pnpm-lock.yaml', 'poetry.lock', 
        'Gemfile.lock', 'Pipfile.lock', 'cargo.lock', 'go.sum', 'mix.lock', 'pubspec.lock',
        
        // Documentation
        'README.md', 'README.txt', 'README', 'CHANGELOG.md', 'CHANGELOG', 'CONTRIBUTING.md',
        'AUTHORS', 'CONTRIBUTORS', 'HISTORY.md', 'NEWS.md', 'RELEASES.md',
        
        // License
        'LICENSE', 'LICENSE.txt', 'LICENSE.md', 'COPYING', 'COPYRIGHT',
        
        // CI/CD
        '.travis.yml', '.gitlab-ci.yml', 'appveyor.yml', 'circle.yml', 'azure-pipelines.yml',
        'bitbucket-pipelines.yml', 'Jenkinsfile', '.circleci', 'cloudbuild.yaml',
        
        // Docker
        'Dockerfile', 'Dockerfile.*', 'docker-compose.yml', 'docker-compose.*.yml', '.dockerignore',
        
        // Build Tools
        'Makefile', 'makefile', 'GNUmakefile', 'Rakefile', 'Gruntfile.js', 'Gulpfile.js',
        'webpack.config.js', 'rollup.config.js', 'vite.config.js', 'vite.config.ts',
        'esbuild.config.js', 'tsconfig.json', 'jsconfig.json', 'babel.config.js', '.babelrc',
        
        // Linting/Formatting
        '.eslintrc', '.eslintrc.*', '.eslintignore', '.prettierrc', '.prettierrc.*', '.prettierignore',
        '.stylelintrc', '.stylelintrc.*', '.editorconfig', '.jshintrc', '.jscsrc', 'phpcs.xml',
        'phpstan.neon', 'psalm.xml', '.php-cs-fixer.php', 'pylint.rc', '.flake8', 'tslint.json',
        
        // IDE
        '.idea', '.vscode', '*.iml', '*.code-workspace', '.project', '.classpath', '.settings',
        
        // Config Files
        'config.php', 'config.js', 'config.json', 'firebase.json', 'firebase.js', 'firebase-config.js',
        'vercel.json', 'netlify.toml', '.nvmrc', '.node-version', '.ruby-version', '.python-version',
        
        // Web Server
        '.htaccess', '.htpasswd', 'nginx.conf', 'web.config',
        
        // Package Managers
        'composer.json', 'package.json', 'bower.json', 'requirements.txt', 'Pipfile', 'Gemfile',
        'go.mod', 'Cargo.toml', 'build.gradle', 'pom.xml', 'pubspec.yaml', 'mix.exs',
        
        // CMake
        'CMakeLists.txt', 'CMakeCache.txt',
        
        // System
        '.DS_Store', 'Thumbs.db', 'desktop.ini', 'ehthumbs.db',
        
        // PHP
        'phpunit.xml', 'phpunit.xml.dist', 'behat.yml',
        
        // JavaScript/Node
        '.npmrc', '.yarnrc', '.nvmrc', 'jest.config.js', 'vitest.config.js',
        
        // Python
        'setup.py', 'setup.cfg', 'MANIFEST.in', 'pyproject.toml', 'tox.ini',
        
        // Ruby
        '.rspec', 'Gemfile', '.rubocop.yml',
        
        // Coverage
        '.coveragerc', 'coverage.xml', '.lcov',
        
        // Security
        '.snyk', 'SECURITY.md',
        
        // Other
        'renovate.json', '.editorconfig', 'sonar-project.properties', 'codeship-services.yml'
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
        } catch(Throwable $e) {
            echo 'Snippet取得エラー: ' . $e->getMessage();
            throw new RuntimeException('コードスニペットの取得に失敗しました。');
        } finally {
            if (isset($cloneDir)) {
                $this->cleanup($cloneDir);
            }
        }
    }

    /**
     * Repositoryからコードを抽出する
     */
    private function extractSnippet(int $snippetLines, string $cloneDir): ?string
    {
        $files = $this->getCodeFiles($cloneDir);

        if(empty($files)) {
            echo "コードファイルが見つかりませんでした。\n";
            return null;
        }

        $rankedFiles = $this->rankFiles($files);

        if (empty($rankedFiles)) {
            echo "ランキングしたファイルがありません。\n";
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
            echo "無視されたファイル: {$basename}\n";
            return false;
        }

        // Skip ignored directories
        foreach (self::IGNORE_DIRS as $ignoreDir) {
            if (str_contains($filePath, DIRECTORY_SEPARATOR . $ignoreDir . DIRECTORY_SEPARATOR)) {
                echo "無視されたディレクトリ内のファイル: {$filePath}\n";
                return false;
            }
        }

        // Skip test/spec files
        if (preg_match('/\b(?:test|spec|__tests__|\.test\.|\.spec\.)\b/i', $basename)) {
            echo "テストファイルを無視: {$basename}\n";
            return false;
        }

        // Skip ignored extensions
        $extension = strtolower($file->getExtension());
        if (in_array($extension, self::IGNORE_EXTENSIONS, true)) {
            echo "無視された拡張子のファイル: {$filePath}\n";
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
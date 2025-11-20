<?php

namespace Infrastructure\ExternalServices;

use Application\CustomExceptions\UnSupportedRepoURL;
use Domain\Repositories\RepoDownloader;
use RuntimeException;
use InvalidArgumentException;

class GithubDownloader implements RepoDownloader
{
    public function download(string $url): string {
        $this->validateRepoUrl($url);
        $dirPath = $this->generateTempDir();
        $this->cloneRepo($url, $dirPath);
        return $dirPath;
    }


    /**
     * RepositoryのURLが正しいか検証する
     */
    private function validateRepoUrl(string $url): void
    {
        // git URLの基本的な検証
        if (!preg_match('#^(https?://|git@)#i', $url)) {
            throw new UnSupportedRepoURL('無効なリポジトリURLの形式');
        }
    }

    /**
     * 一意の一時ディレクトリパスを生成する
     */
    private function generateTempDir(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'repo_' . uniqid('', true);
    }

    /**
     * Repositoryを一時ディレクトリにクローンする
     */
    private function cloneRepo(string $repoUrl, string $cloneDir): void
    {
        $cmd = sprintf(
            'git clone --depth=1 --quiet %s %s 2>&1',
            escapeshellarg($repoUrl),
            escapeshellarg($cloneDir)
        );

        exec($cmd, $output, $status);

        if ($status !== 0) {
            throw new RuntimeException(
                "リポジトリのクローンに失敗しました: " . implode("\n", $output)
            );
        }

        if (!is_dir($cloneDir)) {
            throw new RuntimeException('クロンのリポジトリが生成されませんでした。');
        }
    }
}
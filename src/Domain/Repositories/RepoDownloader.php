<?php
namespace Domain\Repositories;

interface RepoDownloader {
        /**
     * 渡されたリポジトリリンクをチェックし、ダウンロードする
     * @param string $url
     * @return string 保存したしたパス
     */
    public function download(string $url): string;
}
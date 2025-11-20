<?php
// このRepoは,渡されたURLに基づいて適切なSnippetリポジトリを返すファクトリクラスです。(GoogleDrive, GitHubなど)
namespace Infrastructure\ExternalServices;

use Application\CustomExceptions\UnSupportedRepoURL;
use Domain\Repositories\RepoDownloader;

class DownloaderFactory
{
    public function create(string $url): RepoDownloader {
        if(str_contains($url, "github")) {
            echo "GithubDownloaderを返します。\n";
            return new GithubDownloader();
        } else if (str_contains($url, "drive.google.com")) {
            echo "GoogleDriveDownloaderを返します。\n";
            return new GoogleDriveDownloader();
        } else {
            throw new UnSupportedRepoURL("無効なURL: $url");
        }
    }
}

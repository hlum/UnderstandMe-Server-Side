<?php

use PHPUnit\Framework\TestCase;
use Infrastructure\ExternalServices\GithubDownloader;

class GithubDownloaderTest extends TestCase
{
    /**
     * 実際に Google Drive から ZIP をダウンロードし、展開されるかを確認
     */
    public function testRealDownload()
    {
        $downloader = new GithubDownloader();

        $url = "https://github.com/hlum/UnderstandMe-Server-Side.git";

        // 実ダウンロード
        $path = $downloader->download($url);

        // フォルダが作られているか
        $this->assertDirectoryExists($path);

        // フォルダ内に最低 1 ファイルは存在するはず
        $files = scandir($path);
        $this->assertGreaterThan(2, count($files)); // . と .. を除く
    }
}

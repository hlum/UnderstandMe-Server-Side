<?php

use PHPUnit\Framework\TestCase;
use Infrastructure\ExternalServices\SnippetRepository;

class SnippetRepositoryTest extends TestCase
{
    /**
     * 実際に Google Drive から ZIP をダウンロードし、展開されるかを確認
     */
    public function testRealDownload()
    {
        $snippetRepo = new SnippetRepository();

        $url = "https://drive.google.com/file/d/1_8eNj07rNS7B-JyCZ-lj1GtRYG03Ye-M/view?usp=sharing";

        // スニペット取得
        $snippet = $snippetRepo->getRandomCodeSnippet($url);

        // スニペットが空でないことを確認
        $this->assertNotEmpty($snippet);
    }
}

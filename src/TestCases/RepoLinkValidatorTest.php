<?php

use PHPUnit\Framework\TestCase;
use Helpers\RepoLinkValidator;

class RepoLinkValidatorTest extends TestCase
{
    /**
     * 実際に Google Drive から ZIP をダウンロードし、展開されるかを確認
     */
    public function testValidCase()
    {
        $validator = new RepoLinkValidator();

        $url = "https://drive.google.com/file/d/1_8eNj07rNS7B-JyCZ-lj1GtRYG03Ye-M/view?usp=sharing";

        $isValid = $validator->validate($url);

        $this->assertTrue($isValid);
    }


    public function testInvalidCase()
    {
        $validator = new RepoLinkValidator();

        $url = "https://drive.google.com/file/d/invalid_file_id/view?usp=sharing";

        $isValid = $validator->validate($url);

        $this->assertFalse($isValid);
    }


    public function testGithubValidCase()
    {
        $validator = new RepoLinkValidator();

        $url = "https://github.com/hlum/UnderstandMe-Server-Side.git";

        $isValid = $validator->validate($url);
        $this->assertTrue($isValid);
    }


    public function testGithubInvalidCase()
    {
        $validator = new RepoLinkValidator();

        $url = "https://github.com/hlum/Invalid-Repository.git/extra/path";
        $isValid = $validator->validate($url);
        $this->assertFalse($isValid);
    }


    public function testInvalidBigFileDriveLink()
    {
        $validator = new RepoLinkValidator();

        $url = "https://drive.google.com/file/d/1Tz-0OA4XUg6w1M1MiXvxxgPelwtTxCAi/view?usp=sharing";

        $isValid = $validator->validate($url);

        $this->assertFalse($isValid);
    }
        

}

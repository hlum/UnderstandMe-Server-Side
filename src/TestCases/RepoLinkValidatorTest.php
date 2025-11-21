<?php

use PHPUnit\Framework\TestCase;
use Helpers\RepoLinkValidator;
use Application\CustomExceptions\UnSupportedRepoURL;
use Application\CustomExceptions\UnsupportedFileTypeException;

class RepoLinkValidatorTest extends TestCase
{
    private RepoLinkValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new RepoLinkValidator();
    }

    /**
     * Valid Google Drive ZIP should pass without exception
     */
    public function testValidGoogleDriveZip(): void
    {
        $url = "https://drive.google.com/file/d/188nthRbTF51QhJ2x7eja8NCGcwcYqOyP/view?usp=sharing";

        try {
            $this->validator->validate($url);
            $this->assertTrue(true); // No exception thrown, test passed
        } catch (\Exception $e) {
            $this->fail("Exception thrown for valid Google Drive ZIP: " . $e->getMessage());
        }
    }

    /**
     * Invalid Google Drive ID should throw UnSupportedRepoURL
     */
    public function testInvalidGoogleDriveId(): void
    {
        $url = "https://drive.google.com/file/d/INVALID_ID/view?usp=sharing";

        $this->expectException(UnSupportedRepoURL::class);
        $this->validator->validate($url);
    }

    /**
     * Google Drive file is not ZIP should throw UnsupportedFileTypeException
     */
    public function testGoogleDriveNotZip(): void
    {
        $url = "https://drive.google.com/file/d/1MDsDhNwj61Bg_NKY7xJx8EEizE8cdh8Y/view?usp=sharing";

        $this->expectException(UnsupportedFileTypeException::class);
        $this->validator->validate($url);
    }

    /**
     * Valid GitHub URL should pass without exception
     */
    public function testValidGithubUrl(): void
    {
        $url = "https://github.com/hlum/UnderstandMe-Server-Side.git";

        try {
            $this->validator->validate($url);
            $this->assertTrue(true); // No exception thrown
        } catch (\Exception $e) {
            $this->fail("Exception thrown for valid GitHub URL: " . $e->getMessage());
        }
    }

    /**
     * Invalid GitHub URL should throw UnSupportedRepoURL
     */
    public function testInvalidGithubUrl(): void
    {
        $url = "https://github.com/hlum/UnderstandMe-Server-Side.gi";

        $this->expectException(UnSupportedRepoURL::class);
        $this->validator->validate($url);
    }
}

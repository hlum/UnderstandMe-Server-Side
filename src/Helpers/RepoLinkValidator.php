<?php

namespace Helpers;

class RepoLinkValidator {

    private const GITHUB_PATTERN = '#^https://github\.com/[\w.-]+/[\w.-]+(\.git)?/?$#';
    private const GOOGLE_DRIVE_PATTERN = '#^https://drive\.google\.com/file/d/([\w-]+)(/(view|edit))?(\?.*)?$#';

    /**
     * Repo link 検証
     * Google driveのリンクだったら、ZIPかどうかもチェック。
     *
     * @param string $url
     * @return bool
     */
    public function validate(string $url): bool
    {
        $type = $this->getLinkType($url);

        if ($type === 'google_drive') {
            $fileId = $this->extractGoogleDriveFileId($url);
            if (!$fileId) return false;

            return $this->isGoogleDriveZip($fileId);
        }

        return $type !== 'invalid';
    }



    private function getLinkType(string $url): string
    {
        if ($this->isValidGithubLink($url)) return 'github';
        if ($this->isValidGoogleDriveLink($url)) return 'google_drive';
        return 'invalid';
    }



    private function isValidGithubLink(string $url): bool
    {
        return preg_match(self::GITHUB_PATTERN, $url) === 1;
    }



    private function isValidGoogleDriveLink(string $url): bool
    {
        return preg_match(self::GOOGLE_DRIVE_PATTERN, $url) === 1;
    }



    private function extractGoogleDriveFileId(string $url): ?string
    {
        if (preg_match(self::GOOGLE_DRIVE_PATTERN, $url, $matches)) {
            return $matches[1] ?? null;
        }
        return null;
    }



    /**
     * Google Drive ファイルが ZIP かどうかを判定
     */
    private function isGoogleDriveZip(string $fileId): bool
    {
        $baseUrl = "https://drive.google.com/uc?export=download&id={$fileId}";
        $cookieFile = tempnam(sys_get_temp_dir(), 'gdcookie');

        // Step 1: initial request
        $html = $this->curlGet($baseUrl, $cookieFile);

        // Step 2: check for confirm token (large file / virus scan)
        if (preg_match('/confirm=([0-9A-Za-z_]+)/', $html, $matches)) {
            $token = $matches[1];
            $downloadUrl = $baseUrl . "&confirm={$token}";
        } else {
            $downloadUrl = $baseUrl;
        }

        // Step 3: read first 4 bytes
        $fp = $this->curlInitRange($downloadUrl, $cookieFile, 0, 4);
        $bytes = fread($fp, 4);
        fclose($fp);
        unlink($cookieFile);

        // Step 4: check ZIP magic number
        return $bytes === "\x50\x4B\x03\x04";
    }

    private function curlGet(string $url, string $cookieFile): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_VERBOSE => false,
            CURLOPT_HEADER => false,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    private function curlInitRange(string $url, string $cookieFile, int $start, int $end)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_RANGE => "{$start}-{$end}",
            CURLOPT_FILE => $fp = fopen('php://temp', 'w+'),
            CURLOPT_VERBOSE => false,
            CURLOPT_HEADER => false,
        ]);
        curl_exec($ch);
        curl_close($ch);
        rewind($fp);
        return $fp;
    }
}

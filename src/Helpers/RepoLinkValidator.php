<?php

namespace Helpers;

use Application\CustomExceptions\UnsupportedFileTypeException;
use Application\CustomExceptions\UnSupportedRepoURL;

class RepoLinkValidator {

    private const GITHUB_PATTERN = '#^https://github\.com/[\w.-]+/[\w.-]+(\.git|/)?$#';
    private const GOOGLE_DRIVE_PATTERN = '#^https://drive\.google\.com/file/d/([\w-]+)(/(view|edit))?(\?.*)?$#';

    /**
     * リポジトリリンクの妥当性を検証
     *
     * @param string $url
     * @throws UnSupportedRepoURL URLはGitHubまたはGoogle Driveのいずれかである必要があります
     * @throws UnsupportedFileTypeException Google DriveのファイルがZIPでない場合にスローされます
     */
    public function validate(string $url): void
    {
        $type = $this->getLinkType($url);

        if ($type === 'invalid') {
            throw new UnSupportedRepoURL("This repository URL is not supported.");
        }

        if ($type === 'google_drive') {
            $fileId = $this->extractGoogleDriveFileId($url);
            if (!$fileId) {
                throw new UnSupportedRepoURL("Invalid Google Drive link.");
            }

            // Check if the file is accessible before checking if it's a ZIP
            if (!$this->isGoogleDriveFileAccessible($fileId)) {
                throw new UnSupportedRepoURL("Google Drive file is not accessible or does not exist.");
            }

            if (!$this->isGoogleDriveZip($fileId)) {
                throw new UnsupportedFileTypeException("Google Drive link is not a ZIP file.");
            }
        }
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
     * Check if Google Drive file is accessible
     */
    private function isGoogleDriveFileAccessible(string $fileId): bool
    {
        $url = "https://drive.google.com/uc?export=download&id={$fileId}";
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_NOBODY => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // 200 OK or 302/303 redirect means file exists
        return in_array($httpCode, [200, 302, 303]);
    }

    /**
     * Google DriveのファイルがZIPである場合にtrueを返す
     */
    private function isGoogleDriveZip(string $fileId): bool
    {
        // First, get the initial download page (may show virus scan warning)
        $initialUrl = "https://drive.usercontent.google.com/download?id={$fileId}&export=download";
        
        $ch = curl_init($initialUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return false;
        }
        
        // If we got HTML (virus scan warning), extract UUID and build proper download URL
        if (strpos($contentType, 'text/html') !== false) {
            // Extract UUID from the form
            if (preg_match('/name="uuid"\s+value="([^"]+)"/', $response, $matches)) {
                $uuid = $matches[1];
                $downloadUrl = "https://drive.usercontent.google.com/download?id={$fileId}&export=download&confirm=t&uuid={$uuid}";
                
                return $this->checkZipSignature($downloadUrl);
            }
            return false;
        }
        
        // If direct download (no virus scan warning), check signature directly
        return $this->checkZipSignature($initialUrl);
    }

    /**
     * Check if URL returns ZIP file signature
     */
    private function checkZipSignature(string $url): bool
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_RANGE => "0-3", // Get first 4 bytes
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);
        
        $bytes = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        // Check for successful response (206 Partial Content or 200 OK)
        if (!in_array($httpCode, [200, 206])) {
            return false;
        }
        
        // Make sure we're not getting HTML
        if (strpos($contentType, 'text/html') !== false) {
            return false;
        }
        
        // Check ZIP file signature: PK (0x50 0x4B) followed by 0x03 0x04
        if (strlen($bytes) >= 4) {
            return ord($bytes[0]) === 0x50 && 
                   ord($bytes[1]) === 0x4B && 
                   ord($bytes[2]) === 0x03 && 
                   ord($bytes[3]) === 0x04;
        }
        
        return false;
    }
}
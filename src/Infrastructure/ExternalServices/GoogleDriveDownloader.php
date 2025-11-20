<?php
namespace Infrastructure\ExternalServices;

use Application\CustomExceptions\UnsupportedFileTypeException;
use Application\CustomExceptions\UnSupportedRepoURL;
use Domain\Repositories\RepoDownloader;
use RuntimeException;
use ZipArchive;

class GoogleDriveDownloader implements RepoDownloader
{
    public function download(string $url): string
    {
        $this->validateRepoUrl($url);

        $fileId = $this->extractFileId($url);
        $tempDir = $this->generateTempDir();
        $zipPath = $tempDir . '/download.zip';

        $this->downloadLargeFile($fileId, $zipPath);
        $this->unzipDownloadedFile($zipPath, $tempDir);

        unlink($zipPath); // ZIP 削除

        return $tempDir;
    }

    /**
     * Google Drive の URL から fileId を抽出
     */
    private function extractFileId(string $url): string
    {
        if (preg_match('/\/d\/([^\/]+)/', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('/id=([^&]+)/', $url, $matches)) {
            return $matches[1];
        }

        throw new \InvalidArgumentException("Google Drive file ID を抽出できません: $url");
    }

    /**
     * 🔥 大容量ファイル対応版（warning page 対応）
     */
    private function downloadLargeFile(string $fileId, string $destination): void
    {
        $base = "https://drive.google.com/uc?export=download&id={$fileId}";

        $cookieFile = tempnam(sys_get_temp_dir(), 'gdcookie');

        // 最初のリクエスト（ウイルススキャン警告ページが返ることがある）
        $html = $this->curlGet($base, $cookieFile);

        // warning token（confirm=xxxx）を探す
        if (preg_match('/confirm=([0-9A-Za-z_]+)/', $html, $matches)) {
            $token = $matches[1];
            $downloadUrl = $base . "&confirm={$token}";
        } else {
            // 小さいファイルは最初からダウンロード可能
            $downloadUrl = $base;
        }

        // 実際のファイルダウンロード
        $this->curlDownload($downloadUrl, $destination, $cookieFile);

        unlink($cookieFile);
    }

    /**
     * シンプルな GET with cookie
     */
    protected function curlGet(string $url, string $cookieFile): string
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

    /**
     * 実ファイルを保存
     */
    protected function curlDownload(string $url, string $destination, string $cookieFile): void
    {
        $fp = fopen($destination, 'wb');
        if (!$fp) {
            throw new \RuntimeException("ファイル書き込みに失敗: $destination");
        }

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_BUFFERSIZE => 1024 * 1024, // 1MB/バッファ
            CURLOPT_VERBOSE => false,
            CURLOPT_HEADER => false,
        ]);

        curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \RuntimeException('ダウンロードエラー: ' . curl_error($ch));
        }

        curl_close($ch);
        fclose($fp);
    }

    private function unzipDownloadedFile(string $zipFilePath, string $extractTo): void
    {
        $zip = new ZipArchive();
        $errorCode = $zip->open($zipFilePath);
        
        if ($errorCode !== true) {
            throw new UnsupportedFileTypeException("ZIP ファイルを開けません: $zipFilePath"." (Error code: $errorCode)");
        }

        $zip->extractTo($extractTo);
        $zip->close();
    }

    private function validateRepoUrl(string $url): void
    {
        if (!str_contains($url, "drive.google.com")) {
            throw new UnSupportedRepoURL("無効なGoogle DriveのURL: $url");
        }
    }

    private function generateTempDir(): string
    {
        $tempDir = sys_get_temp_dir() . '/' . uniqid('google_drive_repo_', true);
        if (!mkdir($tempDir) && !is_dir($tempDir)) {
            throw new \RuntimeException("ディレクトリの作成に失敗: $tempDir");
        }
        return $tempDir;
    }
}
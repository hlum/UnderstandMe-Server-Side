<?php

class SafeRequire {
    public static function requireFile(string $filePath): void {
        if (file_exists($filePath)) {
            require_once $filePath;
        } else {
            throw new \RuntimeException("Required file not found: $filePath");
        }
    }
}
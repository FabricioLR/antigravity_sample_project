<?php

namespace App;

use Exception;

class FileManager {
    private string $storageRoot;
    private int $userId;

    public function __construct(int $userId, string $storageRoot = '/var/www/html/storage') {
        $this->userId = $userId;
        $this->storageRoot = rtrim($storageRoot, '/');
        $this->ensureUserDirectory();
    }

    private function getUserDirectory(): string {
        return $this->storageRoot . '/user_' . $this->userId;
    }

    private function ensureUserDirectory(): void {
        $dir = $this->getUserDirectory();
        if (!is_dir($dir)) {
            // Attempt to create, suppress warning if permission denied so it can be handled or displayed gracefully
            @mkdir($dir, 0777, true);
        }
    }

    public function uploadFile(array $file): string {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload failed with error code: " . $file['error']);
        }

        $filename = basename($file['name']);
        // Basic sanitization
        $filename = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $filename);
        
        $destination = $this->getUserDirectory() . '/' . $filename;
        
        // Ensure no overwrite of existing file by adding timestamp if needed, or just throw error
        if (file_exists($destination)) {
            throw new Exception("File already exists.");
        }

        // in tests we might just move it physically or copy
        if (is_uploaded_file($file['tmp_name'])) {
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new Exception("Failed to move uploaded file.");
            }
        } else {
            // For testing without actual HTTP upload
            if (!rename($file['tmp_name'], $destination)) {
                throw new Exception("Failed to move file.");
            }
        }

        return $filename;
    }

    public function listFiles(): array {
        $dir = $this->getUserDirectory();
        
        if (!is_dir($dir)) {
            // Se o diretório não existe (por erro de permissão por exemplo), não falhar fatalmente
            return [];
        }

        $scanned = @scandir($dir);
        if ($scanned === false) {
            return [];
        }

        $files = array_diff($scanned, array('.', '..'));
        $result = [];
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            $result[] = [
                'name' => $file,
                'size' => filesize($path),
                'modified' => @filemtime($path) ?: time() // Fallback time if mtime fails
            ];
        }
        return $result;
    }

    public function deleteFile(string $filename): bool {
        $path = $this->getUserDirectory() . '/' . basename($filename);
        if (file_exists($path) && is_file($path)) {
            return unlink($path);
        }
        throw new Exception("File not found.");
    }

    public function getFilePath(string $filename): string {
        $path = $this->getUserDirectory() . '/' . basename($filename);
        if (!file_exists($path) || !is_file($path)) {
            throw new Exception("File not found.");
        }
        return $path;
    }

    public function getFileContent(string $filename): string {
        $path = $this->getFilePath($filename);
        $content = @file_get_contents($path);
        if ($content === false) {
            throw new Exception("Failed to read file.");
        }
        return $content;
    }

    public function updateFileContent(string $filename, string $content): void {
        $path = $this->getFilePath($filename);
        if (@file_put_contents($path, $content) === false) {
            throw new Exception("Failed to write to file.");
        }
    }
}

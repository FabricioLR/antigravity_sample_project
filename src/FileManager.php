<?php

namespace App;

use App\Storage\StorageInterface;
use Exception;

class FileManager {
    private StorageInterface $storage;
    private int $userId;

    public function __construct(int $userId, StorageInterface $storage) {
        $this->userId = $userId;
        $this->storage = $storage;
        $this->storage->createDirectory($this->getUserPrefix());
    }

    private function getUserPrefix(): string {
        return 'user_' . $this->userId;
    }

    private function getFullContentPath(string $filename): string {
        return $this->getUserPrefix() . '/' . basename($filename);
    }

    public function uploadFile(array $file): string {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload failed with error code: " . $file['error']);
        }

        $filename = basename($file['name']);
        // Basic sanitization
        $filename = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $filename);
        
        $path = $this->getFullContentPath($filename);
        
        if ($this->storage->exists($path)) {
            throw new Exception("File already exists.");
        }

        $content = file_get_contents($file['tmp_name']);
        if ($content === false) {
             throw new Exception("Failed to read uploaded file.");
        }

        $this->storage->put($path, $content);

        return $filename;
    }

    public function listFiles(): array {
        return $this->storage->list($this->getUserPrefix());
    }

    public function deleteFile(string $filename): bool {
        $path = $this->getFullContentPath($filename);
        if ($this->storage->exists($path)) {
            $this->storage->delete($path);
            return true;
        }
        throw new Exception("File not found.");
    }

    public function bulkDelete(array $filenames): array {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];
        foreach ($filenames as $filename) {
            try {
                if ($this->deleteFile($filename)) {
                    $results['success']++;
                }
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][$filename] = $e->getMessage();
            }
        }
        return $results;
    }

    public function renameFile(string $oldName, string $newName): string {
        $oldPath = $this->getFullContentPath($oldName);
        $newName = basename($newName);
        $newName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $newName);
        
        if (empty($newName)) {
            throw new Exception("Invalid new filename.");
        }
        
        $newPath = $this->getUserPrefix() . '/' . $newName;
        if ($this->storage->exists($newPath)) {
            throw new Exception("File with the new name already exists.");
        }
        
        $this->storage->rename($oldPath, $newPath);
        
        return $newName;
    }

    public function getFilePath(string $filename): string {
        $path = $this->getFullContentPath($filename);
        if (!$this->storage->exists($path)) {
            throw new Exception("File not found.");
        }
        return $this->storage->getRealPath($path);
    }

    public function getFileContent(string $filename): string {
        $path = $this->getFullContentPath($filename);
        return $this->storage->get($path);
    }

    public function updateFileContent(string $filename, string $content): void {
        $path = $this->getFullContentPath($filename);
        $this->storage->put($path, $content);
    }

    public function createFile(string $filename): void {
        $filename = basename($filename);
        // Basic sanitization
        $filename = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $filename);
        
        if (empty($filename)) {
            throw new Exception("Nome de arquivo inválido.");
        }
        
        $path = $this->getFullContentPath($filename);
        if ($this->storage->exists($path)) {
            throw new Exception("Arquivo com este nome já existe.");
        }
        
        $this->storage->put($path, '');
    }
}

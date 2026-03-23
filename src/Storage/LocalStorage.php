<?php

namespace App\Storage;

use Exception;

class LocalStorage implements StorageInterface {
    private string $root;

    public function __construct(string $root) {
        $this->root = rtrim($root, '/');
        if (!is_dir($this->root)) {
            @mkdir($this->root, 0777, true);
        }
    }

    private function getFullContentPath(string $path): string {
        return $this->root . '/' . ltrim($path, '/');
    }

    public function put(string $path, string $content): void {
        $fullPath = $this->getFullContentPath($path);
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        if (@file_put_contents($fullPath, $content) === false) {
            throw new Exception("Failed to write to local storage.");
        }
    }

    public function get(string $path): string {
        $fullPath = $this->getFullContentPath($path);
        if (!file_exists($fullPath)) {
            throw new Exception("File not found in local storage.");
        }
        $content = @file_get_contents($fullPath);
        if ($content === false) {
            throw new Exception("Failed to read from local storage.");
        }
        return $content;
    }

    public function delete(string $path): void {
        $fullPath = $this->getFullContentPath($path);
        if (file_exists($fullPath) && is_file($fullPath)) {
            unlink($fullPath);
        }
    }

    public function list(string $prefix = ''): array {
        $dir = $this->getFullContentPath($prefix);
        if (!is_dir($dir)) return [];

        $files = array_diff(scandir($dir), ['.', '..']);
        $result = [];
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_file($path)) {
                $result[] = [
                    'name' => $file,
                    'size' => filesize($path),
                    'modified' => filemtime($path)
                ];
            }
        }
        return $result;
    }

    public function exists(string $path): bool {
        return file_exists($this->getFullContentPath($path));
    }

    public function rename(string $oldPath, string $newPath): void {
        rename($this->getFullContentPath($oldPath), $this->getFullContentPath($newPath));
    }

    public function createDirectory(string $path): void {
        $fullPath = $this->getFullContentPath($path);
        if (!is_dir($fullPath)) {
            @mkdir($fullPath, 0777, true);
        }
    }

    public function getRealPath(string $path): string {
        return $this->getFullContentPath($path);
    }
}

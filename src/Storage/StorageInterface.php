<?php

namespace App\Storage;

interface StorageInterface {
    /**
     * Put content into a file at path
     */
    public function put(string $path, string $content): void;

    /**
     * Get content from a file at path
     */
    public function get(string $path): string;

    /**
     * Delete a file at path
     */
    public function delete(string $path): void;

    /**
     * List files in a directory/prefix
     * Returns array of [name, size, modified]
     */
    public function list(string $prefix = ''): array;

    /**
     * Check if a file exists
     */
    public function exists(string $path): bool;

    /**
     * Rename/Move a file
     */
    public function rename(string $oldPath, string $newPath): void;

    /**
     * Ensure a directory/prefix exists
     */
    public function createDirectory(string $path): void;

    /**
     * Get local file path (for streaming/download if possible)
     */
    public function getRealPath(string $path): string;
}

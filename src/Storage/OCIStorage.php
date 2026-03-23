<?php

namespace App\Storage;

use Exception;
use Oracle\Oci\ObjectStorage\ObjectStorageClient;
use Oracle\Oci\Common\Config\Config;

class OCIStorage implements StorageInterface {
    private ObjectStorageClient $client;
    private string $namespace;
    private string $bucket;

    public function __construct(ObjectStorageClient $client, string $namespace, string $bucket) {
        $this->client = $client;
        $this->namespace = $namespace;
        $this->bucket = $bucket;
    }

    public function put(string $path, string $content): void {
        try {
            $this->client->putObject([
                'NamespaceName' => $this->namespace,
                'BucketName' => $this->bucket,
                'ObjectName' => ltrim($path, '/'),
                'PutObjectBody' => $content
            ]);
        } catch (Exception $e) {
            throw new Exception("OCI Upload Failed: " . $e->getMessage());
        }
    }

    public function get(string $path): string {
        try {
            $response = $this->client->getObject([
                'NamespaceName' => $this->namespace,
                'BucketName' => $this->bucket,
                'ObjectName' => ltrim($path, '/')
            ]);
            return (string)$response->getBody();
        } catch (Exception $e) {
            throw new Exception("OCI Download Failed: " . $e->getMessage());
        }
    }

    public function delete(string $path): void {
        try {
            $this->client->deleteObject([
                'NamespaceName' => $this->namespace,
                'BucketName' => $this->bucket,
                'ObjectName' => ltrim($path, '/')
            ]);
        } catch (Exception $e) {
            // Ignore if not found during delete
        }
    }

    public function list(string $prefix = ''): array {
        try {
            $response = $this->client->listObjects([
                'NamespaceName' => $this->namespace,
                'BucketName' => $this->bucket,
                'Prefix' => ltrim($prefix, '/') . '/',
                'Fields' => 'name,size,timeCreated'
            ]);

            $data = $response->getJson();
            $objects = $data['objects'] ?? [];
            $result = [];
            foreach ($objects as $obj) {
                $name = $obj['name'];
                // Strip prefix
                $name = str_replace(ltrim($prefix, '/') . '/', '', $name);
                if (empty($name)) continue;

                $result[] = [
                    'name' => $name,
                    'size' => $obj['size'],
                    'modified' => strtotime($obj['timeCreated'])
                ];
            }
            return $result;
        } catch (Exception $e) {
            return [];
        }
    }

    public function exists(string $path): bool {
        try {
            $this->client->headObject([
                'NamespaceName' => $this->namespace,
                'BucketName' => $this->bucket,
                'ObjectName' => ltrim($path, '/')
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function rename(string $oldPath, string $newPath): void {
        // OCI doesn't have rename, must copy and delete
        try {
            $this->client->copyObject([
                'NamespaceName' => $this->namespace,
                'BucketName' => $this->bucket,
                'CopyObjectDetails' => [
                    'sourceObjectName' => ltrim($oldPath, '/'),
                    'destinationObjectName' => ltrim($newPath, '/'),
                    'destinationBucket' => $this->bucket,
                    'destinationNamespace' => $this->namespace,
                    'destinationRegion' => $this->client->getRegion()
                ]
            ]);
            $this->delete($oldPath);
        } catch (Exception $e) {
            throw new Exception("OCI Rename Failed: " . $e->getMessage());
        }
    }

    public function createDirectory(string $path): void {
        // No-op for OCI Object Storage as directories are virtual prefixes
    }

    public function getRealPath(string $path): string {
        // Return a temp URL or localized path? For now, we'll download to a temp file if needed by the consumer.
        return "oci://" . $this->bucket . "/" . ltrim($path, '/');
    }
}

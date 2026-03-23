<?php

namespace App\Storage;

use App\Config\OCIConfig;

class StorageFactory {
    public static function create(): StorageInterface {
        $storageType = getenv('STORAGE_TYPE') ?: 'local';

        if ($storageType === 'oci') {
            return new OCIStorage(
                OCIConfig::createClient(),
                OCIConfig::getNamespace(),
                OCIConfig::getBucket()
            );
        }

        $storageRoot = getenv('STORAGE_ROOT') ?: '/var/www/html/storage';
        return new LocalStorage($storageRoot);
    }
}

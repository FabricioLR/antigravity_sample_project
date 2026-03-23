<?php

namespace App\Config;

use Oracle\Oci\ObjectStorage\ObjectStorageClient;
use Oracle\Oci\Common\Config\Config;

class OCIConfig {
    public static function createClient(): ObjectStorageClient {
        // Load from environment or home config
        $config = new Config([
            'user' => getenv('OCI_USER_OCID'),
            'tenancy' => getenv('OCI_TENANCY_OCID'),
            'fingerprint' => getenv('OCI_FINGERPRINT'),
            'key_file' => getenv('OCI_KEY_FILE'),
            'region' => getenv('OCI_REGION')
        ]);

        return new ObjectStorageClient($config);
    }

    public static function getNamespace(): string {
        return getenv('OCI_NAMESPACE');
    }

    public static function getBucket(): string {
        return getenv('OCI_BUCKET');
    }
}

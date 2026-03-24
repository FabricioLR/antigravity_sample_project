<?php

namespace App\Config;

use Oracle\Oci\ObjectStorage\ObjectStorageClient;
use Oracle\Oci\Common\Auth\UserAuthProvider;

class OCIConfig {
    public static function createClient(): ObjectStorageClient {
        $keyFile = getenv('OCI_KEY_FILE');
        if (strpos($keyFile, 'file://') !== 0) {
            $keyFile = 'file://' . $keyFile;
        }

        $authProvider = new UserAuthProvider(
            getenv('OCI_TENANCY_OCID'),
            getenv('OCI_USER_OCID'),
            getenv('OCI_FINGERPRINT'),
            $keyFile
        );

        return new ObjectStorageClient($authProvider, getenv('OCI_REGION'));
    }

    public static function getNamespace(): string {
        return getenv('OCI_NAMESPACE');
    }

    public static function getBucket(): string {
        return getenv('OCI_BUCKET');
    }
}

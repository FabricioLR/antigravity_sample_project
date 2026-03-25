<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\ShareManager;
use App\FileManager;
use App\Storage\StorageFactory;

$uuid = $_GET['uuid'] ?? '';

// Handle friendly URLs if rewrite is active
if (empty($uuid)) {
    // Check if we have a path info like /uuid
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    if (!empty($pathInfo)) {
        $uuid = trim($pathInfo, '/');
    }
}

if (empty($uuid)) {
    http_response_code(400);
    echo "<h1>400 - UUID é obrigatório</h1>";
    exit;
}

try {
    $db = new Database();
    $shareManager = new ShareManager($db);
    $share = $shareManager->getShare($uuid);

    if (!$share) {
        http_response_code(404);
        echo "<h1>404 - Link não encontrado ou expirado</h1>";
        exit;
    }

    $storage = StorageFactory::create();
    $fileManager = new FileManager($share['user_id'], $storage);

    $filename = $share['filename'];
    
    try {
        $content = $fileManager->getFileContent($filename);
    } catch (Exception $e) {
        http_response_code(404);
        echo "<h1>404 - Arquivo original não encontrado</h1>";
        exit;
    }

    // Tentar detectar o tipo MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = @$finfo->buffer($content) ?: 'application/octet-stream';

    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: inline; filename="' . basename($filename) . '"');
    header('Content-Length: ' . strlen($content));
    echo $content;

} catch (Exception $e) {
    http_response_code(500);
    echo "<h1>500 - Erro Interno do Servidor</h1>";
    if (ini_get('display_errors')) {
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

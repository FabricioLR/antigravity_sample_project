<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\Config\Database;
use App\FileManager;

session_start();

$db = new Database();
$auth = new Auth($db);
if (!$auth->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

$userId = $auth->getCurrentUserId();
$storage = \App\Storage\StorageFactory::create();
$fileManager = new FileManager($userId, $storage);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filename = $_POST['file'] ?? '';

    if (empty($filename)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Nome do arquivo é obrigatório.']);
        exit;
    }

    if (empty($_FILES['newFile']['tmp_name'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Um novo arquivo é obrigatório.']);
        exit;
    }

    if ($_FILES['newFile']['name'] != $filename){
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Nome do arquivo é diferente do original.']);
        exit;
    }

    try {
        $filename = $fileManager->uploadFile($_FILES['newFile'], true);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Erro ao substituir arquivo: ' . $e->getMessage()]);
    }
    exit;
}

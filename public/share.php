<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\Config\Database;
use App\ShareManager;

session_start();

$db = new Database();
$auth = new Auth($db);
if (!$auth->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filename = $_POST['file'] ?? '';
    $duration = $_POST['duration'] ?? 'forever';

    if (empty($filename)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Nome do arquivo é obrigatório.']);
        exit;
    }

    try {
        $db = new Database();
        $shareManager = new ShareManager($db);
        $uuid = $shareManager->createShare($_SESSION['user_id'], $filename, $duration);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'uuid' => $uuid]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Erro ao criar compartilhamento: ' . $e->getMessage()]);
    }
}

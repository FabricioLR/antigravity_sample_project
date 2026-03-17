<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\Database;
use App\Auth;
use App\FileManager;

session_start();

$db = new Database();
$auth = new Auth($db);

if (!$auth->isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $auth->logout();
    header('Location: /index.php');
    exit;
}

$userId = $auth->getCurrentUserId();
$fileManager = new FileManager($userId);

$error = '';
$success = '';

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    try {
        $filename = $fileManager->uploadFile($_FILES['file']);
        $success = "Arquivo '$filename' enviado com sucesso!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle File Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['file'])) {
    try {
        $fileManager->deleteFile($_GET['file']);
        $success = "Arquivo apagado com sucesso!";
        header("Location: /dashboard.php?success=" . urlencode($success));
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle File Download (Streaming through PHP)
if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['file'])) {
    try {
        $path = $fileManager->getFilePath($_GET['file']);
        
        // Clear buffers
        if (ob_get_length()) ob_end_clean();
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        
        readfile($path);
        exit;
    } catch (Exception $e) {
        $error = "Falha ao baixar arquivo: " . $e->getMessage();
    }
}

$files = $fileManager->listFiles();
$successMsg = $_GET['success'] ?? $success;

function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow]; 
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Web Storage</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar glass-panel" style="margin: 1rem; border-radius: 12px;">
        <h2 class="text-gradient">Web Storage</h2>
        <div class="nav-links">
            <span style="color: var(--text-muted); margin-right: 1rem;">Olá, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="/dashboard.php" class="active">Meus Arquivos</a>
            <?php if ($auth->isAdmin()): ?>
                <a href="/admin.php">Admin Panel</a>
            <?php endif; ?>
            <a href="/dashboard.php?action=logout" class="btn-logout">Sair</a>
        </div>
    </nav>

    <div class="container">
        
        <div class="page-header">
            <div>
                <h1>Meus Arquivos</h1>
                <p style="color: var(--text-muted); margin-top: 0.5rem;">Gerencie seus documentos em um ambiente seguro</p>
            </div>
            
            <form action="/dashboard.php" method="POST" enctype="multipart/form-data" style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="file" name="file" id="file" required style="display: none;" onchange="this.form.submit()">
                <label for="file" class="btn btn-primary" style="margin: 0; cursor: pointer;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Fazer Upload
                </label>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($successMsg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>

        <div class="glass-panel" style="overflow: hidden;">
            <?php if (empty($files)): ?>
                <div class="empty-state">
                    <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity: 0.5; margin-bottom: 1rem;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <h3>Nenhum arquivo encontrado</h3>
                    <p style="margin-top: 0.5rem;">Faça upload do seu primeiro arquivo para começar.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nome do Arquivo</th>
                            <th>Tamanho</th>
                            <th>Modificado Em</th>
                            <th style="text-align: right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $file): ?>
                            <tr>
                                <td style="font-weight: 500;">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align: sub; margin-right: 0.5rem; color: var(--text-muted);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    <?= htmlspecialchars($file['name']) ?>
                                </td>
                                <td><?= formatBytes($file['size']) ?></td>
                                <td><?= date('d/m/Y H:i', $file['modified']) ?></td>
                                <td style="text-align: right; display: flex; justify-content: flex-end; gap: 0.5rem;">
                                    <a href="/dashboard.php?action=download&file=<?= urlencode($file['name']) ?>" class="btn" style="background: rgba(255,255,255,0.1); color: var(--text-main); padding: 0.4rem 0.8rem;">
                                        Baixar
                                    </a>
                                    <a href="/dashboard.php?action=delete&file=<?= urlencode($file['name']) ?>" class="btn btn-danger" style="padding: 0.4rem 0.8rem;" onclick="return confirm('Tem certeza que deseja apagar este arquivo?');">
                                        Apagar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

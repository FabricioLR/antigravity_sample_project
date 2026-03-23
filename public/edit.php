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

if ($auth->mustChangePassword()) {
    header('Location: /change_password.php');
    exit;
}

$userId = $auth->getCurrentUserId();
$fileManager = new FileManager($userId);

if (!isset($_GET['file']) || empty($_GET['file'])) {
    header('Location: /dashboard.php');
    exit;
}

$filename = $_GET['file'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    try {
        $fileManager->updateFileContent($filename, $_POST['content']);
        $successMsg = "Arquivo '$filename' editado com sucesso!";
        header("Location: /dashboard.php?success=" . urlencode($successMsg));
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

try {
    $content = $fileManager->getFileContent($filename);
} catch (Exception $e) {
    header("Location: /dashboard.php?error=" . urlencode("Erro ao abrir o arquivo: " . $e->getMessage()));
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar <?= htmlspecialchars($filename) ?> - Web Storage</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/edit.css">
</head>
<body>
    <nav class="navbar glass-panel edit-nav">
        <a href="/dashboard.php" class="nav-logo-link"><h2 class="text-gradient nav-logo-text">Web Storage</h2></a>
        <div class="nav-links nav-links-override">
            <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-icon" onclick="toggleDropdown()">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <div class="dropdown-menu">
                    <a href="/dashboard.php" class="dropdown-item dropdown-item-override">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                        Meus Arquivos
                    </a>
                    <?php if (isset($auth) && $auth->isAdmin()): ?>
                    <a href="/admin.php" class="dropdown-item dropdown-item-override">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Painel Admin
                    </a>
                    <?php endif; ?>
                    <a href="/change_password.php" class="dropdown-item dropdown-item-override">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                        Mudar Senha
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="/dashboard.php?action=logout" class="dropdown-item danger dropdown-item-override">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container edit-container">
        <?php if ($error): ?>
            <div class="alert alert-error alert-margin"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="/edit.php?file=<?= urlencode($filename) ?>" method="POST" class="editor-form">
            
            <div class="editor-header">
                <div class="editor-title-box">
                    <svg width="22" height="22" fill="none" stroke="var(--primary)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span class="editor-title-text"><?= htmlspecialchars($filename) ?></span>
                </div>
                
                <div class="editor-actions">
                    <?php 
                    $isNew = isset($_GET['new']) && $_GET['new'] === '1';
                    $cancelUrl = $isNew ? "/dashboard.php?action=delete&file=" . urlencode($filename) : "/dashboard.php";
                    ?>
                    <a href="<?= $cancelUrl ?>" class="btn btn-cancel">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary btn-save">
                        Salvar Alterações
                    </button>
                </div>
            </div>

            <textarea name="content" spellcheck="false" class="editor-textarea"><?= htmlspecialchars($content) ?></textarea>
            
        </form>
    </div>

    <script src="/js/edit.js"></script>
</body>
</html>

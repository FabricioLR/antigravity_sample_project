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
    <title>Editar Arquivo - Web Storage</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .editor-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            height: 60vh;
        }
        .code-editor {
            flex-grow: 1;
            width: 100%;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.85); /* kept dark editor but darker */
            color: #f8f8f2;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
            resize: vertical;
        }
        .code-editor:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar glass-panel" style="margin: 1rem; border-radius: 12px;">
        <h2 class="text-gradient">Web Storage</h2>
        <div class="nav-links">
            <span style="color: var(--text-muted); margin-right: 1rem;">Olá, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="/dashboard.php">Meus Arquivos</a>
            <a href="/dashboard.php?action=logout" class="btn-logout">Sair</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div>
                <h1>Editar Arquivo: <span style="color: var(--primary-color);"><?= htmlspecialchars($filename) ?></span></h1>
            </div>
            <a href="/dashboard.php" class="btn" style="background: rgba(0,0,0,0.06); color: var(--text-main);">
                Voltar
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="/edit.php?file=<?= urlencode($filename) ?>" method="POST" class="glass-panel editor-container">
            <textarea name="content" class="code-editor" spellcheck="false"><?= htmlspecialchars($content) ?></textarea>
            
            <div class="action-buttons">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align: sub;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    Salvar Arquivo
                </button>
                <a href="/dashboard.php" class="btn btn-logout" style="flex: 1; text-align: center; text-decoration: none;">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</body>
</html>

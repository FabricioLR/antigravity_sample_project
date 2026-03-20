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
    <title>Editar <?= htmlspecialchars($filename) ?> - Web Storage</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar glass-panel" style="width: 100%; margin: 0; border-radius: 0; border-left: none; border-right: none; border-top: none; padding: 1rem 2rem;">
        <h2 class="text-gradient">Web Storage</h2>
        <div class="nav-links" style="display: flex; align-items: center; gap: 1rem;">
            <?php if ($auth->isAdmin()): ?>
                <a href="/admin.php" style="margin: 0;">Admin Panel</a>
            <?php endif; ?>
            <a href="/dashboard.php" style="margin: 0;">Meus Arquivos</a>
            <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-icon" onclick="toggleDropdown()">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <div class="dropdown-menu">
                    <a href="/change_password.php" class="dropdown-item" style="margin: 0;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                        Mudar Senha
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="/dashboard.php?action=logout" class="dropdown-item danger" style="margin: 0;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container" style="max-width: 95%;">
        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-top: 1rem;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="/edit.php?file=<?= urlencode($filename) ?>" method="POST" style="background: #ffffff; border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; display: flex; flex-direction: column; height: 75vh; margin-top: 1rem;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1.5rem; background: var(--bg-base); border-bottom: 1px solid var(--border);">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <svg width="22" height="22" fill="none" stroke="var(--primary)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span style="font-weight: 600; color: var(--text-main); font-size: 1.05rem;"><?= htmlspecialchars($filename) ?></span>
                </div>
                
                <div style="display: flex; gap: 0.5rem;">
                    <a href="/dashboard.php" class="btn" style="background: rgba(0,0,0,0.05); color: var(--text-main); font-size: 0.85rem; padding: 0.5rem 1rem;">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary" style="font-size: 0.85rem; padding: 0.5rem 1.25rem;">
                        Salvar Alterações
                    </button>
                </div>
            </div>

            <textarea name="content" spellcheck="false" style="flex: 1; width: 100%; border: none; padding: 1.5rem; font-family: 'Courier New', Courier, monospace; font-size: 14px; line-height: 1.6; background: #151515; color: #ffffff; resize: none; outline: none; transition: background 0.3s;"><?= htmlspecialchars($content) ?></textarea>
            
        </form>
    </div>

    <script>
        function toggleDropdown() {
            document.getElementById('profileDropdown').classList.toggle('active');
        }

        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown && !dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
    </script>
</body>
</html>

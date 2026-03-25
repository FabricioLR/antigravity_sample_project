<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\Database;
use App\Auth;
use App\ShareManager;

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
$shareManager = new ShareManager($db);

// Handle Revocation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'revoke' && isset($_POST['uuid'])) {
    if ($shareManager->deleteShare($_POST['uuid'], $userId)) {
        $_SESSION['success'] = "Compartilhamento revogado com sucesso.";
    } else {
        $_SESSION['error'] = "Falha ao revogar compartilhamento.";
    }
    header("Location: /shares.php");
    exit;
}

$shares = $shareManager->listShares($userId);
$successMsg = $_SESSION['success'] ?? '';
$errorMsg = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$shareBaseUrl = getenv('SHARE_BASE_URL') ?: '';

function formatExpiry($date) {
    if (!$date) return 'Nunca';
    return date('d/m/Y H:i', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Compartilhamentos - Web Storage</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/dashboard.css">
    <script>
        window.SHARE_BASE_URL = "<?= htmlspecialchars($shareBaseUrl) ?>";
    </script>
</head>
<body>
    <nav class="navbar glass-panel dashboard-nav">
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
                    <a href="/shares.php" class="dropdown-item dropdown-item-override active" style="background: rgba(255,255,255,0.05);">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                        Meus Compartilhamentos
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

    <div class="container">
        <div class="page-header">
            <div>
                <h1>Meus Compartilhamentos</h1>
                <p class="page-subtitle">Gerencie os links públicos que você criou</p>
            </div>
        </div>

        <?php if ($errorMsg): ?>
            <div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>
        <?php if ($successMsg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>

        <div class="glass-panel glass-panel-override">
            <?php if (empty($shares)): ?>
                <div class="empty-state">
                    <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="empty-icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                    <h3>Nenhum compartilhamento ativo</h3>
                    <p class="empty-text">Vá para seus arquivos e clique em "Compartilhar" para criar um link.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Arquivo</th>
                                <th>Expira em</th>
                                <th>Criado em</th>
                                <th style="text-align: right;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shares as $share): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                            <?= htmlspecialchars($share['filename']) ?>
                                        </div>
                                    </td>
                                    <td><?= formatExpiry($share['expires_at']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($share['created_at'])) ?></td>
                                    <td style="text-align: right;">
                                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                            <button class="btn btn-primary btn-sm" onclick="copyShareLinkList('<?= $share['uuid'] ?>')">Copiar Link</button>
                                            <form method="POST" action="/shares.php" onsubmit="return confirm('Tem certeza que deseja revogar este compartilhamento?')">
                                                <input type="hidden" name="action" value="revoke">
                                                <input type="hidden" name="uuid" value="<?= htmlspecialchars($share['uuid']) ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Revogar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="/js/dashboard.js"></script>
    <script>
        function copyShareLinkList(uuid) {
            let shareUrl;
            if (window.SHARE_BASE_URL && window.SHARE_BASE_URL.trim() !== '') {
                shareUrl = window.SHARE_BASE_URL.replace(/\/$/, '') + '/' + uuid;
            } else {
                const protocol = window.location.protocol;
                const host = window.location.host;
                shareUrl = `${protocol}//${host}/${uuid}`;
            }

            navigator.clipboard.writeText(shareUrl).then(() => {
                alert('Link copiado para a área de transferência!');
            });
        }
    </script>
</body>
</html>

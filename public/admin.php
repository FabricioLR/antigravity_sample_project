<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\Database;
use App\Auth;
use App\UserManager;

session_start();

$db = new Database();
$auth = new Auth($db);

if (!$auth->isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

if (!$auth->isAdmin()) {
    header('HTTP/1.0 403 Forbidden');
    echo "<h1>403 Forbidden</h1><p>Acesso negado. Apenas administradores podem ver esta página.</p>";
    exit;
}

$userManager = new UserManager($db);

$error = '';
$success = '';

// Handle add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $newUsername = $_POST['username'] ?? '';
    $newPassword = $_POST['password'] ?? '';
    $newRole = $_POST['role'] ?? 'user';
    
    try {
        if (strlen($newUsername) < 3 || strlen($newPassword) < 3) {
            throw new Exception("Usuário e senha devem ter pelo menos 3 caracteres.");
        }
        $userManager->addUser($newUsername, $newPassword, $newRole);
        $success = "Usuário '$newUsername' criado com sucesso!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle rem user
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $idToRemove = (int) $_GET['id'];
        if ($idToRemove === $auth->getCurrentUserId()) {
            throw new Exception("Não é possível remover a si próprio.");
        }
        $userManager->removeUser($idToRemove);
        $success = "Usuário removido com sucesso!";
        header("Location: /admin.php?success=" . urlencode($success));
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$users = $userManager->listUsers();
$successMsg = $_GET['success'] ?? $success;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Web Storage</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar glass-panel" style="width: 100%; margin: 0; border-radius: 0; border-left: none; border-right: none; border-top: none; padding: 1rem 2rem;">
        <a href="/dashboard.php" style="text-decoration: none;"><h2 class="text-gradient" style="margin: 0;">Web Storage</h2></a>
        <div class="nav-links" style="display: flex; align-items: center; gap: 1rem;">
            <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-icon" onclick="toggleDropdown()">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <div class="dropdown-menu">
                    <a href="/dashboard.php" class="dropdown-item" style="margin: 0;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                        Meus Arquivos
                    </a>
                    <?php if (isset($auth) && $auth->isAdmin()): ?>
                    <a href="/admin.php" class="dropdown-item" style="margin: 0;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Painel Admin
                    </a>
                    <?php endif; ?>
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

    <div class="container">
        
        <div class="page-header">
            <div>
                <h1>Gestão de Usuários</h1>
                <p style="color: var(--text-muted); margin-top: 0.5rem;">Adicione ou remova acesso à plataforma</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($successMsg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
            <!-- Tabela -->
            <div class="glass-panel" style="overflow: hidden;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuário</th>
                            <th>Função</th>
                            <th>Criado em</th>
                            <th style="text-align: right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($user['id']) ?></td>
                                <td style="font-weight: 500;">
                                    <?= htmlspecialchars($user['username']) ?>
                                    <?php if ($user['id'] === $auth->getCurrentUserId()): ?>
                                        <span style="font-size: 0.75rem; background: var(--primary); padding: 0.1rem 0.4rem; border-radius: 4px; margin-left: 0.5rem;">Você</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-size: 0.8rem; padding: 0.2rem 0.5rem; border-radius: 4px; background: rgba(0,0,0,0.06); color: var(--text-muted);">
                                        <?= strtoupper(htmlspecialchars($user['role'])) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                <td style="text-align: right;">
                                    <?php if ($user['id'] !== $auth->getCurrentUserId()): ?>
                                        <a href="/admin.php?action=delete&id=<?= urlencode($user['id']) ?>" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;" onclick="return confirm('Eliminar o acesso deste usuário para sempre?');">
                                            Revogar Acesso
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.85rem; padding-right: 1rem;">Admin</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Adicionar Usuário Form -->
            <div class="glass-panel" style="padding: 1.5rem;">
                <h3 style="margin-bottom: 1.5rem;">Novo Usuário</h3>
                <form method="POST" action="/admin.php">
                    <input type="hidden" name="action" value="add_user">
                    
                    <div class="form-group">
                        <label for="username">Nome de Usuário</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Senha Inicial</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Permissão</label>
                        <select id="role" name="role">
                            <option value="user">Usuário Comum</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1rem;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 0.25rem;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Criar Usuário
                    </button>
                </form>
            </div>
        </div>

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

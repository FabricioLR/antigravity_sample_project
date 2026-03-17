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
    <nav class="navbar glass-panel" style="margin: 1rem; border-radius: 12px;">
        <h2 class="text-gradient">Web Storage Admin</h2>
        <div class="nav-links">
            <a href="/dashboard.php">Meus Arquivos</a>
            <a href="/admin.php" class="active">Admin Panel</a>
            <a href="/dashboard.php?action=logout" class="btn-logout">Sair</a>
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
                                    <span style="font-size: 0.8rem; padding: 0.2rem 0.5rem; border-radius: 4px; background: rgba(255,255,255,0.1); color: var(--text-muted);">
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
</body>
</html>

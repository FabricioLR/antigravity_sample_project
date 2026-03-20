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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "Todos os campos são obrigatórios.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "A nova senha e a confirmação não coincidem.";
    } else {
        try {
            $userManager = new UserManager($db);
            $userId = $auth->getCurrentUserId();
            $userManager->changePassword($userId, $currentPassword, $newPassword);
            $success = "Senha alterada com sucesso!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mudar Senha - Web Storage</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar glass-panel" style="width: 100%; margin: 0; border-radius: 0; border-left: none; border-right: none; border-top: none; padding: 1rem 2rem;">
        <a href="/dashboard.php" style="text-decoration: none;"><h2 class="text-gradient" style="margin: 0;">Web Storage</h2></a>
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

    <div class="auth-container">
        <div class="auth-box glass-panel" style="background: #ffffff;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <svg width="48" height="48" fill="none" stroke="var(--primary)" viewBox="0 0 24 24" style="margin-bottom: 1rem;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                <h2>Mudar Senha</h2>
                <p style="color: var(--text-muted); margin-top: 0.5rem;">Crie uma nova senha segura.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <script>
                    setTimeout(() => {
                        window.location.href = '/dashboard.php';
                    }, 2000);
                </script>
            <?php endif; ?>

            <form method="POST" action="/change_password.php">
                <div class="form-group">
                    <label for="current_password">Senha Atual</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">Nova Senha</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar Nova Senha</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1rem;">
                    Atualizar Senha
                </button>
            </form>
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

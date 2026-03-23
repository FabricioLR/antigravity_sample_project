<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\Database;
use App\Auth;

session_start();

$db = new Database();
$auth = new Auth($db);

$error = '';

if ($auth->isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($auth->login($username, $password)) {
        header('Location: /dashboard.php');
        exit;
    } else {
        $error = 'Credenciais inválidas. Tente novamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Web Storage</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box glass-panel">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h1 class="text-gradient" style="font-size: 2rem;">Web Storage</h1>
                <p style="color: var(--text-muted); margin-top: 0.5rem;">Acesse seus arquivos na nuvem</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="/index.php">
                <div class="form-group">
                    <label for="username">Usuário</label>
                    <input type="text" id="username" name="username" required autofocus placeholder="Digite seu usuário">
                </div>
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required placeholder="Digite sua senha">
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1rem;">
                    Entrar no Sistema
                </button>
            </form>
        </div>
    </div>
</body>
</html>

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

// Handle Bulk Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_delete' && isset($_POST['files'])) {
    try {
        $filesToDelete = json_decode($_POST['files'], true);
        if (is_array($filesToDelete) && !empty($filesToDelete)) {
            $result = $fileManager->bulkDelete($filesToDelete);
            $success = $result['success'] . " arquivo(s) apagado(s) com sucesso.";
            if ($result['failed'] > 0) {
                $error .= $result['failed'] . " arquivo(s) falharam em ser apagados. ";
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle Rename
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rename') {
    $oldName = $_POST['old_name'] ?? '';
    $newName = $_POST['new_name'] ?? '';
    if (!empty($oldName) && !empty($newName)) {
        try {
            $renamed = $fileManager->renameFile($oldName, $newName);
            $success = "Arquivo '$oldName' renomeado para '$renamed' com sucesso!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = "Nome de arquivo inválido.";
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
$errorMsg = $_GET['error'] ?? $error;

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
    <link rel="stylesheet" href="/css/dashboard.css">
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
                <h1>Meus Arquivos</h1>
                <p class="page-subtitle">Gerencie seus documentos em um ambiente seguro</p>
            </div>
            
            <form action="/dashboard.php" method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="file" name="file" id="file" required class="hidden-input" onchange="this.form.submit()">
                <label for="file" class="btn btn-primary upload-label">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Fazer Upload
                </label>
            </form>
        </div>

        <?php if ($errorMsg): ?>
            <div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>
        <?php if ($successMsg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>

        <div class="glass-panel glass-panel-override">
            <?php if (empty($files)): ?>
                <div class="empty-state">
                    <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="empty-icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <h3>Nenhum arquivo encontrado</h3>
                    <p class="empty-text">Faça upload do seu primeiro arquivo para começar.</p>
                </div>
            <?php else: ?>
                <!-- Bulk Delete Form -->
                <form id="bulkActionForm" method="POST" action="/dashboard.php" style="display: none;">
                    <input type="hidden" name="action" value="bulk_delete">
                    <input type="hidden" name="files" id="bulkFilesInput" value="">
                </form>

                <!-- Rename Form -->
                <form id="renameActionForm" method="POST" action="/dashboard.php" style="display: none;">
                    <input type="hidden" name="action" value="rename">
                    <input type="hidden" name="old_name" id="renameOldInput" value="">
                    <input type="hidden" name="new_name" id="renameNewInput" value="">
                </form>

                <div class="action-bar-top glass-panel action-bar-override" id="topActionBar">
                    <button class="btn action-btn action-btn-rename" id="btnRename" onclick="renameSelected()">
                        Renomear
                    </button>
                    <button class="btn btn-primary action-btn action-btn-edit" id="btnEdit" onclick="editSelected()">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> Editar
                    </button>
                    <button class="btn action-btn action-btn-download" id="btnDownload" onclick="downloadSelected()">
                        Baixar
                    </button>
                    <button class="btn btn-danger action-btn action-btn-delete" id="btnDelete" onclick="deleteSelected()">
                        Apagar
                    </button>
                    <span class="selection-count" id="selectionCount">0 itens selecionados</span>
                </div>

                <table class="data-table file-explorer-table">
                    <thead>
                        <tr>
                            <th class="th-checkbox">
                                <input type="checkbox" id="selectAllCheckbox" onclick="toggleAllFiles(this)" class="cursor-pointer">
                            </th>
                            <th>Nome</th>
                            <th>Tamanho</th>
                            <th>Modificado em</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $file): ?>
                            <?php 
                            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                            $textExtensions = ['txt', 'json', 'md', 'csv', 'log', 'xml', 'yml', 'yaml', 'php', 'html', 'css', 'js'];
                            $isEditable = in_array($ext, $textExtensions) ? 'true' : 'false';
                            ?>
                            <tr class="file-row cursor-pointer" onclick="toggleFileRow(this, event)" data-filename="<?= htmlspecialchars($file['name']) ?>" data-editable="<?= $isEditable ?>">
                                <td class="td-checkbox">
                                    <input type="checkbox" class="file-checkbox cursor-pointer" onclick="toggleFileCheckbox(this, event)">
                                </td>
                                <td class="filename-cell">
                                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="file-icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    <span class="file-name"><?= htmlspecialchars($file['name']) ?></span>
                                </td>
                                <td><?= formatBytes($file['size']) ?></td>
                                <td><?= date('d/m/Y H:i', $file['modified']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <script src="/js/dashboard.js"></script>
</body>
</html>

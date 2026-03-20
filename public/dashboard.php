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
</head>
<body>
    <nav class="navbar glass-panel" style="width: 100%; margin: 0; border-radius: 0; border-left: none; border-right: none; border-top: none; padding: 1rem 2rem;">
        <h2 class="text-gradient">Web Storage</h2>
        <div class="nav-links" style="display: flex; align-items: center; gap: 1rem;">
            <?php if ($auth->isAdmin()): ?>
                <a href="/admin.php" style="margin: 0;">Admin Panel</a>
            <?php endif; ?>
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

        <?php if ($errorMsg): ?>
            <div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
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

                <div class="action-bar-top glass-panel" id="topActionBar" style="display: flex; min-height: 60px; gap: 0.5rem; padding: 0.75rem 1rem; border-radius: 0; border-top: 0; border-left: 0; border-right: 0; align-items: center; border-bottom: 1px solid var(--border);">
                    <button class="btn action-btn" id="btnRename" style="display: none; background: rgba(0,0,0,0.05); color: var(--text-main); padding: 0.4rem 0.8rem;" onclick="renameSelected()">
                        Renomear
                    </button>
                    <button class="btn btn-primary action-btn" id="btnEdit" onclick="editSelected()" style="display: none; padding: 0.4rem 0.8rem;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> Editar
                    </button>
                    <button class="btn action-btn" id="btnDownload" style="display: none; background: rgba(0,0,0,0.05); color: var(--text-main); padding: 0.4rem 0.8rem;" onclick="downloadSelected()">
                        Baixar
                    </button>
                    <button class="btn btn-danger action-btn" id="btnDelete" style="display: none; padding: 0.4rem 0.8rem;" onclick="deleteSelected()">
                        Apagar
                    </button>
                    <span style="margin-left: auto; color: var(--text-muted); font-size: 0.9rem;" id="selectionCount">0 itens selecionados</span>
                </div>

                <table class="data-table file-explorer-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">
                                <input type="checkbox" id="selectAllCheckbox" onclick="toggleAllFiles(this)" style="cursor: pointer;">
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
                            <tr class="file-row" onclick="toggleFileRow(this, event)" data-filename="<?= htmlspecialchars($file['name']) ?>" data-editable="<?= $isEditable ?>">
                                <td style="text-align: center;">
                                    <input type="checkbox" class="file-checkbox" onclick="toggleFileCheckbox(this, event)" style="cursor: pointer;">
                                </td>
                                <td style="font-weight: 500; display: flex; align-items: center; gap: 0.5rem; border: none;">
                                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
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
    <script>
        function toggleDropdown() {
            document.getElementById('profileDropdown').classList.toggle('active');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown && !dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });

        // File Explorer Selection Logic
        function updateActionBar() {
            const checkboxes = document.querySelectorAll('.file-checkbox:checked');
            const count = checkboxes.length;
            document.getElementById('selectionCount').innerText = `${count} item${count !== 1 ? 'ns' : ''} selecionado${count !== 1 ? 's' : ''}`;
            
            const topActionBar = document.getElementById('topActionBar');
            const btnRename = document.getElementById('btnRename');
            const btnEdit = document.getElementById('btnEdit');
            const btnDownload = document.getElementById('btnDownload');
            const btnDelete = document.getElementById('btnDelete');

            let canEdit = false;
            if (count === 1) {
                const row = checkboxes[0].closest('tr');
                if (row.getAttribute('data-editable') === 'true') {
                    canEdit = true;
                }
            }

            if (count === 0) {
                if (btnRename) btnRename.style.display = 'none';
                if (btnEdit) btnEdit.style.display = 'none';
                if (btnDownload) btnDownload.style.display = 'none';
                if (btnDelete) btnDelete.style.display = 'none';
            } else {
                if (btnRename) {
                    btnRename.style.display = (count === 1) ? 'inline-flex' : 'none';
                    btnRename.disabled = false;
                }

                if (btnEdit) {
                    btnEdit.style.display = canEdit ? 'inline-flex' : 'none';
                    btnEdit.disabled = false;
                }

                if (btnDownload) {
                    btnDownload.style.display = 'inline-flex';
                    btnDownload.disabled = false;
                }

                if (btnDelete) {
                    btnDelete.style.display = 'inline-flex';
                    btnDelete.disabled = false;
                }
            }
        }

        function toggleFileRow(row, event) {
            if (event.target.tagName.toLowerCase() === 'input') return;
            const checkbox = row.querySelector('.file-checkbox');
            checkbox.checked = !checkbox.checked;
            if (checkbox.checked) {
                row.classList.add('selected');
            } else {
                row.classList.remove('selected');
            }
            updateSelectAllState();
            updateActionBar();
        }

        function toggleFileCheckbox(checkbox, event) {
            event.stopPropagation();
            const row = checkbox.closest('tr');
            if (checkbox.checked) {
                row.classList.add('selected');
            } else {
                row.classList.remove('selected');
            }
            updateSelectAllState();
            updateActionBar();
        }

        function toggleAllFiles(mainCheckbox) {
            const checkboxes = document.querySelectorAll('.file-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = mainCheckbox.checked;
                const row = cb.closest('tr');
                if (cb.checked) {
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            });
            updateActionBar();
        }

        function updateSelectAllState() {
            const mainCheckbox = document.getElementById('selectAllCheckbox');
            if(!mainCheckbox) return;
            const checkboxes = document.querySelectorAll('.file-checkbox');
            const checkedBoxes = document.querySelectorAll('.file-checkbox:checked');
            if (checkboxes.length === 0) {
                mainCheckbox.checked = false;
                mainCheckbox.indeterminate = false;
            } else if (checkedBoxes.length === checkboxes.length) {
                mainCheckbox.checked = true;
                mainCheckbox.indeterminate = false;
            } else if (checkedBoxes.length > 0) {
                mainCheckbox.checked = false;
                mainCheckbox.indeterminate = true;
            } else {
                mainCheckbox.checked = false;
                mainCheckbox.indeterminate = false;
            }
        }

        // Actions
        function getSelectedFiles() {
            return Array.from(document.querySelectorAll('.file-checkbox:checked')).map(cb => {
                return cb.closest('tr').getAttribute('data-filename');
            });
        }

        function editSelected() {
            const files = getSelectedFiles();
            if (files.length === 1) {
                const isEditable = document.querySelector(`.file-checkbox:checked`).closest('tr').getAttribute('data-editable') === 'true';
                if(isEditable) {
                    window.location.href = '/edit.php?file=' + encodeURIComponent(files[0]);
                }
            }
        }

        function renameSelected() {
            const files = getSelectedFiles();
            if (files.length === 1) {
                const oldName = files[0];
                const newName = prompt("Digite o novo nome para o arquivo:", oldName);
                if (newName && newName !== oldName && newName.trim() !== '') {
                    document.getElementById('renameOldInput').value = oldName;
                    document.getElementById('renameNewInput').value = newName.trim();
                    document.getElementById('renameActionForm').submit();
                }
            }
        }

        function downloadSelected() {
            const files = getSelectedFiles();
            files.forEach((file, index) => {
                setTimeout(() => {
                    const a = document.createElement('a');
                    a.href = '/dashboard.php?action=download&file=' + encodeURIComponent(file);
                    // Força a exibição mínima para trigger correto de evento
                    a.style.display = 'none';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                }, index * 400); // delay para prevenir bloqueio por múltiplas requisições
            });
        }

        function deleteSelected() {
            const files = getSelectedFiles();
            if (files.length === 0) return;
            
            const countText = files.length === 1 ? 'este arquivo' : `estes ${files.length} arquivos`;
            if (confirm(`Tem certeza que deseja apagar ${countText}? Esta ação é irreversível.`)) {
                document.getElementById('bulkFilesInput').value = JSON.stringify(files);
                document.getElementById('bulkActionForm').submit();
            }
        }
    </script>
</body>
</html>

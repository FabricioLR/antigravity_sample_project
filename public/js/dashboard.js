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
    const btnShare = document.getElementById('btnShare');

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
        if (btnShare) btnShare.style.display = 'none';
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

        if (btnShare) {
            btnShare.style.display = (count === 1) ? 'inline-flex' : 'none';
            btnShare.disabled = false;
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

// New File Creation Logic
function toggleCreateDropdown(event) {
    event.stopPropagation();
    document.getElementById('createDropdownWrapper').classList.toggle('active');
}

function createNewTextFile(event) {
    event.preventDefault();
    const name = prompt("Digite o nome do novo arquivo (ex: notas.txt):");
    if (name && name.trim() !== '') {
        document.getElementById('createFileNameInput').value = name.trim();
        document.getElementById('createFileForm').submit();
    }
}

// Close all dropdowns when clicking outside
document.addEventListener('click', function(event) {
    // Profile dropdown
    const profileDropdown = document.getElementById('profileDropdown');
    if (profileDropdown && !profileDropdown.contains(event.target)) {
        profileDropdown.classList.remove('active');
    }
    
    // Create dropdown
    const createDropdownWrapper = document.getElementById('createDropdownWrapper');
    if (createDropdownWrapper && !createDropdownWrapper.contains(event.target)) {
        createDropdownWrapper.classList.remove('active');
    }

    // Share Modal
    const shareModal = document.getElementById('shareModal');
    if (shareModal && event.target === shareModal) {
        closeShareModal();
    }
});

// Share Functions
function shareSelected() {
    const files = getSelectedFiles();
    if (files.length === 1) {
        const filename = files[0];
        document.getElementById('shareFileNameDisplay').innerText = `Arquivo: ${filename}`;
        document.getElementById('shareResultArea').style.display = 'none';
        const btnGen = document.getElementById('btnGenerateShare');
        btnGen.style.display = 'inline-block';
        btnGen.disabled = false;
        btnGen.innerText = 'Gerar Link';
        document.getElementById('shareModal').classList.add('active');
    }
}

function closeShareModal() {
    document.getElementById('shareModal').classList.remove('active');
}

function generateShare() {
    const files = getSelectedFiles();
    if (files.length !== 1) return;

    const filename = files[0];
    const duration = document.getElementById('shareDuration').value;
    const btn = document.getElementById('btnGenerateShare');
    
    btn.disabled = true;
    btn.innerText = 'Gerando...';

    const formData = new FormData();
    formData.append('file', filename);
    formData.append('duration', duration);

    fetch('/share.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const protocol = window.location.protocol;
            const host = window.location.host;
            const shareUrl = `${protocol}//${host}/${data.uuid}`;
            
            document.getElementById('shareLinkInput').value = shareUrl;
            document.getElementById('shareResultArea').style.display = 'block';
            btn.style.display = 'none';
        } else {
            alert('Erro: ' + data.error);
            btn.disabled = false;
            btn.innerText = 'Gerar Link';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erro ao conectar com o servidor.');
        btn.disabled = false;
        btn.innerText = 'Gerar Link';
    });
}

function copyShareLink() {
    const input = document.getElementById('shareLinkInput');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = event.target;
        const originalText = btn.innerText;
        btn.innerText = 'Copiado!';
        setTimeout(() => {
            btn.innerText = originalText;
        }, 2000);
    });
}

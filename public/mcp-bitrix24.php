<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php');
    exit;
}
if (!isset($user_data) || ($user_data['perfil'] ?? '') !== 'admin_interno') {
    echo '<div style="color:#e53e3e;padding:2rem">Acesso restrito a administradores.</div>';
    return;
}
?>
<style>
.mcp-badge-ativo   { display:inline-block;padding:.2rem .6rem;border-radius:20px;font-size:.72rem;font-weight:700;background:#d1fae5;color:#065f46 }
.mcp-badge-inativo { display:inline-block;padding:.2rem .6rem;border-radius:20px;font-size:.72rem;font-weight:700;background:#f0f4f8;color:#a0aec0 }
.mcp-chave-cell    { font-family:monospace;font-size:.8rem;color:#718096 }
.mcp-action-btn {
    border:1px solid #e2e8f0;border-radius:6px;background:#fff;padding:.35rem .65rem;
    font-size:.78rem;cursor:pointer;color:#4a5568;font-weight:500;margin-right:.4rem;
}
.mcp-action-btn:hover { background:#f8fafc }
.mcp-action-btn.danger { color:#e53e3e;border-color:#fed7d7 }
.mcp-action-btn.danger:hover { background:#fff5f5 }
</style>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-robot"></i> MCP Bitrix24</h1>
    <button onclick="mcpAbrirNovo()" class="btn-primary"><i class="fas fa-plus"></i> Add Client</button>
</div>

<div class="table-panel">
    <div id="mcp-loading" class="panel-loading" style="padding:2rem"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>
    <div class="table-scroll" id="mcp-table-wrap" style="display:none">
        <table class="clientes-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Key</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="mcp-tbody"></tbody>
        </table>
    </div>
    <div class="table-footer" id="mcp-footer"></div>
</div>

<!-- Modal: Add Client / Show Key -->
<div id="mcp-modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(6,25,32,.6);backdrop-filter:blur(4px);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:2rem;width:440px;max-width:92vw;box-shadow:0 24px 60px rgba(0,0,0,.25);animation:kwPop .18s ease">

        <!-- form view: pedir nome -->
        <div id="mcp-modal-form">
            <div style="width:52px;height:52px;border-radius:50%;background:#e6fbff;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.4rem;color:#086B8D">
                <i class="fas fa-robot"></i>
            </div>
            <h3 style="text-align:center;font-family:'Rubik',sans-serif;font-size:1.05rem;font-weight:700;color:#1a202c;margin:0 0 1.25rem">Novo cliente MCP</h3>
            <div class="panel-field no-edit" style="margin-bottom:.5rem">
                <label>Nome *</label>
                <input type="text" id="mcp-novo-nome" class="form-input" placeholder="Nome do cliente" onkeydown="if(event.key==='Enter') mcpCriarCliente()">
            </div>
            <div id="mcp-novo-erro" style="color:#e53e3e;font-size:.85rem;display:none;margin-bottom:.75rem"></div>
            <div style="display:flex;gap:.75rem;margin-top:1rem">
                <button onclick="mcpFecharModal()" style="flex:1;padding:.65rem;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:#718096;font-size:.875rem;cursor:pointer;font-weight:500">Cancelar</button>
                <button id="mcp-novo-confirmar" onclick="mcpCriarCliente()" style="flex:1;padding:.65rem;border:none;border-radius:8px;background:#0DC2FF;color:#fff;font-size:.875rem;cursor:pointer;font-weight:700">Adicionar</button>
            </div>
        </div>

        <!-- key view: mostrar a chave completa uma unica vez -->
        <div id="mcp-modal-key" style="display:none">
            <div style="width:52px;height:52px;border-radius:50%;background:#d1fae5;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.4rem;color:#065f46">
                <i class="fas fa-key"></i>
            </div>
            <h3 style="text-align:center;font-family:'Rubik',sans-serif;font-size:1.05rem;font-weight:700;color:#1a202c;margin:0 0 .4rem">Cliente criado</h3>
            <p style="text-align:center;font-size:.82rem;color:#e53e3e;margin:0 0 1.1rem;line-height:1.5;font-weight:600">
                Esta é a única vez que a chave completa é exibida. Copie e guarde agora — depois disso ela fica mascarada permanentemente.
            </p>
            <div style="display:flex;gap:.5rem;align-items:center;margin-bottom:.5rem">
                <input type="text" id="mcp-chave-gerada" readonly class="form-input" style="font-family:monospace;font-size:.78rem">
                <button onclick="mcpCopiarChave()" title="Copiar" style="padding:.6rem .8rem;border:1px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;color:#086B8D">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <span id="mcp-copy-msg" style="font-size:.78rem;color:#48bb78;display:block;min-height:1.1em;margin-bottom:1rem"></span>
            <button onclick="mcpFecharModal()" style="width:100%;padding:.65rem;border:none;border-radius:8px;background:#0DC2FF;color:#fff;font-size:.875rem;cursor:pointer;font-weight:700">Fechar</button>
        </div>
    </div>
</div>

<script>
function mcpHtmlEsc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function mcpMaskChave(chave) {
    if (!chave) return '—';
    const tail = chave.length > 8 ? chave.slice(-8) : chave;
    return '••••••••' + mcpHtmlEsc(tail);
}

/* ── carregar tabela ─────────────────────────────────────────────── */
function mcpCarregar() {
    fetch('/api/mcp-clients.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'list' })
    })
        .then(r => r.json())
        .then(rows => {
            document.getElementById('mcp-loading').style.display = 'none';
            document.getElementById('mcp-table-wrap').style.display = 'block';
            const tbody = document.getElementById('mcp-tbody');
            if (!Array.isArray(rows) || !rows.length) {
                tbody.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-robot"></i><p>Nenhum cliente MCP cadastrado.</p></div></td></tr>';
                document.getElementById('mcp-footer').textContent = '0 clientes';
                return;
            }
            tbody.innerHTML = rows.map(c => `
                <tr>
                    <td style="font-weight:600;color:#1a202c">${mcpHtmlEsc(c.nome)}</td>
                    <td class="mcp-chave-cell">${mcpMaskChave(c.chave)}</td>
                    <td><span class="${c.ativo ? 'mcp-badge-ativo' : 'mcp-badge-inativo'}">${c.ativo ? 'Ativo' : 'Inativo'}</span></td>
                    <td style="color:#718096;font-size:.82rem">${mcpHtmlEsc(c.created_fmt || '—')}</td>
                    <td>
                        <button class="mcp-action-btn" onclick="mcpToggle(${c.id})">
                            <i class="fas fa-${c.ativo ? 'pause' : 'play'}"></i> ${c.ativo ? 'Deactivate' : 'Activate'}
                        </button>
                        <button class="mcp-action-btn danger" onclick="mcpExcluir(${c.id}, '${mcpHtmlEsc(c.nome)}')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>`).join('');
            document.getElementById('mcp-footer').textContent = `${rows.length} cliente${rows.length !== 1 ? 's' : ''}`;
        })
        .catch(() => {
            document.getElementById('mcp-loading').innerHTML = '<span style="color:#e53e3e">Erro ao carregar clientes.</span>';
        });
}

/* ── toggle ativo/inativo ────────────────────────────────────────── */
async function mcpToggle(id) {
    const res = await fetch('/api/mcp-clients.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'toggle', id })
    }).then(r => r.json());

    if (res.erro) { alert(res.erro); return; }
    mcpCarregar();
}

/* ── excluir ─────────────────────────────────────────────────────── */
async function mcpExcluir(id, nome) {
    const ok = await kwConfirm(`Deseja excluir o cliente MCP "${nome}"?\n\nEle perderá acesso ao servidor MCP imediatamente.`, 'Excluir cliente MCP');
    if (!ok) return;

    const res = await fetch('/api/mcp-clients.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id })
    }).then(r => r.json());

    if (res.erro) { alert(res.erro); return; }
    mcpCarregar();
}

/* ── modal: novo cliente ─────────────────────────────────────────── */
function mcpAbrirNovo() {
    document.getElementById('mcp-novo-nome').value = '';
    document.getElementById('mcp-novo-erro').style.display = 'none';
    document.getElementById('mcp-modal-form').style.display = 'block';
    document.getElementById('mcp-modal-key').style.display = 'none';
    document.getElementById('mcp-modal-overlay').style.display = 'flex';
    setTimeout(() => document.getElementById('mcp-novo-nome').focus(), 60);
}

function mcpFecharModal() {
    document.getElementById('mcp-modal-overlay').style.display = 'none';
}

async function mcpCriarCliente() {
    const nome   = document.getElementById('mcp-novo-nome').value.trim();
    const erroEl = document.getElementById('mcp-novo-erro');
    if (!nome) {
        erroEl.textContent = 'Nome é obrigatório.';
        erroEl.style.display = 'block';
        return;
    }
    erroEl.style.display = 'none';

    const btn = document.getElementById('mcp-novo-confirmar');
    btn.disabled = true;
    btn.textContent = 'Adicionando...';

    const res = await fetch('/api/mcp-clients.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'create', nome })
    }).then(r => r.json());

    btn.disabled = false;
    btn.innerHTML = 'Adicionar';

    if (res.erro) {
        erroEl.textContent = res.erro;
        erroEl.style.display = 'block';
        return;
    }

    document.getElementById('mcp-chave-gerada').value = res.chave || '';
    document.getElementById('mcp-copy-msg').textContent = '';
    document.getElementById('mcp-modal-form').style.display = 'none';
    document.getElementById('mcp-modal-key').style.display = 'block';
    mcpCarregar();
}

function mcpCopiarChave() {
    const input = document.getElementById('mcp-chave-gerada');
    input.select();
    navigator.clipboard.writeText(input.value).then(() => {
        document.getElementById('mcp-copy-msg').textContent = '✓ Copiado para a área de transferência';
    }).catch(() => {
        document.getElementById('mcp-copy-msg').textContent = 'Não foi possível copiar automaticamente — selecione e copie manualmente.';
    });
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && document.getElementById('mcp-modal-overlay').style.display === 'flex') {
        mcpFecharModal();
    }
});

mcpCarregar();
</script>

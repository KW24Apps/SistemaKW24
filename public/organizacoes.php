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
.org-badge-ativo   { display:inline-block;padding:.2rem .6rem;border-radius:20px;font-size:.72rem;font-weight:700;background:#d1fae5;color:#065f46 }
.org-badge-inativo { display:inline-block;padding:.2rem .6rem;border-radius:20px;font-size:.72rem;font-weight:700;background:#f0f4f8;color:#a0aec0 }
.org-motor-cell    { font-family:monospace;font-size:.78rem;color:#718096;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap }
#org-modal-box     { background:#fff;border-radius:16px;padding:2rem;width:480px;max-width:94vw;box-shadow:0 24px 60px rgba(0,0,0,.25);animation:kwPop .18s ease }
.org-form-label    { display:block;font-size:.72rem;font-weight:700;color:#4a5568;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.35rem }
.org-toggle-row    { display:flex;align-items:center;gap:.75rem;padding:.5rem 0 }
</style>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-sitemap"></i> Organizações</h1>
    <button onclick="orgAbrirModal()" class="btn-primary"><i class="fas fa-plus"></i> Nova Organização</button>
</div>

<div class="table-panel">
    <div id="org-loading" class="panel-loading" style="padding:2rem"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>
    <div class="table-scroll" id="org-table-wrap" style="display:none">
        <table class="clientes-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Status</th>
                    <th>Webhook Motor</th>
                    <th style="width:120px;text-align:right">Ações</th>
                </tr>
            </thead>
            <tbody id="org-tbody"></tbody>
        </table>
    </div>
    <div class="table-footer" id="org-footer"></div>
</div>

<!-- Overlay + Modal criar/editar -->
<div id="org-overlay" onclick="orgFecharModal()"
     style="display:none;position:fixed;inset:0;background:rgba(6,25,32,.6);backdrop-filter:blur(4px);z-index:900"></div>

<div id="org-modal" style="display:none;position:fixed;inset:0;z-index:901;display:none;align-items:center;justify-content:center;pointer-events:none">
    <div id="org-modal-box" style="pointer-events:all">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
            <h3 id="org-modal-title" style="font-family:'Rubik',sans-serif;font-size:1.05rem;font-weight:700;color:#1a202c;margin:0"></h3>
            <button onclick="orgFecharModal()" class="panel-close"><i class="fas fa-times"></i></button>
        </div>

        <div style="display:grid;gap:.85rem">
            <div>
                <label class="org-form-label">Nome *</label>
                <input type="text" id="org-f-nome" class="form-input" placeholder="Nome da organização">
            </div>
            <div>
                <label class="org-form-label">Webhook Motor <small style="font-weight:400;color:#a0aec0;text-transform:none">— webhook para sync de metadados via api_kw24</small></label>
                <input type="url" id="org-f-webhook" class="form-input" placeholder="https://...">
            </div>
            <div class="org-toggle-row">
                <label class="toggle-switch">
                    <input type="checkbox" id="org-f-ativo" checked>
                    <span class="toggle-track"><span class="toggle-thumb"></span></span>
                </label>
                <span id="org-f-ativo-label" style="font-size:.875rem;color:#2d3748;font-weight:500">Organização ativa</span>
            </div>
            <div id="org-form-erro" style="color:#e53e3e;font-size:.85rem;display:none"></div>
        </div>

        <div style="display:flex;gap:.75rem;margin-top:1.5rem">
            <button onclick="orgFecharModal()" style="flex:1;padding:.65rem;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:#718096;font-size:.875rem;cursor:pointer;font-weight:500">Cancelar</button>
            <button onclick="orgSalvar()" id="org-btn-salvar" style="flex:1;padding:.65rem;border:none;border-radius:8px;background:#0DC2FF;color:#fff;font-size:.875rem;cursor:pointer;font-weight:700">Salvar</button>
        </div>
    </div>
</div>

<script>
let orgIdEditando = null;

function orgCarregar() {
    fetch('/api/organizacoes.php?action=list', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(rows => {
            document.getElementById('org-loading').style.display = 'none';
            document.getElementById('org-table-wrap').style.display = 'block';
            const tbody = document.getElementById('org-tbody');
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="4"><div class="empty-state"><i class="fas fa-sitemap"></i><p>Nenhuma organização cadastrada.</p></div></td></tr>';
                document.getElementById('org-footer').textContent = '0 organizações';
                return;
            }
            tbody.innerHTML = rows.map(o => `
                <tr>
                    <td style="font-weight:600;color:#1a202c">${htmlEsc(o.nome)}</td>
                    <td><span class="${o.ativo ? 'org-badge-ativo' : 'org-badge-inativo'}">${o.ativo ? 'Ativo' : 'Inativo'}</span></td>
                    <td class="org-motor-cell" title="${htmlEsc(o.webhook_motor || '')}">${o.webhook_motor ? htmlEsc(o.webhook_motor) : '<span style="color:#cbd5e0">—</span>'}</td>
                    <td style="text-align:right">
                        <button onclick="orgEditar(${o.id})" style="background:none;border:1px solid #e2e8f0;border-radius:6px;padding:.3rem .6rem;font-size:.8rem;cursor:pointer;color:#4a5568;margin-right:.35rem" title="Editar"><i class="fas fa-pen"></i></button>
                        <button onclick="orgToggleAtivo(${o.id}, ${o.ativo})" style="background:none;border:1px solid ${o.ativo ? '#fed7d7' : '#c6f6d5'};border-radius:6px;padding:.3rem .6rem;font-size:.8rem;cursor:pointer;color:${o.ativo ? '#c53030' : '#276749'}" title="${o.ativo ? 'Desativar' : 'Ativar'}"><i class="fas fa-${o.ativo ? 'ban' : 'check'}"></i></button>
                    </td>
                </tr>`).join('');
            document.getElementById('org-footer').textContent = `${rows.length} organização${rows.length !== 1 ? 'ões' : ''}`;
        })
        .catch(() => {
            document.getElementById('org-loading').innerHTML = '<span style="color:#e53e3e">Erro ao carregar organizações.</span>';
        });
}

function htmlEsc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function orgAbrirModal(id) {
    orgIdEditando = id || null;
    document.getElementById('org-modal-title').textContent = id ? 'Editar Organização' : 'Nova Organização';
    document.getElementById('org-f-nome').value    = '';
    document.getElementById('org-f-webhook').value = '';
    document.getElementById('org-f-ativo').checked = true;
    document.getElementById('org-f-ativo-label').textContent = 'Organização ativa';
    document.getElementById('org-form-erro').style.display = 'none';

    if (id) {
        fetch(`/api/organizacoes.php?action=get&id=${id}`, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(o => {
                document.getElementById('org-f-nome').value    = o.nome    || '';
                document.getElementById('org-f-webhook').value = o.webhook_motor || '';
                document.getElementById('org-f-ativo').checked = !!o.ativo;
                document.getElementById('org-f-ativo-label').textContent = o.ativo ? 'Organização ativa' : 'Organização inativa';
            });
    }

    orgMostrarModal();
}

function orgMostrarModal() {
    document.getElementById('org-overlay').style.display = 'block';
    const modal = document.getElementById('org-modal');
    modal.style.display = 'flex';
    setTimeout(() => document.getElementById('org-f-nome').focus(), 60);

    document.getElementById('org-f-ativo').onchange = function() {
        document.getElementById('org-f-ativo-label').textContent = this.checked ? 'Organização ativa' : 'Organização inativa';
    };
}

function orgFecharModal() {
    document.getElementById('org-overlay').style.display = 'none';
    document.getElementById('org-modal').style.display   = 'none';
    orgIdEditando = null;
}

function orgEditar(id) { orgAbrirModal(id); }

async function orgSalvar() {
    const nome    = document.getElementById('org-f-nome').value.trim();
    const webhook = document.getElementById('org-f-webhook').value.trim();
    const ativo   = document.getElementById('org-f-ativo').checked;
    const erroEl  = document.getElementById('org-form-erro');

    if (!nome) {
        erroEl.textContent = 'Nome é obrigatório.';
        erroEl.style.display = 'block';
        return;
    }
    erroEl.style.display = 'none';

    const btn = document.getElementById('org-btn-salvar');
    btn.disabled = true;
    btn.textContent = 'Salvando...';

    const action = orgIdEditando ? 'update' : 'create';
    const payload = { nome, ativo, webhook_motor: webhook || null };
    if (orgIdEditando) payload.id = orgIdEditando;

    try {
        const res = await fetch(`/api/organizacoes.php?action=${action}`, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(r => r.json());

        if (res.sucesso) {
            orgFecharModal();
            orgCarregar();
        } else {
            erroEl.textContent = res.erro || 'Erro ao salvar.';
            erroEl.style.display = 'block';
        }
    } catch (e) {
        erroEl.textContent = 'Erro de conexão.';
        erroEl.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.textContent = 'Salvar';
    }
}

async function orgToggleAtivo(id, ativoAtual) {
    const acao = ativoAtual ? 'desativar' : 'ativar';
    const ok = await kwConfirm(`Deseja ${acao} esta organização?`, `${acao.charAt(0).toUpperCase() + acao.slice(1)} organização`, ativoAtual ? 'danger' : 'success');
    if (!ok) return;

    fetch('/api/organizacoes.php?action=toggle-ativo', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    }).then(r => r.json()).then(res => {
        if (res.sucesso) orgCarregar();
        else alert(res.erro || 'Erro.');
    });
}

// Fechar com ESC
document.addEventListener('keydown', e => { if (e.key === 'Escape') orgFecharModal(); });

orgCarregar();
</script>

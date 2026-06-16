<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php'); exit;
}
if (!isset($user_data['perfil']) || $user_data['perfil'] !== 'admin_interno') {
    header('Location: ?page=dashboard&error=access_denied'); exit;
}
?>

<style>
/* ── Portais Admin ── */
.portais-card {
    background: rgba(255,255,255,0.05);
    border: 1.5px solid rgba(255,255,255,0.10);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.25rem;
}
.portais-card-header {
    padding: .75rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    gap: .6rem;
    font-size: .62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: rgba(255,255,255,.5);
}
.portais-card-body { padding: 1.25rem; }
.portais-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.portais-field { display: flex; flex-direction: column; gap: .35rem; }
.portais-field label {
    font-size: .62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: rgba(255,255,255,.4);
}
.portais-input, .portais-select {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 7px;
    color: #fff;
    font-size: .75rem;
    padding: .45rem .75rem;
    outline: none;
    font-family: inherit;
    width: 100%;
    box-sizing: border-box;
}
.portais-input:focus, .portais-select:focus { border-color: rgba(13,194,255,0.5); }
.portais-select option { background: #0d1e2d; }
.portais-input-row { display: flex; gap: .5rem; }
.portais-input-row .portais-input { flex: 1; }
.portais-btn {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .45rem .9rem; border: none; border-radius: 7px;
    font-size: .72rem; font-weight: 700; cursor: pointer; transition: background .15s;
    white-space: nowrap;
}
.portais-btn-primary { background: #0DC2FF; color: #061920; }
.portais-btn-primary:hover { background: #08aadd; }
.portais-btn-gen { background: rgba(255,255,255,0.1); color: rgba(255,255,255,.8); }
.portais-btn-gen:hover { background: rgba(255,255,255,0.18); }
.portais-btn-cancel { background: rgba(255,255,255,0.06); color: rgba(255,255,255,.5); }
.portais-btn-cancel:hover { background: rgba(255,255,255,0.12); }
.portais-form-actions { display: flex; gap: .65rem; margin-top: 1rem; }
.portais-msg { font-size: .72rem; padding: .5rem .85rem; border-radius: 7px; margin-top: .75rem; }
.portais-msg-ok  { background: rgba(38,255,147,0.12); color: #26FF93; border: 1px solid rgba(38,255,147,0.2); }
.portais-msg-err { background: rgba(229,62,62,0.12); color: #fc8181; border: 1px solid rgba(229,62,62,0.2); }

/* Table */
.portais-table { width: 100%; border-collapse: collapse; font-size: .72rem; }
.portais-table thead th {
    padding: .55rem .9rem; text-align: left;
    font-size: .62rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .05em; color: rgba(255,255,255,.35);
    border-bottom: 1px solid rgba(255,255,255,0.07);
    white-space: nowrap;
}
.portais-table td {
    padding: .7rem .9rem;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    color: rgba(255,255,255,.82);
    vertical-align: middle;
}
.portais-table tbody tr:last-child td { border-bottom: none; }
.portais-table tbody tr:hover td { background: rgba(255,255,255,0.02); }
.portais-badge {
    display: inline-block; font-size: .6rem; font-weight: 700;
    padding: .18rem .5rem; border-radius: 20px; white-space: nowrap;
}
.portais-badge-ativo   { background: rgba(38,255,147,0.15); color: #26FF93; }
.portais-badge-inativo { background: rgba(255,255,255,0.07); color: rgba(255,255,255,.35); }
.portais-link-text {
    color: rgba(13,194,255,.7); font-size: .68rem;
    font-family: monospace; text-decoration: none;
    max-width: 240px; display: inline-block; overflow: hidden;
    text-overflow: ellipsis; white-space: nowrap; vertical-align: middle;
}
.portais-link-text:hover { color: #0DC2FF; }
.portais-copy-btn {
    background: none; border: none; color: rgba(255,255,255,.3);
    cursor: pointer; font-size: .72rem; padding: .2rem .35rem;
    border-radius: 4px; transition: color .15s, background .15s; vertical-align: middle;
}
.portais-copy-btn:hover { color: #0DC2FF; background: rgba(13,194,255,0.08); }
.portais-action-btn {
    background: none; border: 1px solid rgba(255,255,255,0.12);
    color: rgba(255,255,255,.6); font-size: .65rem; font-weight: 600;
    padding: .25rem .6rem; border-radius: 5px; cursor: pointer; margin-right: .3rem;
    transition: all .15s; white-space: nowrap;
}
.portais-action-btn:hover { background: rgba(255,255,255,0.08); color: #fff; }
.portais-action-btn.danger { border-color: rgba(229,62,62,0.3); color: #fc8181; }
.portais-action-btn.danger:hover { background: rgba(229,62,62,0.12); border-color: rgba(229,62,62,0.5); }
.portais-action-btn.warn { border-color: rgba(246,173,85,0.3); color: #f6ad55; }
.portais-action-btn.warn:hover { background: rgba(246,173,85,0.1); }
.portais-empty { text-align:center; padding: 2.5rem 1rem; color: rgba(255,255,255,.25); font-size: .72rem; }
.portais-tbl-scroll { overflow-x: auto; }
</style>

<!-- ── Page header ── -->
<div class="page-header" style="margin-bottom:1.25rem">
    <h1 class="page-title" style="font-size:1.25rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:.65rem">
        <i class="fas fa-globe" style="color:#0DC2FF;font-size:1.05rem"></i>
        Portais de Cliente
    </h1>
</div>

<!-- ── Formulário ── -->
<div class="portais-card" id="portais-form-card">
    <div class="portais-card-header">
        <i class="fas fa-plus-circle" style="color:#0DC2FF"></i>
        <span id="portais-form-title">Criar Portal</span>
    </div>
    <div class="portais-card-body">
        <input type="hidden" id="porta-edit-id" value="">
        <div class="portais-form-grid">
            <div class="portais-field">
                <label>Empresa (Bitrix24)</label>
                <select class="portais-select" id="porta-empresa-sel" onchange="portaisOnEmpresaChange()">
                    <option value="">Carregando…</option>
                </select>
                <input type="hidden" id="porta-company-id">
                <input type="hidden" id="porta-company-name">
            </div>
            <div class="portais-field">
                <label>Slug (URL)</label>
                <input type="text" class="portais-input" id="porta-slug" placeholder="ex: capiton" pattern="[a-z0-9\-]+">
            </div>
            <div class="portais-field">
                <label>Senha</label>
                <div class="portais-input-row">
                    <input type="text" class="portais-input" id="porta-senha" placeholder="••••••••••••" autocomplete="off">
                    <button type="button" class="portais-btn portais-btn-gen" onclick="portaisGerarSenha()">
                        <i class="fas fa-dice"></i> Gerar
                    </button>
                </div>
            </div>
            <div class="portais-field" id="porta-nova-senha-field" style="display:none">
                <label>Nova senha (deixe vazio para manter)</label>
                <div class="portais-input-row">
                    <input type="text" class="portais-input" id="porta-nova-senha" placeholder="(sem alteração)" autocomplete="off">
                    <button type="button" class="portais-btn portais-btn-gen" onclick="portaisGerarSenha(true)">
                        <i class="fas fa-dice"></i> Gerar
                    </button>
                </div>
            </div>
        </div>
        <div class="portais-form-actions">
            <button type="button" class="portais-btn portais-btn-primary" onclick="portaisSubmit()">
                <i class="fas fa-save"></i>
                <span id="portais-submit-label">Criar portal</span>
            </button>
            <button type="button" class="portais-btn portais-btn-cancel" id="portais-cancel-btn" style="display:none" onclick="portaisResetForm()">
                Cancelar edição
            </button>
        </div>
        <div id="portais-msg" style="display:none" class="portais-msg"></div>
    </div>
</div>

<!-- ── Lista ── -->
<div class="portais-card">
    <div class="portais-card-header">
        <i class="fas fa-list" style="color:#0DC2FF"></i>
        <span>Portais ativos</span>
        <span id="portais-count" style="margin-left:auto;color:rgba(255,255,255,.2);font-size:.6rem"></span>
    </div>
    <div class="portais-tbl-scroll">
        <table class="portais-table">
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Status</th>
                    <th>Link</th>
                    <th>Embed</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="portais-tbody">
                <tr><td colspan="5" class="portais-empty"><i class="fas fa-circle-notch fa-spin"></i> Carregando…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    'use strict';

    var _portals = {};
    var _empresas = {};
    var BASE = 'https://app.kw24.com.br';

    function esc(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Empresas ────────────────────────────────────────────────────────────────
    function loadEmpresas() {
        fetch('/api/portais-empresas.php')
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var sel = document.getElementById('porta-empresa-sel');
                sel.innerHTML = '<option value="">— Selecione a empresa —</option>';
                (d.empresas || []).forEach(function (e) {
                    _empresas[e.id] = e.name;
                    var o = document.createElement('option');
                    o.value = e.id;
                    o.textContent = e.name;
                    sel.appendChild(o);
                });
            })
            .catch(function () {
                document.getElementById('porta-empresa-sel').innerHTML =
                    '<option value="">Erro ao carregar empresas</option>';
            });
    }

    window.portaisOnEmpresaChange = function () {
        var sel = document.getElementById('porta-empresa-sel');
        var id  = sel.value;
        var nm  = sel.options[sel.selectedIndex]?.text || '';
        document.getElementById('porta-company-id').value   = id;
        document.getElementById('porta-company-name').value = nm;

        // Auto-sugerir slug a partir do nome
        if (id && !document.getElementById('porta-edit-id').value) {
            var slug = nm.toLowerCase()
                .normalize('NFD').replace(/[̀-ͯ]/g,'')
                .replace(/[^a-z0-9\s\-]/g,'')
                .replace(/\s+/g,'-')
                .replace(/-+/g,'-')
                .replace(/^-|-$/g,'');
            document.getElementById('porta-slug').value = slug;
        }
    };

    // ── Gerar senha aleatória ───────────────────────────────────────────────────
    window.portaisGerarSenha = function (isEdit) {
        var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        var pwd   = '';
        var arr   = new Uint8Array(12);
        crypto.getRandomValues(arr);
        arr.forEach(function (b) { pwd += chars[b % chars.length]; });
        var fieldId = isEdit ? 'porta-nova-senha' : 'porta-senha';
        document.getElementById(fieldId).value = pwd;
    };

    // ── Submit ──────────────────────────────────────────────────────────────────
    window.portaisSubmit = function () {
        var editId  = document.getElementById('porta-edit-id').value;
        var cidEl   = document.getElementById('porta-company-id');
        var cnmEl   = document.getElementById('porta-company-name');
        var slugEl  = document.getElementById('porta-slug');
        var senhaEl = document.getElementById('porta-senha');
        var nvSenha = document.getElementById('porta-nova-senha');

        var body = new URLSearchParams();

        if (editId) {
            body.set('action',       'editar');
            body.set('id',           editId);
            body.set('company_name', cnmEl.value);
            body.set('slug',         slugEl.value.trim().toLowerCase());
            body.set('nova_senha',   nvSenha.value.trim());
        } else {
            if (!cidEl.value) { showMsg('Selecione uma empresa.', true); return; }
            if (!slugEl.value.trim()) { showMsg('Informe o slug.', true); return; }
            if (!senhaEl.value.trim()) { showMsg('Informe ou gere uma senha.', true); return; }
            body.set('action',       'criar');
            body.set('company_id',   cidEl.value);
            body.set('company_name', cnmEl.value);
            body.set('slug',         slugEl.value.trim().toLowerCase());
            body.set('senha',        senhaEl.value.trim());
        }

        fetch('/api/portais-gerenciar.php', { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.erro) { showMsg(d.erro, true); return; }
                showMsg(editId ? 'Portal atualizado com sucesso.' : 'Portal criado! Link: ' + BASE + '/portal/' + d.slug, false);
                portaisResetForm();
                loadPortais();
            })
            .catch(function () { showMsg('Erro de rede.', true); });
    };

    // ── Carregar lista ──────────────────────────────────────────────────────────
    function loadPortais() {
        var body = new URLSearchParams({action: 'listar'});
        fetch('/api/portais-gerenciar.php', { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                _portals = {};
                (d.portais || []).forEach(function (p) { _portals[p.id] = p; });
                renderTable(d.portais || []);
            });
    }

    function renderTable(portais) {
        var count = document.getElementById('portais-count');
        var tbody = document.getElementById('portais-tbody');
        count.textContent = portais.length + ' portal' + (portais.length !== 1 ? 'is' : '');

        if (!portais.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="portais-empty">Nenhum portal criado ainda</td></tr>';
            return;
        }

        var html = '';
        portais.forEach(function (p) {
            var link  = BASE + '/portal/' + p.slug;
            var embed = '<iframe src="' + BASE + '/portal/embed/' + p.embed_token
                      + '" width="100%" height="800" frameborder="0"></iframe>';
            var badge = p.ativo
                ? '<span class="portais-badge portais-badge-ativo">Ativo</span>'
                : '<span class="portais-badge portais-badge-inativo">Inativo</span>';

            html += '<tr>'
                + '<td style="font-weight:600;color:#fff">' + esc(p.company_name) + '</td>'
                + '<td>' + badge + '</td>'
                + '<td>'
                    + '<a href="' + link + '" target="_blank" class="portais-link-text">' + link + '</a>'
                    + '<button class="portais-copy-btn" data-copy="' + esc(link) + '" title="Copiar link"><i class="fas fa-copy"></i></button>'
                + '</td>'
                + '<td>'
                    + '<span style="font-size:.62rem;color:rgba(255,255,255,.3);font-family:monospace">&lt;iframe…&gt;</span>'
                    + '<button class="portais-copy-btn" data-copy="' + esc(embed) + '" title="Copiar embed"><i class="fas fa-copy"></i></button>'
                + '</td>'
                + '<td style="white-space:nowrap">'
                    + '<button class="portais-action-btn" onclick="portaisEdit(' + p.id + ')">Editar</button>'
                    + '<button class="portais-action-btn warn" onclick="portaisToggle(' + p.id + ')">' + (p.ativo ? 'Desativar' : 'Ativar') + '</button>'
                    + '<button class="portais-action-btn danger" onclick="portaisDelete(' + p.id + ', ' + JSON.stringify(p.company_name) + ')">Excluir</button>'
                + '</td>'
                + '</tr>';
        });
        tbody.innerHTML = html;
    }

    // ── Ações de tabela ─────────────────────────────────────────────────────────
    window.portaisEdit = function (id) {
        var p = _portals[id];
        if (!p) return;

        document.getElementById('porta-edit-id').value      = p.id;
        document.getElementById('porta-company-id').value   = p.company_id;
        document.getElementById('porta-company-name').value = p.company_name;
        document.getElementById('porta-slug').value         = p.slug;

        // Select empresa
        var sel = document.getElementById('porta-empresa-sel');
        for (var i = 0; i < sel.options.length; i++) {
            if (parseInt(sel.options[i].value) === parseInt(p.company_id)) {
                sel.selectedIndex = i; break;
            }
        }

        // Troca de UI para modo edição
        document.getElementById('portais-form-title').textContent    = 'Editar Portal — ' + p.company_name;
        document.getElementById('portais-submit-label').textContent  = 'Salvar alterações';
        document.getElementById('portais-cancel-btn').style.display  = '';
        document.getElementById('porta-senha').closest('.portais-field').style.display   = 'none';
        document.getElementById('porta-nova-senha-field').style.display = '';

        document.getElementById('portais-form-card').scrollIntoView({ behavior: 'smooth' });
    };

    window.portaisToggle = function (id) {
        var body = new URLSearchParams({action: 'toggle', id: id});
        fetch('/api/portais-gerenciar.php', { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.sucesso) loadPortais();
                else showMsg(d.erro || 'Erro', true);
            });
    };

    window.portaisDelete = function (id, name) {
        if (!confirm('Excluir portal de "' + name + '"? Esta ação não pode ser desfeita.')) return;
        var body = new URLSearchParams({action: 'excluir', id: id});
        fetch('/api/portais-gerenciar.php', { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.sucesso) { showMsg('Portal excluído.', false); loadPortais(); }
                else showMsg(d.erro || 'Erro', true);
            });
    };

    window.portaisCopy = function (text) {
        navigator.clipboard.writeText(text).catch(function () {
            var el = document.createElement('textarea');
            el.value = text; document.body.appendChild(el);
            el.select(); document.execCommand('copy'); document.body.removeChild(el);
        });
    };

    window.portaisResetForm = function () {
        document.getElementById('porta-edit-id').value       = '';
        document.getElementById('porta-company-id').value    = '';
        document.getElementById('porta-company-name').value  = '';
        document.getElementById('porta-slug').value          = '';
        document.getElementById('porta-senha').value         = '';
        document.getElementById('porta-nova-senha').value    = '';
        document.getElementById('porta-empresa-sel').selectedIndex = 0;
        document.getElementById('portais-form-title').textContent   = 'Criar Portal';
        document.getElementById('portais-submit-label').textContent = 'Criar portal';
        document.getElementById('portais-cancel-btn').style.display = 'none';
        document.getElementById('porta-senha').closest('.portais-field').style.display = '';
        document.getElementById('porta-nova-senha-field').style.display = 'none';
        hideMsg();
    };

    function showMsg(text, isErr) {
        var el = document.getElementById('portais-msg');
        el.textContent = text;
        el.className = 'portais-msg ' + (isErr ? 'portais-msg-err' : 'portais-msg-ok');
        el.style.display = '';
    }
    function hideMsg() {
        document.getElementById('portais-msg').style.display = 'none';
    }

    // ── Copy buttons (delegated — valores em data-copy, sem inline JS) ──────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.portais-copy-btn');
        if (!btn || !('copy' in btn.dataset)) return;
        portaisCopy(btn.dataset.copy);
        var icon = btn.querySelector('i');
        if (icon) {
            icon.className = 'fas fa-check';
            setTimeout(function () { icon.className = 'fas fa-copy'; }, 1500);
        }
    });

    // ── Init ────────────────────────────────────────────────────────────────────
    loadEmpresas();
    loadPortais();
})();
</script>

<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php'); exit;
}
if (!isset($user_data['perfil']) || $user_data['perfil'] !== 'admin_interno') {
    header('Location: ?page=dashboard&error=access_denied'); exit;
}
?>
<style>
/* ── Portais BI Admin ── */
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
    display: flex; align-items: center; gap: .6rem;
    font-size: .62rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .06em;
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
    font-size: .62rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .06em;
    color: rgba(255,255,255,.4);
}
.portais-input, .portais-select {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 7px; color: #fff;
    font-size: .75rem; padding: .45rem .75rem;
    outline: none; font-family: inherit;
    width: 100%; box-sizing: border-box;
}
.portais-input:focus, .portais-select:focus { border-color: rgba(13,194,255,0.5); }
.portais-select option { background: #0d1e2d; }
.portais-input-row { display: flex; gap: .5rem; }
.portais-input-row .portais-input { flex: 1; }

/* Multi-select custom */
.portais-multisel {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 7px; padding: .35rem .5rem;
    max-height: 130px; overflow-y: auto; min-height: 50px;
}
.portais-multisel .pm-item {
    display: flex; align-items: center; gap: .45rem;
    padding: .22rem .3rem; border-radius: 4px;
    cursor: pointer; transition: background .1s;
    font-size: .73rem; color: rgba(255,255,255,.75);
    user-select: none;
}
.portais-multisel .pm-item:hover { background: rgba(255,255,255,0.06); }
.portais-multisel .pm-item input[type=checkbox] { accent-color: #0DC2FF; margin: 0; }
.portais-multisel .pm-item.selected { color: #0DC2FF; }
.portais-multisel-empty {
    font-size: .7rem; color: rgba(255,255,255,.25);
    padding: .4rem .2rem;
}

/* Filter type toggle */
.portais-toggle-row { display: flex; gap: .5rem; }
.portais-toggle-btn {
    flex: 1; padding: .4rem .5rem;
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 6px; background: rgba(255,255,255,0.05);
    color: rgba(255,255,255,.5); font-size: .72rem;
    font-weight: 600; cursor: pointer; text-align: center;
    transition: all .15s;
}
.portais-toggle-btn.active { border-color: #0DC2FF; background: rgba(13,194,255,0.12); color: #0DC2FF; }

.portais-btn {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .45rem .9rem; border: none; border-radius: 7px;
    font-size: .72rem; font-weight: 700; cursor: pointer;
    transition: background .15s; white-space: nowrap;
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
.portais-msg-err { background: rgba(229,62,62,0.12);  color: #fc8181; border: 1px solid rgba(229,62,62,0.2); }

/* Table */
.portais-table { width: 100%; border-collapse: collapse; font-size: .72rem; }
.portais-table thead th {
    padding: .55rem .9rem; text-align: left;
    font-size: .62rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .05em; color: rgba(255,255,255,.35);
    border-bottom: 1px solid rgba(255,255,255,0.07); white-space: nowrap;
}
.portais-table td {
    padding: .7rem .9rem; border-bottom: 1px solid rgba(255,255,255,0.05);
    color: rgba(255,255,255,.82); vertical-align: middle;
}
.portais-table tbody tr:last-child td { border-bottom: none; }
.portais-table tbody tr:hover td { background: rgba(255,255,255,0.02); }
.portais-badge {
    display: inline-block; font-size: .6rem; font-weight: 700;
    padding: .18rem .5rem; border-radius: 20px; white-space: nowrap;
}
.portais-badge-ativo   { background: rgba(38,255,147,0.15); color: #26FF93; }
.portais-badge-inativo { background: rgba(255,255,255,0.07); color: rgba(255,255,255,.35); }
.portais-badge-tipo {
    display: inline-block; font-size: .58rem; font-weight: 700;
    padding: .15rem .45rem; border-radius: 20px;
    background: rgba(13,194,255,0.12); color: rgba(13,194,255,.8);
    text-transform: uppercase; letter-spacing: .04em;
}
.portais-link-text {
    color: rgba(13,194,255,.7); font-size: .68rem; font-family: monospace;
    text-decoration: none; max-width: 220px; display: inline-block;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: middle;
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
.portais-tags { display: flex; flex-wrap: wrap; gap: .25rem; }
.portais-tag {
    font-size: .6rem; padding: .12rem .4rem; border-radius: 4px;
    background: rgba(255,255,255,0.08); color: rgba(255,255,255,.55);
    white-space: nowrap; max-width: 140px;
    overflow: hidden; text-overflow: ellipsis;
}
</style>

<!-- Page header -->
<div class="page-header" style="margin-bottom:1.25rem">
    <h1 class="page-title" style="font-size:1.25rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:.65rem">
        <i class="fas fa-globe" style="color:#0DC2FF;font-size:1.05rem"></i>
        Portais BI
    </h1>
</div>

<!-- ── Formulário ── -->
<div class="portais-card" id="portais-form-card">
    <div class="portais-card-header">
        <i class="fas fa-plus-circle" style="color:#0DC2FF"></i>
        <span id="portais-form-title">Criar Portal</span>
    </div>
    <div class="portais-card-body">
        <input type="hidden" id="pbi-edit-id" value="">

        <!-- Relatório: always visible -->
        <div class="portais-field" style="margin-bottom:1rem">
            <label>Relatório</label>
            <select class="portais-select" id="pbi-relatorio">
                <option value="">Carregando…</option>
            </select>
        </div>

        <!-- Extra fields: hidden until a report is selected -->
        <div id="pbi-extra-fields" style="display:none">
            <div class="portais-form-grid">

                <div class="portais-field">
                    <label>Tipo de filtro</label>
                    <div class="portais-toggle-row">
                        <button type="button" class="portais-toggle-btn active" id="pbi-tipo-parceiro" onclick="pbiSetTipo('parceiro')">Parceiro</button>
                        <button type="button" class="portais-toggle-btn"        id="pbi-tipo-oportunidade" onclick="pbiSetTipo('oportunidade')">Oportunidade</button>
                    </div>
                </div>

                <div class="portais-field">
                    <label>Slug (URL)</label>
                    <input type="text" class="portais-input" id="pbi-slug" placeholder="ex: parceiro-abc" pattern="[a-z0-9\-]+">
                </div>

                <div class="portais-field">
                    <label>Nome (opcional)</label>
                    <input type="text" class="portais-input" id="pbi-nome" placeholder="Referência interna">
                </div>

                <div class="portais-field" id="pbi-senha-field">
                    <label>Senha</label>
                    <div class="portais-input-row">
                        <input type="text" class="portais-input" id="pbi-senha" placeholder="••••••••" autocomplete="off">
                        <button type="button" class="portais-btn portais-btn-gen" onclick="pbiGerarSenha('pbi-senha')">
                            <i class="fas fa-dice"></i> Gerar
                        </button>
                    </div>
                </div>

                <div class="portais-field" id="pbi-nova-senha-field" style="display:none">
                    <label>Nova senha <span style="color:rgba(255,255,255,.25)">(vazio = manter)</span></label>
                    <div class="portais-input-row">
                        <input type="text" class="portais-input" id="pbi-nova-senha" placeholder="(sem alteração)" autocomplete="off">
                        <button type="button" class="portais-btn portais-btn-gen" onclick="pbiGerarSenha('pbi-nova-senha')">
                            <i class="fas fa-dice"></i> Gerar
                        </button>
                    </div>
                </div>

                <div class="portais-field" style="grid-column: 1 / -1">
                    <label id="pbi-filtros-label">Parceiros <span style="color:rgba(255,255,255,.25)">(selecione um ou mais)</span></label>
                    <input type="text" class="portais-input" id="pbi-filtros-search" placeholder="Buscar..." autocomplete="off" style="margin-bottom:.4rem">
                    <div class="portais-multisel" id="pbi-filtros-list">
                        <span class="portais-multisel-empty">Carregando…</span>
                    </div>
                </div>

            </div>
        </div>

        <div class="portais-form-actions">
            <button type="button" class="portais-btn portais-btn-primary" onclick="pbiSubmit()">
                <i class="fas fa-save"></i>
                <span id="pbi-submit-label">Criar portal</span>
            </button>
            <button type="button" class="portais-btn portais-btn-cancel" id="pbi-cancel-btn" style="display:none" onclick="pbiResetForm()">
                Cancelar edição
            </button>
        </div>
        <div id="pbi-msg" style="display:none" class="portais-msg"></div>
    </div>
</div>

<!-- ── Tabela ── -->
<div class="portais-card">
    <div class="portais-card-header">
        <i class="fas fa-list" style="color:#0DC2FF"></i>
        <span>Portais ativos</span>
        <span id="pbi-count" style="margin-left:auto;color:rgba(255,255,255,.2);font-size:.6rem"></span>
    </div>
    <div class="portais-tbl-scroll">
        <table class="portais-table">
            <thead>
                <tr>
                    <th>Relatório</th>
                    <th>Tipo</th>
                    <th>Filtros</th>
                    <th>Status</th>
                    <th>Link</th>
                    <th>Embed</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="pbi-tbody">
                <tr><td colspan="7" class="portais-empty"><i class="fas fa-circle-notch fa-spin"></i> Carregando…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    'use strict';
    var BASE          = 'https://app.kw24.com.br';
    var _portais      = {};
    var _tipo         = 'parceiro';
    var _filterItems  = [];
    var _filterLoaded = false;

    function esc(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Relatórios dropdown ────────────────────────────────────────────────
    function loadRelatorios() {
        fetch('/api/relatorios-bi.php?action=list')
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var sel = document.getElementById('pbi-relatorio');
                sel.innerHTML = '<option value="">— Selecione —</option>';
                (d.data || []).forEach(function (r) {
                    var o = document.createElement('option');
                    o.value = r.slug;
                    o.textContent = r.nome_amigavel;
                    sel.appendChild(o);
                });
            })
            .catch(function () {
                var sel = document.getElementById('pbi-relatorio');
                sel.innerHTML = '<option value="">Erro ao carregar relatórios</option>';
            });
    }

    // Show/hide extra fields based on report selection
    document.getElementById('pbi-relatorio').addEventListener('change', function () {
        if (this.value) {
            document.getElementById('pbi-extra-fields').style.display = '';
            if (!_filterLoaded) {
                loadFilterItems(_tipo);
                _filterLoaded = true;
            }
        } else {
            document.getElementById('pbi-extra-fields').style.display = 'none';
        }
    });

    // ── Tipo de filtro ─────────────────────────────────────────────────────
    window.pbiSetTipo = function (tipo) {
        _tipo = tipo;
        document.getElementById('pbi-tipo-parceiro').className    = 'portais-toggle-btn' + (tipo === 'parceiro'    ? ' active' : '');
        document.getElementById('pbi-tipo-oportunidade').className = 'portais-toggle-btn' + (tipo === 'oportunidade' ? ' active' : '');
        document.getElementById('pbi-filtros-label').innerHTML = (tipo === 'parceiro' ? 'Parceiros' : 'Oportunidades')
            + ' <span style="color:rgba(255,255,255,.25)">(selecione um ou mais)</span>';
        // Only load if extra fields are visible (report already selected or editing)
        if (document.getElementById('pbi-extra-fields').style.display !== 'none') {
            loadFilterItems(tipo);
        }
    };

    function loadFilterItems(tipo) {
        var list   = document.getElementById('pbi-filtros-list');
        var search = document.getElementById('pbi-filtros-search');
        if (search) search.value = '';
        list.innerHTML = '<span class="portais-multisel-empty">Carregando…</span>';
        fetch('/api/portais-bi.php?action=list-filters&type=' + tipo)
            .then(function (r) { return r.json(); })
            .then(function (d) {
                _filterItems = d.items || [];
                renderFilterList(_filterItems, []);
            })
            .catch(function () {
                list.innerHTML = '<span class="portais-multisel-empty" style="color:#fc8181">Erro ao carregar.</span>';
            });
    }

    function renderFilterList(items, selectedIds) {
        var list = document.getElementById('pbi-filtros-list');
        if (!items.length) {
            list.innerHTML = '<span class="portais-multisel-empty">Nenhum item encontrado.</span>';
            return;
        }
        var html = '';
        items.forEach(function (item) {
            var checked = selectedIds.indexOf(String(item.id)) !== -1;
            html += '<label class="pm-item' + (checked ? ' selected' : '') + '">'
                + '<input type="checkbox" value="' + esc(item.id) + '" data-nome="' + esc(item.nome) + '"'
                + (checked ? ' checked' : '') + '>'
                + esc(item.nome)
                + '</label>';
        });
        list.innerHTML = html;
        list.querySelectorAll('input[type=checkbox]').forEach(function (cb) {
            cb.addEventListener('change', function () {
                cb.closest('.pm-item').className = 'pm-item' + (cb.checked ? ' selected' : '');
            });
        });
    }

    // ── Search filter ──────────────────────────────────────────────────────
    document.getElementById('pbi-filtros-search').addEventListener('input', function () {
        var q = (this.value || '').toLowerCase();
        document.querySelectorAll('#pbi-filtros-list .pm-item').forEach(function (item) {
            var cb   = item.querySelector('input[type=checkbox]');
            var nome = (cb ? (cb.dataset.nome || '') : item.textContent || '').toLowerCase();
            item.style.display = (!q || nome.indexOf(q) !== -1) ? '' : 'none';
        });
    });

    function getSelectedFilters() {
        var values = [], labels = [];
        document.querySelectorAll('#pbi-filtros-list input[type=checkbox]:checked').forEach(function (cb) {
            values.push(String(cb.value));
            labels.push(String(cb.dataset.nome || cb.value));
        });
        return { values: values, labels: labels };
    }

    // ── Gerar senha ────────────────────────────────────────────────────────
    window.pbiGerarSenha = function (fieldId) {
        var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        var pwd = '';
        var arr = new Uint8Array(12);
        crypto.getRandomValues(arr);
        arr.forEach(function (b) { pwd += chars[b % chars.length]; });
        document.getElementById(fieldId).value = pwd;
    };

    // ── Submit ─────────────────────────────────────────────────────────────
    window.pbiSubmit = function () {
        var editId    = document.getElementById('pbi-edit-id').value;
        var relatorio = document.getElementById('pbi-relatorio').value;
        var slug      = document.getElementById('pbi-slug').value.trim().toLowerCase();
        var nome      = document.getElementById('pbi-nome').value.trim();
        var fil       = getSelectedFilters();

        if (!relatorio) { pbiShowMsg('Selecione um relatório.', true); return; }
        if (!slug)       { pbiShowMsg('Informe o slug.',         true); return; }
        if (!fil.values.length) { pbiShowMsg('Selecione pelo menos um filtro.', true); return; }

        var body = {
            relatorio_slug: relatorio,
            filter_type:    _tipo,
            filter_values:  fil.values,
            filter_labels:  fil.labels,
            slug:           slug,
            nome:           nome,
        };

        if (editId) {
            body.id = parseInt(editId);
            var novaSenha = document.getElementById('pbi-nova-senha').value.trim();
            if (novaSenha) body.senha = novaSenha;
        } else {
            var senha = document.getElementById('pbi-senha').value.trim();
            if (!senha) { pbiShowMsg('Informe ou gere uma senha.', true); return; }
            body.senha = senha;
        }

        fetch('/api/portais-bi.php?' + (editId ? 'action=update' : 'action=create'), {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(body),
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.erro) { pbiShowMsg(d.erro, true); return; }
            if (!editId && d.embed_token) {
                var link  = BASE + '/portal/' + relatorio + '/' + slug;
                var embed = '<iframe src="' + link + '?embed=' + d.embed_token
                    + '" width="100%" height="800" frameborder="0" style="border:none"><\/iframe>';
                pbiShowMsg('Portal criado! Link: ' + link + '\nEmbed copiado para a área de transferência.', false);
                navigator.clipboard.writeText(embed).catch(function () {});
            } else {
                pbiShowMsg('Portal atualizado.', false);
            }
            pbiResetForm();
            loadPortais();
        })
        .catch(function () { pbiShowMsg('Erro de rede.', true); });
    };

    // ── Carregar lista ─────────────────────────────────────────────────────
    function loadPortais() {
        fetch('/api/portais-bi.php?action=list')
            .then(function (r) { return r.json(); })
            .then(function (d) {
                _portais = {};
                (d.portais || []).forEach(function (p) { _portais[p.id] = p; });
                renderTable(d.portais || []);
            })
            .catch(function () {
                document.getElementById('pbi-tbody').innerHTML =
                    '<tr><td colspan="7" class="portais-empty" style="color:#fc8181">Erro ao carregar portais.</td></tr>';
            });
    }

    function renderTable(portais) {
        var count = document.getElementById('pbi-count');
        var tbody = document.getElementById('pbi-tbody');
        count.textContent = portais.length + ' portal' + (portais.length !== 1 ? 'is' : '');
        if (!portais.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="portais-empty">Nenhum portal criado ainda</td></tr>';
            return;
        }
        var html = '';
        portais.forEach(function (p) {
            var link  = BASE + '/portal/' + p.relatorio_slug + '/' + p.slug;
            var embed = '<iframe src="' + link + '?embed=' + p.embed_token
                + '" width="100%" height="800" frameborder="0" style="border:none"><\/iframe>';
            var badge = p.ativo
                ? '<span class="portais-badge portais-badge-ativo">Ativo</span>'
                : '<span class="portais-badge portais-badge-inativo">Inativo</span>';
            var tags = (p.filter_labels || []).slice(0, 3).map(function (l) {
                return '<span class="portais-tag">' + esc(l) + '</span>';
            }).join('');
            if ((p.filter_labels || []).length > 3) {
                tags += '<span class="portais-tag">+' + ((p.filter_labels || []).length - 3) + '</span>';
            }

            html += '<tr>'
                + '<td style="font-weight:500;color:#fff;font-size:.7rem">' + esc(p.relatorio_slug) + '</td>'
                + '<td><span class="portais-badge-tipo">' + esc(p.filter_type) + '</span></td>'
                + '<td><div class="portais-tags">' + tags + '</div></td>'
                + '<td>' + badge + '</td>'
                + '<td>'
                    + '<a href="' + link + '" target="_blank" class="portais-link-text">' + esc(link) + '</a>'
                    + '<button class="portais-copy-btn" data-copy="' + esc(link) + '" title="Copiar link"><i class="fas fa-copy"></i></button>'
                + '</td>'
                + '<td>'
                    + '<span style="font-size:.62rem;color:rgba(255,255,255,.3);font-family:monospace">&lt;iframe&hellip;&gt;</span>'
                    + '<button class="portais-copy-btn" data-copy="' + esc(embed) + '" title="Copiar embed"><i class="fas fa-copy"></i></button>'
                + '</td>'
                + '<td style="white-space:nowrap">'
                    + '<button class="portais-action-btn" data-action="edit" data-id="' + p.id + '">Editar</button>'
                    + '<button class="portais-action-btn warn" data-action="toggle" data-id="' + p.id + '">' + (p.ativo ? 'Desativar' : 'Ativar') + '</button>'
                    + '<button class="portais-action-btn danger" data-action="delete" data-id="' + p.id + '" data-nome="' + esc(p.nome || p.slug) + '">Excluir</button>'
                + '</td>'
                + '</tr>';
        });
        tbody.innerHTML = html;
    }

    // ── Delegação de eventos para ações da tabela ──────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-action]');
        if (!btn) return;
        var tbody = document.getElementById('pbi-tbody');
        if (!tbody || !tbody.contains(btn)) return;
        var action = btn.dataset.action;
        var id     = parseInt(btn.dataset.id);
        if (action === 'edit')   window.pbiEdit(id);
        else if (action === 'toggle') window.pbiToggle(id);
        else if (action === 'delete') window.pbiDelete(id, btn.dataset.nome);
    });

    // ── Ações ──────────────────────────────────────────────────────────────
    window.pbiEdit = function (id) {
        var p = _portais[id];
        if (!p) return;

        document.getElementById('pbi-edit-id').value   = p.id;
        document.getElementById('pbi-relatorio').value = p.relatorio_slug;
        document.getElementById('pbi-slug').value      = p.slug;
        document.getElementById('pbi-nome').value      = p.nome || '';

        // Show extra fields before setting tipo (pbiSetTipo checks visibility)
        document.getElementById('pbi-extra-fields').style.display = '';
        _filterLoaded = true;

        pbiSetTipo(p.filter_type);
        // Mark selections after filter items finish loading
        setTimeout(function () {
            var selectedIds = (p.filter_values || []).map(String);
            document.querySelectorAll('#pbi-filtros-list input[type=checkbox]').forEach(function (cb) {
                cb.checked = selectedIds.indexOf(String(cb.value)) !== -1;
                cb.closest('.pm-item').className = 'pm-item' + (cb.checked ? ' selected' : '');
            });
        }, 600);

        document.getElementById('portais-form-title').textContent      = 'Editar Portal';
        document.getElementById('pbi-submit-label').textContent        = 'Salvar alterações';
        document.getElementById('pbi-cancel-btn').style.display        = '';
        document.getElementById('pbi-senha-field').style.display       = 'none';
        document.getElementById('pbi-nova-senha-field').style.display  = '';
        document.getElementById('portais-form-card').scrollIntoView({ behavior: 'smooth' });
    };

    window.pbiToggle = function (id) {
        fetch('/api/portais-bi.php?action=toggle', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: id}),
        })
        .then(function (r) { return r.json(); })
        .then(function (d) { if (d.sucesso) loadPortais(); else pbiShowMsg(d.erro || 'Erro', true); });
    };

    window.pbiDelete = function (id, nome) {
        if (!confirm('Excluir portal "' + nome + '"? Esta ação não pode ser desfeita.')) return;
        fetch('/api/portais-bi.php?action=delete', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: id}),
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.sucesso) { pbiShowMsg('Portal excluído.', false); loadPortais(); }
            else pbiShowMsg(d.erro || 'Erro ao excluir', true);
        })
        .catch(function () { pbiShowMsg('Erro de rede ao excluir.', true); });
    };

    window.pbiResetForm = function () {
        document.getElementById('pbi-edit-id').value    = '';
        document.getElementById('pbi-relatorio').value  = '';
        document.getElementById('pbi-slug').value       = '';
        document.getElementById('pbi-nome').value       = '';
        document.getElementById('pbi-senha').value      = '';
        document.getElementById('pbi-nova-senha').value = '';

        _filterLoaded = false;
        _tipo = 'parceiro';
        document.getElementById('pbi-tipo-parceiro').className    = 'portais-toggle-btn active';
        document.getElementById('pbi-tipo-oportunidade').className = 'portais-toggle-btn';
        document.getElementById('pbi-filtros-label').innerHTML =
            'Parceiros <span style="color:rgba(255,255,255,.25)">(selecione um ou mais)</span>';

        document.getElementById('pbi-extra-fields').style.display      = 'none';
        document.getElementById('portais-form-title').textContent      = 'Criar Portal';
        document.getElementById('pbi-submit-label').textContent        = 'Criar portal';
        document.getElementById('pbi-cancel-btn').style.display        = 'none';
        document.getElementById('pbi-senha-field').style.display       = '';
        document.getElementById('pbi-nova-senha-field').style.display  = 'none';
        pbiHideMsg();
    };

    // ── Copy (delegado) ────────────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.portais-copy-btn');
        if (!btn || !('copy' in btn.dataset)) return;
        navigator.clipboard.writeText(btn.dataset.copy).catch(function () {
            var el = document.createElement('textarea');
            el.value = btn.dataset.copy; document.body.appendChild(el);
            el.select(); document.execCommand('copy'); document.body.removeChild(el);
        });
        var icon = btn.querySelector('i');
        if (icon) { icon.className = 'fas fa-check'; setTimeout(function () { icon.className = 'fas fa-copy'; }, 1500); }
    });

    function pbiShowMsg(text, isErr) {
        var el = document.getElementById('pbi-msg');
        el.textContent = text;
        el.className = 'portais-msg ' + (isErr ? 'portais-msg-err' : 'portais-msg-ok');
        el.style.display = '';
    }
    function pbiHideMsg() { document.getElementById('pbi-msg').style.display = 'none'; }

    // ── Init ───────────────────────────────────────────────────────────────
    loadRelatorios();
    loadPortais();
})();
</script>

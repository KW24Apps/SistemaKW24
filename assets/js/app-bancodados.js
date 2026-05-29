/**
 * KW24 - Configuração específica: BancoDados
 */

function renderBancoDados(app, clienteId) {
    const config   = app.config_extra ? (typeof app.config_extra === 'string' ? JSON.parse(app.config_extra) : app.config_extra) : {};
    const entities = config.entities || [];
    const intervalo = config.intervalo_horas || 6;

    const listaHtml = entities.length
        ? entities.map((e, i) => _bdCardHtml(e, i, intervalo)).join('')
        : '<p style="color:#a0aec0;font-size:.85rem;text-align:center;padding:1rem 0">Nenhuma consulta configurada ainda.</p>';

    return `
        <div id="bd-config" data-cliente="${clienteId}" data-app="${app.id}">
            <p style="font-size:.85rem;color:#718096;margin-bottom:1rem">${app.descricao || ''}</p>

            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
                <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#a0aec0">Consultas configuradas</span>
                <button onclick="bdAbrirForm()" class="btn-primary" style="padding:.35rem .8rem;font-size:.8rem">
                    <i class="fas fa-plus"></i> Adicionar Consulta
                </button>
            </div>

            <div id="bd-lista">${listaHtml}</div>

            <!-- Formulário nova/editar consulta -->
            <div id="bd-form" style="display:none;border:1px dashed #0DC2FF;border-radius:8px;padding:1rem;background:#f0f9ff;margin-top:.75rem">
                <p id="bd-form-titulo" style="font-size:.8rem;font-weight:700;color:#086B8D;margin-bottom:.75rem">
                    <i class="fas fa-plus-circle"></i> Nova Consulta
                </p>

                <div style="display:grid;gap:.65rem">
                    <div>
                        <label style="font-size:.72rem;font-weight:700;color:#4a5568;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.25rem">Nome da tabela no banco *</label>
                        <input id="bd-table-name" type="text" class="form-input" placeholder="ex: negocio, oportunidades, faturas">
                    </div>
                    <div>
                        <label style="font-size:.72rem;font-weight:700;color:#4a5568;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.25rem">Entidade *</label>
                        <select id="bd-entity-select" class="form-input" onchange="bdCarregarFunis(this.value)">
                            <option value="">Carregando entidades...</option>
                        </select>
                    </div>
                    <div id="bd-funis-container" style="display:none">
                        <label style="font-size:.72rem;font-weight:700;color:#4a5568;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.4rem">
                            Funis <span style="font-weight:400;text-transform:none;color:#a0aec0">— deixe todos desmarcados para sincronizar todos</span>
                        </label>
                        <div id="bd-funis-lista" style="display:grid;grid-template-columns:1fr 1fr;gap:.3rem;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:.6rem"></div>
                    </div>
                    <div id="bd-funis-loading" style="display:none;font-size:.8rem;color:#a0aec0"><i class="fas fa-spinner fa-spin"></i> Carregando funis...</div>
                    <div id="bd-form-erro" style="color:#e53e3e;font-size:.8rem;display:none"></div>
                    <div style="display:flex;gap:.6rem;justify-content:flex-end">
                        <button onclick="bdFecharForm()" class="btn-cancelar-edit" style="padding:.45rem .9rem;font-size:.82rem">Cancelar</button>
                        <button onclick="bdSalvarEntidade()" class="btn-primary" style="padding:.45rem .9rem;font-size:.82rem">
                            <i class="fas fa-check"></i> <span id="bd-btn-salvar-label">Adicionar</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Agendamento -->
            <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid #e2e8f0">
                <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#a0aec0;display:block;margin-bottom:.6rem">Agendamento</span>
                <div style="display:flex;align-items:center;gap:.75rem">
                    <label style="font-size:.875rem;color:#4a5568;white-space:nowrap">Atualizar a cada</label>
                    <input id="bd-intervalo" type="number" min="2" step="1" value="${intervalo}"
                        class="form-input" style="width:80px;text-align:center"
                        oninput="bdValidarIntervalo(this)">
                    <label style="font-size:.875rem;color:#4a5568">horas</label>
                    <span style="font-size:.75rem;color:#a0aec0">(mínimo 2h)</span>
                </div>
                <div id="bd-intervalo-erro" style="color:#e53e3e;font-size:.78rem;margin-top:.3rem;display:none">
                    Intervalo mínimo é de 2 horas.
                </div>
            </div>

            <!-- Barra de salvar config -->
            <div id="bd-save-bar" style="margin-top:1rem;padding-top:1rem;border-top:1px solid #e2e8f0;display:flex;align-items:center;gap:.75rem;justify-content:flex-end">
                <span id="bd-save-msg" style="font-size:.8rem;color:#718096"></span>
                <button onclick="bdSalvarConfig()" class="btn-salvar" style="padding:.5rem 1.25rem"><i class="fas fa-check"></i> Salvar configuração</button>
            </div>
        </div>`;
}

// Gera o HTML de um card de consulta (usado na renderização inicial e no bdRenderLista)
function _bdCardHtml(e, i, intervalo) {
    const funisTexto = e.categories && e.categories.length
        ? e.categories.map(c => c.name || c).join(', ')
        : 'Todos os funis';
    const intervaloAtual = intervalo ?? parseInt(document.getElementById('bd-intervalo')?.value || 6);
    return `
        <div style="border:1px solid #e2e8f0;border-radius:8px;padding:.85rem 1rem;margin-bottom:.6rem;background:#f8fafc">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem">
                <div style="flex:1;min-width:0">
                    <div style="font-weight:700;font-size:.9rem;color:#2d3748">
                        <i class="fas fa-table" style="color:#0DC2FF;margin-right:.4rem"></i>
                        ${e.table_base_name}
                        <span style="font-weight:400;font-size:.75rem;color:#a0aec0;margin-left:.5rem">· atualiza a cada ${intervaloAtual}h</span>
                    </div>
                    <div style="font-size:.78rem;color:#a0aec0;margin-top:.25rem;white-space:normal">
                        ${e.label} · ${funisTexto}
                    </div>
                </div>
                <div style="display:flex;gap:.4rem;flex-shrink:0">
                    <button onclick="bdEditarEntidade(${i})"
                        style="border:none;background:#e9f5ff;color:#086B8D;border-radius:6px;padding:.3rem .6rem;cursor:pointer;font-size:.75rem">
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                    <button onclick="bdRemoverEntidade(${i})"
                        style="border:none;background:#fee2e2;color:#c53030;border-radius:6px;padding:.3rem .6rem;cursor:pointer;font-size:.75rem">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>`;
}

// Estado
let bdEntidades     = [];
let bdClienteId     = null;
let bdAppId         = null;
let bdEditandoIndex = null;

function bdInicializar(app, clienteId) {
    bdClienteId = clienteId;
    bdAppId     = app.id;
    const config = app.config_extra
        ? (typeof app.config_extra === 'string' ? JSON.parse(app.config_extra) : app.config_extra)
        : {};
    bdEntidades = config.entities ? JSON.parse(JSON.stringify(config.entities)) : [];
}

function bdAbrirForm() {
    bdEditandoIndex = null;
    document.getElementById('bd-form-titulo').innerHTML      = '<i class="fas fa-plus-circle"></i> Nova Consulta';
    document.getElementById('bd-btn-salvar-label').textContent = 'Adicionar';
    document.getElementById('bd-form').style.display         = 'block';
    document.getElementById('bd-funis-container').style.display = 'none';
    document.getElementById('bd-funis-loading').style.display   = 'none';
    document.getElementById('bd-form-erro').style.display       = 'none';
    document.getElementById('bd-table-name').value = '';

    _bdCarregarEntidades(null);
    document.getElementById('bd-table-name').focus();
}

function bdEditarEntidade(index) {
    const e = bdEntidades[index];
    bdEditandoIndex = index;

    document.getElementById('bd-form-titulo').innerHTML      = '<i class="fas fa-pencil-alt"></i> Editar Consulta';
    document.getElementById('bd-btn-salvar-label').textContent = 'Salvar';
    document.getElementById('bd-form').style.display         = 'block';
    document.getElementById('bd-funis-container').style.display = 'none';
    document.getElementById('bd-funis-loading').style.display   = 'none';
    document.getElementById('bd-form-erro').style.display       = 'none';
    document.getElementById('bd-table-name').value = e.table_base_name;

    // Carrega entidades e depois seleciona a correta + pré-marca funis
    _bdCarregarEntidades(e.id, () => bdCarregarFunis(e.id, e.categories || []));
}

function _bdCarregarEntidades(selecionarId, onLoad) {
    const select = document.getElementById('bd-entity-select');
    select.innerHTML = '<option value="">Carregando...</option>';

    fetch(`/api/bitrix-entidades.php?cliente_id=${bdClienteId}&aplicacao_id=${bdAppId}`, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (data.erro) { select.innerHTML = `<option value="">Erro: ${data.erro}</option>`; return; }
            select.innerHTML = '<option value="">Selecione a entidade...</option>' +
                data.entidades.map(e => `<option value="${e.id}" data-title="${e.title}">${e.title}</option>`).join('');
            if (selecionarId) {
                select.value = selecionarId;
                if (onLoad) onLoad();
            }
        })
        .catch(() => { select.innerHTML = '<option value="">Erro ao carregar</option>'; });
}

// marcar = [{id,name}] ou [] — pre-checa checkboxes no modo edição
function bdCarregarFunis(entityId, marcar) {
    const container = document.getElementById('bd-funis-container');
    const loading   = document.getElementById('bd-funis-loading');
    const lista     = document.getElementById('bd-funis-lista');

    if (!entityId) { container.style.display = 'none'; return; }

    container.style.display = 'none';
    loading.style.display   = 'block';

    const idsMarcados = (marcar || []).map(c => typeof c === 'object' ? c.id : c);

    fetch(`/api/bitrix-funis.php?cliente_id=${bdClienteId}&aplicacao_id=${bdAppId}&entity_id=${entityId}`, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            loading.style.display = 'none';
            if (!data.funis || !data.funis.length) { container.style.display = 'none'; return; }
            lista.innerHTML = data.funis.map(f => `
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;color:#2d3748;cursor:pointer;padding:.15rem 0">
                    <input type="checkbox" value="${f.id}" ${idsMarcados.includes(f.id) ? 'checked' : ''} style="accent-color:#0DC2FF">
                    ${f.nome}
                </label>`).join('');
            container.style.display = 'block';
        })
        .catch(() => { loading.style.display = 'none'; });
}

function bdFecharForm() {
    bdEditandoIndex = null;
    document.getElementById('bd-form').style.display            = 'none';
    document.getElementById('bd-form-erro').style.display       = 'none';
    document.getElementById('bd-funis-container').style.display = 'none';
    const tableEl = document.getElementById('bd-table-name'); if (tableEl) tableEl.value = '';
    const sel     = document.getElementById('bd-entity-select'); if (sel) sel.value = '';
}

function bdSalvarEntidade() {
    const tableName   = document.getElementById('bd-table-name').value.trim();
    const select      = document.getElementById('bd-entity-select');
    const entityId    = parseInt(select.value);
    const entityTitle = select.options[select.selectedIndex]?.getAttribute('data-title') || '';
    const erro        = document.getElementById('bd-form-erro');

    if (!tableName || !entityId) {
        erro.textContent = 'Nome da tabela e entidade são obrigatórios.';
        erro.style.display = 'block'; return;
    }

    const categories = [];
    document.querySelectorAll('#bd-funis-lista input[type=checkbox]:checked').forEach(cb => {
        categories.push({ id: parseInt(cb.value), name: cb.closest('label').textContent.trim() });
    });

    const entrada = {
        key:             `crm_${entityId}_${Date.now()}`,
        type:            'crm',
        id:              entityId,
        label:           entityTitle,
        table_base_name: tableName,
        categories
    };

    if (bdEditandoIndex !== null) {
        bdEntidades[bdEditandoIndex] = entrada;
    } else {
        bdEntidades.push(entrada);
    }

    bdFecharForm();
    bdRenderLista();
    document.getElementById('bd-save-bar').style.display = 'flex';
}

function bdRemoverEntidade(index) {
    bdEntidades.splice(index, 1);
    bdRenderLista();
    document.getElementById('bd-save-bar').style.display = 'flex';
}

function bdRenderLista() {
    const lista = document.getElementById('bd-lista');
    if (!lista) return;
    lista.innerHTML = bdEntidades.length
        ? bdEntidades.map((e, i) => _bdCardHtml(e, i, null)).join('')
        : '<p style="color:#a0aec0;font-size:.85rem;text-align:center;padding:1rem 0">Nenhuma consulta configurada ainda.</p>';
}

function bdValidarIntervalo(el) {
    const val  = parseInt(el.value);
    const erro = document.getElementById('bd-intervalo-erro');
    if (val < 2 || isNaN(val)) {
        erro.style.display = 'block';
        el.value = 2;
    } else {
        erro.style.display = 'none';
    }
}

function bdSalvarConfig() {
    const el  = document.getElementById('bd-config');
    const cId = el?.getAttribute('data-cliente');
    const aId = el?.getAttribute('data-app');
    const msg = document.getElementById('bd-save-msg');

    if (!cId || !aId) return;
    msg.textContent = 'Salvando...';

    fetch('/api/cliente-app-config.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            cliente_id:   parseInt(cId),
            aplicacao_id: parseInt(aId),
            config_extra: {
                entities:        bdEntidades,
                intervalo_horas: Math.max(2, parseInt(document.getElementById('bd-intervalo')?.value || 6))
            }
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            msg.textContent = '✓ Salvo com sucesso';
            document.getElementById('bd-save-bar').style.display = 'none';
            setTimeout(() => msg.textContent = '', 2500);
        } else { msg.textContent = data.erro || 'Erro ao salvar.'; }
    })
    .catch(() => { msg.textContent = 'Erro de conexão.'; });
}

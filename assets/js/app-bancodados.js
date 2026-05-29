/**
 * KW24 - Configuração específica: BancoDados
 * Renderiza a tela de config dentro do modal de app do cliente
 */

function renderBancoDados(app, clienteId) {
    const config   = app.config_extra ? (typeof app.config_extra === 'string' ? JSON.parse(app.config_extra) : app.config_extra) : {};
    const entities = config.entities || [];

    const listaHtml = entities.length
        ? entities.map((e, i) => `
            <div style="border:1px solid #e2e8f0;border-radius:8px;padding:.85rem 1rem;margin-bottom:.6rem;background:#f8fafc">
                <div style="display:flex;align-items:center;justify-content:space-between">
                    <div>
                        <div style="font-weight:700;font-size:.9rem;color:#2d3748">
                            <i class="fas fa-table" style="color:#0DC2FF;margin-right:.4rem"></i>
                            ${e.table_base_name}
                        </div>
                        <div style="font-size:.78rem;color:#a0aec0;margin-top:.2rem">
                            ${e.label || e.type} · ID ${e.id}
                            ${e.categories && e.categories.length ? `· Funis: ${e.categories.join(', ')}` : '· Todos os funis'}
                        </div>
                    </div>
                    <button onclick="bdRemoverEntidade(${i})"
                        style="border:none;background:#fee2e2;color:#c53030;border-radius:6px;padding:.3rem .6rem;cursor:pointer;font-size:.75rem">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>`).join('')
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

            <!-- Formulário de nova consulta (oculto por padrão) -->
            <div id="bd-form" style="display:none;border:1px dashed #0DC2FF;border-radius:8px;padding:1rem;background:#f0f9ff;margin-top:.75rem">
                <p style="font-size:.8rem;font-weight:700;color:#086B8D;margin-bottom:.75rem"><i class="fas fa-plus-circle"></i> Nova Consulta</p>

                <div style="display:grid;gap:.65rem">
                    <div>
                        <label style="font-size:.72rem;font-weight:700;color:#4a5568;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.25rem">Nome da tabela no banco *</label>
                        <input id="bd-table-name" type="text" class="form-input" placeholder="ex: negocio, oportunidades, faturas">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem">
                        <div>
                            <label style="font-size:.72rem;font-weight:700;color:#4a5568;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.25rem">Tipo *</label>
                            <select id="bd-type" class="form-input">
                                <option value="crm">CRM</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:.72rem;font-weight:700;color:#4a5568;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.25rem">ID da entidade *</label>
                            <input id="bd-entity-id" type="number" class="form-input" placeholder="ex: 2, 1126, 31">
                        </div>
                    </div>
                    <div>
                        <label style="font-size:.72rem;font-weight:700;color:#4a5568;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.25rem">Label amigável</label>
                        <input id="bd-label" type="text" class="form-input" placeholder="ex: Negócios, Oportunidades">
                    </div>
                    <div>
                        <label style="font-size:.72rem;font-weight:700;color:#4a5568;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.25rem">
                            Funis/Categorias (IDs separados por vírgula)
                            <span style="font-weight:400;text-transform:none;color:#a0aec0"> — deixe vazio para todos</span>
                        </label>
                        <input id="bd-categories" type="text" class="form-input" placeholder="ex: 53, 17, 15, 51">
                    </div>
                    <div id="bd-form-erro" style="color:#e53e3e;font-size:.8rem;display:none"></div>
                    <div style="display:flex;gap:.6rem;justify-content:flex-end">
                        <button onclick="bdFecharForm()" class="btn-cancelar-edit" style="padding:.45rem .9rem;font-size:.82rem">Cancelar</button>
                        <button onclick="bdSalvarEntidade()" class="btn-primary" style="padding:.45rem .9rem;font-size:.82rem">Adicionar</button>
                    </div>
                </div>
            </div>

            <!-- Agendamento -->
            <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid #e2e8f0">
                <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#a0aec0;display:block;margin-bottom:.6rem">Agendamento</span>
                <div style="display:flex;align-items:center;gap:.75rem">
                    <label style="font-size:.875rem;color:#4a5568;white-space:nowrap">Atualizar a cada</label>
                    <input id="bd-intervalo" type="number" min="2" step="1" value="${config.intervalo_horas || 6}"
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

// Estado temporário das entidades no formulário
let bdEntidades = [];

function bdInicializar(app) {
    const config = app.config_extra
        ? (typeof app.config_extra === 'string' ? JSON.parse(app.config_extra) : app.config_extra)
        : {};
    bdEntidades = config.entities ? JSON.parse(JSON.stringify(config.entities)) : [];
}

function bdAbrirForm() {
    document.getElementById('bd-form').style.display = 'block';
    document.getElementById('bd-table-name').focus();
}

function bdFecharForm() {
    document.getElementById('bd-form').style.display = 'none';
    document.getElementById('bd-form-erro').style.display = 'none';
    ['bd-table-name','bd-entity-id','bd-label','bd-categories'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
}

function bdSalvarEntidade() {
    const tableName  = document.getElementById('bd-table-name').value.trim();
    const type       = document.getElementById('bd-type').value;
    const entityId   = parseInt(document.getElementById('bd-entity-id').value);
    const label      = document.getElementById('bd-label').value.trim();
    const catsRaw    = document.getElementById('bd-categories').value.trim();
    const erro       = document.getElementById('bd-form-erro');

    if (!tableName || !entityId) {
        erro.textContent = 'Nome da tabela e ID são obrigatórios.';
        erro.style.display = 'block'; return;
    }

    const categories = catsRaw
        ? catsRaw.split(',').map(c => parseInt(c.trim())).filter(n => !isNaN(n))
        : [];

    bdEntidades.push({
        key:             `${type}_${entityId}_${Date.now()}`,
        type,
        id:              entityId,
        label:           label || tableName,
        table_base_name: tableName,
        categories
    });

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
        ? bdEntidades.map((e, i) => `
            <div style="border:1px solid #e2e8f0;border-radius:8px;padding:.85rem 1rem;margin-bottom:.6rem;background:#f8fafc">
                <div style="display:flex;align-items:center;justify-content:space-between">
                    <div>
                        <div style="font-weight:700;font-size:.9rem;color:#2d3748">
                            <i class="fas fa-table" style="color:#0DC2FF;margin-right:.4rem"></i>
                            ${e.table_base_name}
                        </div>
                        <div style="font-size:.78rem;color:#a0aec0;margin-top:.2rem">
                            ${e.label} · ID ${e.id}
                            ${e.categories && e.categories.length ? `· Funis: ${e.categories.join(', ')}` : '· Todos os funis'}
                        </div>
                    </div>
                    <button onclick="bdRemoverEntidade(${i})"
                        style="border:none;background:#fee2e2;color:#c53030;border-radius:6px;padding:.3rem .6rem;cursor:pointer;font-size:.75rem">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>`).join('')
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
    const el     = document.getElementById('bd-config');
    const cId    = el?.getAttribute('data-cliente');
    const aId    = el?.getAttribute('data-app');
    const msg    = document.getElementById('bd-save-msg');

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

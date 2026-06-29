/**
 * KW24 - Painel lateral de cliente
 * Carregado no index.php para estar sempre disponível
 */

// ===== CONFIRMAÇÃO CUSTOMIZADA =====
function kwConfirm(msg, titulo = 'Confirmar ação', tipo = 'danger') {
    return new Promise(resolve => {
        const overlay = document.getElementById('kw-confirm-overlay');
        const box     = document.getElementById('kw-confirm-box');
        const icon    = document.getElementById('kw-confirm-icon');
        const titleEl = document.getElementById('kw-confirm-title');
        const msgEl   = document.getElementById('kw-confirm-msg');
        const btnOk   = document.getElementById('kw-confirm-ok');
        const btnCancel = document.getElementById('kw-confirm-cancel');

        titleEl.textContent = titulo;
        msgEl.textContent   = msg;

        // Estilos por tipo
        if (tipo === 'danger') {
            icon.style.background  = '#fee2e2';
            icon.style.color       = '#c53030';
            icon.innerHTML         = '<i class="fas fa-exclamation-triangle"></i>';
            btnOk.style.background = '#e53e3e';
            btnOk.onmouseover      = () => btnOk.style.background = '#c53030';
            btnOk.onmouseout       = () => btnOk.style.background = '#e53e3e';
        } else {
            icon.style.background  = '#d1fae5';
            icon.style.color       = '#065f46';
            icon.innerHTML         = '<i class="fas fa-check-circle"></i>';
            btnOk.style.background = '#0DC2FF';
            btnOk.onmouseover      = () => btnOk.style.background = '#086B8D';
            btnOk.onmouseout       = () => btnOk.style.background = '#0DC2FF';
        }

        overlay.style.display = 'flex';

        const close = (result) => {
            overlay.style.display = 'none';
            btnOk.onclick     = null;
            btnCancel.onclick = null;
            resolve(result);
        };

        btnOk.onclick     = () => close(true);
        btnCancel.onclick = () => close(false);
        overlay.onclick   = (e) => { if (e.target === overlay) close(false); };
    });
}

const iconeApp = {
    clicksign:   'fas fa-file-signature',
    deal:        'fas fa-handshake',
    task:        'fas fa-tasks',
    company:     'fas fa-building',
    omie:        'fas fa-calculator',
    receita:     'fas fa-search',
    import:      'fas fa-upload',
    disk:        'fas fa-hdd',
    calcdata:    'fas fa-calendar-alt',
    mediahora:   'fas fa-clock',
    scheduler:   'fas fa-robot',
    geraroptnd:  'fas fa-magic',
    extenso:     'fas fa-font',
    validar_cnpj:'fas fa-id-card'
};

let clienteIdAtual  = null;
let edicoesPendentes = {};
let todasApps       = [];
let appsAtivas      = [];

// Modal de ativação com webhook + descricao
function kwAtivarApp(appNome) {
    return new Promise(resolve => {
        const overlay   = document.getElementById('kw-ativar-overlay');
        const titleEl   = document.getElementById('kw-ativar-title');
        const msgEl     = document.getElementById('kw-ativar-msg');
        const input     = document.getElementById('kw-ativar-webhook');
        const erro      = document.getElementById('kw-ativar-erro');
        const btnOk     = document.getElementById('kw-ativar-ok');
        const btnCancel = document.getElementById('kw-ativar-cancel');

        // Injeta campo de descrição se ainda não existir
        let descInput = document.getElementById('kw-ativar-descricao');
        if (!descInput) {
            const descWrap = document.createElement('div');
            descWrap.style.cssText = 'margin-top:.75rem';
            descWrap.innerHTML = `
                <label style="display:block;font-size:.75rem;font-weight:700;color:#4a5568;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.4rem">Descrição <small style="font-weight:400;color:#a0aec0;text-transform:none">(opcional — ex: Comercial, Operacional)</small></label>
                <input id="kw-ativar-descricao" type="text" placeholder="Ex: Comercial"
                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:.6rem .75rem;font-size:.875rem;color:#2d3748;outline:none;font-family:inherit;box-sizing:border-box"
                    onfocus="this.style.borderColor='#0DC2FF'" onblur="this.style.borderColor='#e2e8f0'">`;
            input.parentNode.insertAdjacentElement('afterend', descWrap);
            descInput = document.getElementById('kw-ativar-descricao');
        }

        titleEl.textContent   = 'Ativar aplicação';
        msgEl.textContent     = `Adicionar "${appNome}" para este cliente?`;
        input.value           = '';
        descInput.value       = '';
        erro.style.display    = 'none';
        overlay.style.display = 'flex';
        input.focus();

        const close = (result) => {
            overlay.style.display = 'none';
            btnOk.onclick     = null;
            btnCancel.onclick = null;
            overlay.onclick   = null;
            resolve(result);
        };

        btnOk.onclick = () => {
            const wh = input.value.trim();
            if (!wh) { erro.style.display = 'block'; return; }
            erro.style.display = 'none';
            close({ webhook: wh, descricao: descInput.value.trim() || null });
        };

        btnCancel.onclick = () => close(null);
        overlay.onclick   = (e) => { if (e.target === overlay) close(null); };
        input.onkeydown   = (e) => { if (e.key === 'Enter') btnOk.click(); };
    });
}

// ===== ABRIR / FECHAR PAINEL =====

function abrirCliente(id) {
    clienteIdAtual   = id;
    edicoesPendentes = {};
    cancelarEdicoes();

    const overlay = document.getElementById('cliente-overlay');
    const panel   = document.getElementById('cliente-panel');
    if (!overlay || !panel) return;

    overlay.classList.add('open');
    panel.classList.add('open');
    document.getElementById('panel-loading').style.display  = 'flex';
    document.getElementById('panel-conteudo').style.display = 'none';

    fetch('/api/cliente-detalhe.php?id=' + id, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (data.erro) { alert(data.erro); fecharPainel(); return; }
            preencherPainel(data.cliente, data.aplicacoes);
        })
        .catch(err => { console.error('Painel erro:', err); fecharPainel(); });
}

function fecharPainel() {
    const overlay = document.getElementById('cliente-overlay');
    const panel   = document.getElementById('cliente-panel');
    if (overlay) overlay.classList.remove('open');
    if (panel)   panel.classList.remove('open');
    cancelarEdicoes();
    modoNovo = false;
    // Restaura largura original do painel
    document.getElementById('cliente-panel').style.width = '';
    // Restaura menu ⋮
    const btnMenu = document.getElementById('btn-menu-cliente');
    if (btnMenu) btnMenu.style.visibility = '';
    // Restaura botão salvar
    const btnSalvar = document.querySelector('#panel-save-bar .btn-salvar');
    if (btnSalvar) btnSalvar.innerHTML = '<i class="fas fa-check"></i> Salvar';
}

function salvarNovoCliente() {
    const campos = {
        nome:        document.getElementById('novo-nome')?.value.trim(),
        cnpj:        document.getElementById('novo-cnpj')?.value.trim(),
        telefone:    document.getElementById('novo-telefone')?.value.trim(),
        email:       document.getElementById('novo-email')?.value.trim(),
        endereco:    document.getElementById('novo-endereco')?.value.trim(),
        link_bitrix: document.getElementById('novo-link-bitrix')?.value.trim(),
        id_bitrix:   document.getElementById('novo-id-bitrix')?.value.trim() || null,
        org_id:      document.getElementById('novo-org-id')?.value || null,
    };

    const obrigatorios = ['nome','cnpj','telefone','email','endereco','link_bitrix'];
    for (const c of obrigatorios) {
        if (!campos[c]) {
            const erro = document.getElementById('novo-cliente-erro');
            erro.textContent = `Campo obrigatório: ${c.replace(/_/g,' ')}`;
            erro.style.display = 'block';
            return;
        }
    }

    const msg = document.getElementById('save-msg');
    msg.textContent = 'Cadastrando...';

    fetch('/api/cliente-criar.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(campos)
    })
    .then(r => r.json())
    .then(res => {
        if (res.sucesso) {
            fecharPainel();
            // Mostra chave gerada antes de redirecionar
            if (res.chave_acesso) {
                const overlay = document.createElement('div');
                overlay.style.cssText = 'position:fixed;inset:0;background:rgba(6,25,32,.6);backdrop-filter:blur(4px);z-index:9998;display:flex;align-items:center;justify-content:center';
                overlay.innerHTML = `
                    <div style="background:#fff;border-radius:16px;padding:2rem;width:440px;max-width:92vw;box-shadow:0 24px 60px rgba(0,0,0,.25);animation:kwPop .18s ease">
                        <div style="width:48px;height:48px;border-radius:50%;background:#d1fae5;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;font-size:1.3rem;color:#065f46"><i class="fas fa-check-circle"></i></div>
                        <h3 style="text-align:center;font-family:'Rubik',sans-serif;font-size:1rem;font-weight:700;color:#1a202c;margin:0 0 .35rem">Cliente cadastrado!</h3>
                        <p style="text-align:center;font-size:.85rem;color:#718096;margin:0 0 .75rem">Chave de acesso gerada automaticamente:</p>
                        <div style="display:flex;align-items:center;gap:.5rem;background:#f8fafc;border-radius:8px;padding:.6rem .75rem;border:1px solid #e2e8f0;margin-bottom:1.25rem">
                            <span style="font-family:monospace;font-size:.82rem;color:#2d3748;word-break:break-all;flex:1">${_esc(res.chave_acesso)}</span>
                            <button onclick="copiarChaveApp('${_esc(res.chave_acesso)}')" style="background:#0DC2FF;color:#fff;border:none;border-radius:6px;padding:.35rem .65rem;font-size:.8rem;cursor:pointer;font-weight:600;flex-shrink:0"><i class="fas fa-copy"></i></button>
                        </div>
                        <button onclick="this.closest('[style*=fixed]').remove();window.location.href='?page=cadastro'"
                            style="width:100%;padding:.65rem;border:none;border-radius:8px;background:#0DC2FF;color:#fff;font-size:.875rem;cursor:pointer;font-weight:700">OK, ir para Cadastro</button>
                    </div>`;
                document.body.appendChild(overlay);
            } else {
                window.location.href = '?page=cadastro';
            }
        } else {
            const erro = document.getElementById('novo-cliente-erro');
            erro.textContent = res.erro || 'Erro ao cadastrar.';
            erro.style.display = 'block';
            msg.textContent = '';
        }
    })
    .catch(() => { msg.textContent = 'Erro de conexão.'; });
}

// ===== PREENCHER PAINEL =====

let _clienteOrgIdAtual = null;

function preencherPainel(c, apps) {
    document.getElementById('panel-avatar').textContent  = (c.nome || '--').substring(0, 2).toUpperCase();
    document.getElementById('panel-nome').textContent    = c.nome || '—';
    document.getElementById('panel-cnpj').textContent    = c.cnpj ? 'CNPJ: ' + c.cnpj : '';

    document.getElementById('pf-id').textContent         = c.id;
    document.getElementById('pf-nome').textContent       = c.nome         || '—';
    document.getElementById('pf-cnpj').textContent       = c.cnpj         || '—';
    document.getElementById('pf-telefone').textContent   = c.telefone     || '—';
    document.getElementById('pf-email').textContent      = c.email        || '—';
    document.getElementById('pf-endereco').textContent   = c.endereco     || '—';
    document.getElementById('pf-bitrix').textContent     = c.link_bitrix  || '—';
    document.getElementById('pf-id-bitrix').textContent  = c.id_bitrix    || '—';

    const _pfChaveWrap = document.getElementById('pf-chave-wrap');
    if (_pfChaveWrap) {
        if (c.chave_acesso) {
            const _cha = _esc(c.chave_acesso);
            _pfChaveWrap.innerHTML = `<div style="display:flex;align-items:center;gap:.5rem;background:#f8fafc;border-radius:6px;padding:.4rem .65rem;border:1px solid #e2e8f0"><span style="font-family:monospace;font-size:.78rem;color:#2d3748;word-break:break-all;flex:1">${_cha}</span><button onclick="copiarChaveApp('${_cha}')" title="Copiar chave de acesso" style="background:none;border:none;cursor:pointer;color:#0DC2FF;font-size:.8rem;padding:.1rem .25rem;flex-shrink:0"><i class="fas fa-copy"></i></button></div>`;
        } else {
            _pfChaveWrap.innerHTML = `<button onclick="gerarChaveAcesso()" style="background:none;border:1px solid #0DC2FF;color:#0DC2FF;border-radius:6px;padding:.3rem .7rem;font-size:.78rem;cursor:pointer;font-weight:600"><i class="fas fa-magic"></i> Gerar chave</button>`;
        }
    }

    _clienteOrgIdAtual = c.org_id || null;
    preencherOrgDropdown('pf-org-select', c.org_id);

    appsAtivas = apps || [];
    renderAppsAtivas(appsAtivas);

    document.getElementById('panel-loading').style.display  = 'none';
    document.getElementById('panel-conteudo').style.display = 'block';
}

function preencherOrgDropdown(selectId, orgIdSelecionado) {
    const sel = document.getElementById(selectId);
    if (!sel) return;
    fetch('/api/organizacoes.php?action=list', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(orgs => {
            sel.innerHTML = '<option value="">— Nenhuma —</option>';
            (orgs || []).forEach(o => {
                const opt = document.createElement('option');
                opt.value       = o.id;
                opt.textContent = o.nome + (o.ativo ? '' : ' (inativa)');
                if (String(o.id) === String(orgIdSelecionado)) opt.selected = true;
                sel.appendChild(opt);
            });
        })
        .catch(() => {});
}

function gerarChaveAcesso() {
    if (!clienteIdAtual) return;
    fetch('/api/cliente-gerar-chave.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cliente_id: clienteIdAtual })
    }).then(r => r.json()).then(data => {
        if (data.sucesso && data.chave_acesso) {
            const wrap = document.getElementById('pf-chave-wrap');
            if (wrap) {
                const cha = _esc(data.chave_acesso);
                wrap.innerHTML = `<div style="display:flex;align-items:center;gap:.5rem;background:#f8fafc;border-radius:6px;padding:.4rem .65rem;border:1px solid #e2e8f0"><span style="font-family:monospace;font-size:.78rem;color:#2d3748;word-break:break-all;flex:1">${cha}</span><button onclick="copiarChaveApp('${cha}')" title="Copiar chave de acesso" style="background:none;border:none;cursor:pointer;color:#0DC2FF;font-size:.8rem;padding:.1rem .25rem;flex-shrink:0"><i class="fas fa-copy"></i></button></div>`;
            }
            mostrarChaveGerada(data.chave_acesso, 'Cliente');
        } else {
            alert(data.erro || 'Erro ao gerar chave.');
        }
    }).catch(() => alert('Erro de conexão.'));
}

function atualizarOrg(orgId) {
    if (!clienteIdAtual) return;
    fetch('/api/cliente-atualizar.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: clienteIdAtual, org_id: orgId || null })
    }).then(r => r.json()).then(d => {
        if (!d.sucesso) alert(d.erro || 'Erro ao atualizar organização.');
    });
}

function _esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function _chaveDisplay(chave) {
    if (!chave) return '—';
    if (chave.length <= 5) return `<strong style="color:#0DC2FF">${_esc(chave)}</strong>`;
    return _esc(chave.slice(0, -5)) + `<strong style="color:#0DC2FF">${_esc(chave.slice(-5))}</strong>`;
}

function renderAppsAtivas(apps) {
    appsAtivas = apps || [];
    const lista = document.getElementById('panel-apps-lista');
    if (!lista) return;

    if (!apps || !apps.length) {
        lista.innerHTML = '<p style="color:#a0aec0;font-size:.85rem">Nenhuma aplicação ativa.<br>Clique em <strong>Ativar</strong> para adicionar.</p>';
        return;
    }

    lista.innerHTML = apps.map((a, i) => `
        <div class="app-card" data-app-index="${i}" style="${!a.ativo ? 'opacity:.55;filter:grayscale(.5)' : ''}">
            <div class="app-card-icon"><i class="${iconeApp[a.slug] || 'fas fa-puzzle-piece'}"></i></div>
            <div class="app-card-info">
                <div class="app-card-name">${_esc(a.nome)}${a.descricao ? ' <small style="color:#a0aec0;font-weight:400">· ' + _esc(a.descricao) + '</small>' : ''}</div>
                <div class="app-card-slug">${_esc(a.slug)}</div>
                ${a.chave ? `<div style="display:flex;align-items:center;gap:.35rem;margin-top:.25rem">
                    <span style="font-family:monospace;font-size:.7rem;background:#f0f4f8;padding:.1rem .4rem;border-radius:4px;letter-spacing:.03em;color:#718096">${_chaveDisplay(a.chave)}</span>
                    <button onclick="event.stopPropagation();copiarChaveApp('${_esc(a.chave)}')" title="Copiar chave" style="background:none;border:none;cursor:pointer;color:#0DC2FF;font-size:.75rem;padding:.1rem .2rem"><i class="fas fa-copy"></i></button>
                </div>` : ''}
            </div>
            ${a.ativo
                ? '<span class="badge-app">Ativo</span>'
                : '<span style="font-size:.7rem;font-weight:600;color:#a0aec0;background:#f0f4f8;padding:.2rem .6rem;border-radius:20px">Bloqueado</span>'}
        </div>`).join('');

    lista.querySelectorAll('.app-card').forEach(card => {
        card.addEventListener('click', () => {
            const idx = parseInt(card.getAttribute('data-app-index'));
            abrirModalApp(appsAtivas[idx]);
        });
    });
}

function copiarChaveApp(chave) {
    navigator.clipboard.writeText(chave).then(() => {
        // feedback temporário
        const tmp = document.createElement('div');
        tmp.textContent = '✓ Chave copiada';
        tmp.style.cssText = 'position:fixed;bottom:1.5rem;left:50%;transform:translateX(-50%);background:#1a202c;color:#fff;padding:.5rem 1.1rem;border-radius:8px;font-size:.82rem;z-index:9999;pointer-events:none;opacity:1;transition:opacity .4s';
        document.body.appendChild(tmp);
        setTimeout(() => { tmp.style.opacity = '0'; setTimeout(() => tmp.remove(), 400); }, 1800);
    });
}

// ===== MODAL CONFIG APP =====

function abrirModalApp(app) {
    document.getElementById('app-modal-icon').innerHTML    = `<i class="${iconeApp[app.slug] || 'fas fa-puzzle-piece'}"></i>`;
    document.getElementById('app-modal-nome').textContent  = app.nome;
    document.getElementById('app-modal-slug').textContent  = app.slug;
    // Roteamento por slug — cada app tem sua tela específica
    let configHtml;
    if (app.slug === 'BancoDados' && typeof renderBancoDados === 'function') {
        bdInicializar(app, clienteIdAtual);
        configHtml = renderBancoDados(app, clienteIdAtual);
    } else {
        configHtml = `
            <p style="color:#718096;font-size:.875rem;margin-bottom:1rem">${app.descricao || ''}</p>
            <div style="padding:2rem;background:#f8fafc;border-radius:8px;border:1px dashed #cbd5e0;text-align:center;color:#a0aec0;margin-bottom:1.25rem">
                <i class="fas fa-cog" style="font-size:2.5rem;display:block;margin-bottom:.75rem"></i>
                <strong>Configurações em construção</strong><br>
                <span style="font-size:.8rem">As configurações de <strong>${app.nome}</strong> serão implementadas aqui.</span>
            </div>`;
    }
    // Seção chave de acesso desta instância (read-only)
    const chaveHtml = app.chave ? `
        <div style="margin-bottom:1rem;padding:.75rem;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0">
            <label style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#a0aec0;display:block;margin-bottom:.4rem">
                <i class="fas fa-key" style="margin-right:.3rem;color:#0DC2FF"></i> Chave de acesso desta aplicação
            </label>
            <div style="display:flex;align-items:center;gap:.5rem">
                <span style="font-family:monospace;font-size:.8rem;color:#2d3748;word-break:break-all;flex:1">${_chaveDisplay(app.chave)}</span>
                <button onclick="copiarChaveApp('${_esc(app.chave)}')" title="Copiar chave"
                    style="flex-shrink:0;background:#0DC2FF;color:#fff;border:none;border-radius:6px;padding:.35rem .65rem;font-size:.8rem;cursor:pointer;font-weight:600">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>` : '';

    // Seção webhook + valor (sempre visível, acima da config específica)
    const integracaoHtml = `
        <div style="margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #e2e8f0">
            <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#a0aec0;display:block;margin-bottom:.6rem">Configuração da integração</span>
            <div style="display:grid;gap:.5rem">
                <div>
                    <label style="font-size:.72rem;font-weight:700;color:#4a5568;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.2rem">Webhook Bitrix24</label>
                    <input id="app-webhook-input" type="text" class="form-input" value="${_esc(app.webhook_bitrix || '')}" placeholder="https://...">
                </div>
                <div>
                    <label style="font-size:.72rem;font-weight:700;color:#4a5568;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.2rem">Valor (R$)</label>
                    <input id="app-valor-input" type="number" step="0.01" min="0" class="form-input" value="${app.valor || ''}" placeholder="0,00">
                </div>
                <div>
                    <button onclick="salvarDadosApp(${clienteIdAtual}, ${app.ca_id})"
                        style="background:none;border:1px solid #0DC2FF;color:#0DC2FF;border-radius:6px;padding:.35rem .75rem;font-size:.8rem;cursor:pointer;font-weight:600">
                        <i class="fas fa-check"></i> Salvar integração
                    </button>
                    <span id="app-integracao-msg" style="font-size:.8rem;color:#718096;margin-left:.5rem"></span>
                </div>
            </div>
        </div>`;

    // Adiciona toggle e botão desativar ao final do conteúdo
    const acoes = `
        <div style="border-top:1px solid #f0f4f8;padding-top:1rem;margin-top:1rem;display:flex;align-items:center;justify-content:space-between">
            <label class="toggle-switch" onclick="bloquearApp(${app.ca_id},'${app.nome.replace(/'/g,"\\'")}',${app.ativo});event.preventDefault()">
                <input type="checkbox" ${app.ativo ? 'checked' : ''} readonly>
                <span class="toggle-track"><span class="toggle-thumb"></span></span>
                <span class="toggle-label">${app.ativo ? 'Aplicação ativa' : 'Aplicação bloqueada'}</span>
            </label>
            <button onclick="desativarApp(${app.ca_id},'${app.nome.replace(/'/g,"\\'")}')"
                style="padding:.5rem .9rem;border:1px solid #fed7d7;border-radius:8px;background:#fff;color:#c53030;font-size:.8rem;font-weight:600;cursor:pointer">
                <i class="fas fa-trash"></i> Desativar
            </button>
        </div>`;

    document.getElementById('app-modal-body').innerHTML = chaveHtml + integracaoHtml + configHtml + acoes;
    document.getElementById('app-config-overlay').classList.add('open');
    document.getElementById('app-config-modal').classList.add('open');
}

async function bloquearApp(caId, appNome, ativo) {
    const msg = ativo
        ? `Bloquear "${appNome}" para este cliente?\nA app ficará registrada mas inativa.`
        : `Desbloquear "${appNome}" para este cliente?`;
    const ok = await kwConfirm(msg, ativo ? 'Bloquear aplicação' : 'Desbloquear aplicação', ativo ? 'danger' : 'success');
    if (!ok) return;

    fetch('/api/cliente-bloquear-app.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cliente_id: clienteIdAtual, ca_id: caId, ativo: !ativo })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.sucesso) { alert(data.erro || 'Erro.'); return; }

        const idx = appsAtivas.findIndex(a => String(a.ca_id) === String(caId));
        if (idx !== -1) appsAtivas[idx].ativo = !ativo;

        const toggle = document.querySelector('#app-config-modal .toggle-switch input');
        const label  = document.querySelector('#app-config-modal .toggle-label');
        if (toggle) toggle.checked = !ativo;
        if (label)  label.textContent = !ativo ? 'Aplicação ativa' : 'Aplicação bloqueada';

        renderAppsAtivas(appsAtivas);
    });
}

async function desativarApp(caId, appNome) {
    const ok = await kwConfirm(
        `Desativar "${appNome}"?\n\nA configuração será removida permanentemente.`,
        'Desativar aplicação'
    );
    if (!ok) return;

    fetch('/api/cliente-desativar-app.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cliente_id: clienteIdAtual, ca_id: caId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            fecharModalApp();
            fetch('/api/cliente-detalhe.php?id=' + clienteIdAtual, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => renderAppsAtivas(d.aplicacoes));
        } else { alert(data.erro || 'Erro.'); }
    });
}

function salvarDadosApp(clienteId, caId) {
    const webhook = document.getElementById('app-webhook-input')?.value.trim();
    const valor   = document.getElementById('app-valor-input')?.value;
    const msg     = document.getElementById('app-integracao-msg');
    if (msg) msg.textContent = 'Salvando...';

    fetch('/api/cliente-app-atualizar.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cliente_id: clienteId, ca_id: caId, webhook_bitrix: webhook, valor: valor || null })
    })
    .then(r => r.json())
    .then(data => {
        if (msg) {
            msg.textContent = data.sucesso ? '✓ Salvo' : (data.erro || 'Erro.');
            setTimeout(() => { if (msg) msg.textContent = ''; }, 2500);
        }
    })
    .catch(() => { if (msg) msg.textContent = 'Erro de conexão.'; });
}

function fecharModalApp() {
    document.getElementById('app-config-overlay').classList.remove('open');
    document.getElementById('app-config-modal').classList.remove('open');
}

// ===== MODAL ATIVAR APP =====

function abrirModalAtivar() {
    document.getElementById('ativar-overlay').classList.add('open');
    document.getElementById('ativar-modal').classList.add('open');

    const lista = document.getElementById('ativar-lista');
    lista.innerHTML = '<div class="panel-loading"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';

    Promise.all([
        fetch('/api/cliente-detalhe.php?id=' + clienteIdAtual, { credentials: 'same-origin' }).then(r => r.json()),
        fetch('/api/aplicacoes-lista.php', { credentials: 'same-origin' }).then(r => r.json())
    ]).then(([detalhe, todas]) => {
        // Conta instâncias ativas por aplicacao_id
        const contagemPorApp = {};
        (detalhe.aplicacoes || []).forEach(a => {
            const aid = parseInt(a.aplicacao_id);
            contagemPorApp[aid] = (contagemPorApp[aid] || 0) + 1;
        });
        lista.innerHTML = todas.map(a => {
            const count = contagemPorApp[parseInt(a.id)] || 0;
            const badge = count > 0
                ? `<span class="badge-app">${count === 1 ? 'Ativa' : count + ' ativas'}</span>`
                : '<span style="font-size:.75rem;color:#0DC2FF;font-weight:600">Ativar →</span>';
            return `
            <div class="app-disponivel" onclick="ativarApp(${a.id}, '${a.nome.replace(/'/g,"\\'")}')">
                <div class="app-card-icon"><i class="${iconeApp[a.slug] || 'fas fa-puzzle-piece'}"></i></div>
                <div class="app-card-info">
                    <div class="app-card-name">${_esc(a.nome)}</div>
                    <div class="app-card-slug">${_esc(a.slug)}</div>
                </div>
                ${badge}
            </div>`;
        }).join('');
    });
}

function fecharModalAtivar() {
    document.getElementById('ativar-overlay').classList.remove('open');
    document.getElementById('ativar-modal').classList.remove('open');
}

async function ativarApp(appId, appNome) {
    const resultado = await kwAtivarApp(appNome);
    if (!resultado) return;

    fetch('/api/cliente-ativar-app.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            cliente_id:    clienteIdAtual,
            aplicacao_id:  appId,
            webhook_bitrix: resultado.webhook,
            descricao:     resultado.descricao || null
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            fecharModalAtivar();
            // Mostra a chave gerada ao admin
            if (data.chave) mostrarChaveGerada(data.chave, appNome);
            fetch('/api/cliente-detalhe.php?id=' + clienteIdAtual, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => renderAppsAtivas(d.aplicacoes));
        } else { alert(data.erro || 'Erro ao ativar.'); }
    });
}

function mostrarChaveGerada(chave, appNome) {
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(6,25,32,.6);backdrop-filter:blur(4px);z-index:9998;display:flex;align-items:center;justify-content:center';
    overlay.innerHTML = `
        <div style="background:#fff;border-radius:16px;padding:2rem;width:440px;max-width:92vw;box-shadow:0 24px 60px rgba(0,0,0,.25);animation:kwPop .18s ease">
            <div style="width:48px;height:48px;border-radius:50%;background:#d1fae5;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;font-size:1.3rem;color:#065f46"><i class="fas fa-key"></i></div>
            <h3 style="text-align:center;font-family:'Rubik',sans-serif;font-size:1rem;font-weight:700;color:#1a202c;margin:0 0 .35rem">Aplicação ativada!</h3>
            <p style="text-align:center;font-size:.85rem;color:#718096;margin:0 0 1rem">${_esc(appNome)} — chave de acesso desta instância:</p>
            <div style="display:flex;align-items:center;gap:.5rem;background:#f8fafc;border-radius:8px;padding:.6rem .75rem;border:1px solid #e2e8f0;margin-bottom:1.25rem">
                <span id="_chave-gerada-txt" style="font-family:monospace;font-size:.82rem;color:#2d3748;word-break:break-all;flex:1">${_esc(chave)}</span>
                <button onclick="copiarChaveApp('${_esc(chave)}')" style="background:#0DC2FF;color:#fff;border:none;border-radius:6px;padding:.35rem .65rem;font-size:.8rem;cursor:pointer;font-weight:600;flex-shrink:0"><i class="fas fa-copy"></i></button>
            </div>
            <button onclick="this.closest('[style*=fixed]').remove()"
                style="width:100%;padding:.65rem;border:none;border-radius:8px;background:#0DC2FF;color:#fff;font-size:.875rem;cursor:pointer;font-weight:700">Entendido</button>
        </div>`;
    document.body.appendChild(overlay);
    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
}

// ===== EDIÇÃO INLINE =====

function editarCampo(fieldEl) {
    if (fieldEl.classList.contains('editando') || fieldEl.classList.contains('no-edit')) return;
    fieldEl.classList.add('editando');

    const campo      = fieldEl.getAttribute('data-campo');
    const tipo       = fieldEl.getAttribute('data-tipo') || 'input';
    const span       = fieldEl.querySelector('span');
    const valorAtual = span.textContent === '—' ? '' : span.textContent;

    span.style.display = 'none';

    const input = tipo === 'textarea'
        ? document.createElement('textarea')
        : Object.assign(document.createElement('input'), { type: 'text' });

    input.value = valorAtual;
    fieldEl.appendChild(input);
    input.focus();

    document.getElementById('panel-save-bar').classList.add('visivel');
    edicoesPendentes[campo] = valorAtual;
    input.addEventListener('input', () => { edicoesPendentes[campo] = input.value; });
}

function cancelarEdicoes() {
    document.querySelectorAll('.panel-field.editando').forEach(f => {
        const span  = f.querySelector('span');
        const input = f.querySelector('input, textarea');
        if (input) input.remove();
        if (span)  span.style.display = '';
        f.classList.remove('editando');
    });
    edicoesPendentes = {};
    const bar = document.getElementById('panel-save-bar');
    if (bar) bar.classList.remove('visivel');
}

function salvarEdicoes() {
    if (modoNovo) { salvarNovoCliente(); return; }
    if (!clienteIdAtual || !Object.keys(edicoesPendentes).length) return;
    const msg = document.getElementById('save-msg');
    msg.textContent = 'Salvando...';

    fetch('/api/cliente-atualizar.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: clienteIdAtual, ...edicoesPendentes })
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            document.querySelectorAll('.panel-field.editando').forEach(f => {
                const campo    = f.getAttribute('data-campo');
                const span     = f.querySelector('span');
                const input    = f.querySelector('input, textarea');
                span.textContent = edicoesPendentes[campo] || '—';
                if (input) input.remove();
                span.style.display = '';
                f.classList.remove('editando');
            });
            edicoesPendentes = {};
            document.getElementById('panel-save-bar').classList.remove('visivel');
            msg.textContent = '';
        } else {
            msg.textContent = data.erro || 'Erro ao salvar.';
        }
    })
    .catch(() => { msg.textContent = 'Erro de conexão.'; });
}

// Fechar com ESC
document.addEventListener('keydown', e => { if (e.key === 'Escape') fecharPainel(); });

// Menu ⋮ do painel
function toggleMenuCliente(e) {
    e.stopPropagation();
    const menu = document.getElementById('menu-cliente-dropdown');
    if (!menu) return;
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

// Fecha o menu ao clicar em qualquer lugar
document.addEventListener('click', () => {
    const menu = document.getElementById('menu-cliente-dropdown');
    if (menu) menu.style.display = 'none';
});

// ===== NOVO CLIENTE (usa o mesmo painel lateral) =====
let modoNovo = false;

function abrirNovoCliente() {
    modoNovo = true;
    clienteIdAtual = null;
    cancelarEdicoes();

    ['novo-nome','novo-cnpj','novo-telefone','novo-email',
     'novo-endereco','novo-link-bitrix','novo-id-bitrix'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    const erroEl = document.getElementById('novo-cliente-erro');
    if (erroEl) erroEl.style.display = 'none';

    // Popula dropdown de organizações
    preencherOrgDropdown('novo-org-id', null);

    document.getElementById('panel-avatar').textContent = '+';
    document.getElementById('panel-nome').textContent   = 'Novo Cliente';
    document.getElementById('panel-cnpj').textContent   = 'Preencha os dados abaixo';

    document.getElementById('panel-loading').style.display  = 'none';
    document.getElementById('panel-conteudo').style.display = 'none';
    document.getElementById('panel-novo').style.display     = 'block';

    // Barra salvar com "Cadastrar"
    document.getElementById('panel-save-bar').classList.add('visivel');
    document.querySelector('#panel-save-bar .btn-salvar').innerHTML = '<i class="fas fa-check"></i> Cadastrar';

    // Esconde menu ⋮
    const btnMenu = document.getElementById('btn-menu-cliente');
    if (btnMenu) btnMenu.style.visibility = 'hidden';

    // Painel mais estreito no modo novo (sem coluna de apps)
    document.getElementById('cliente-panel').style.width = '520px';

    document.getElementById('cliente-overlay').classList.add('open');
    document.getElementById('cliente-panel').classList.add('open');

    const nomeEl = document.getElementById('novo-nome');
    if (nomeEl) nomeEl.focus();
}

function fecharNovoCliente() { fecharPainel(); }

async function excluirCliente() {
    if (!clienteIdAtual) return;
    const nome = document.getElementById('panel-nome').textContent;
    const ok = await kwConfirm(`Deseja excluir o cliente "${nome}"?\n\nTodas as aplicações vinculadas também serão removidas.`, 'Excluir cliente');
    if (!ok) return;

    fetch('/api/cliente-excluir.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: clienteIdAtual })
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            fecharPainel();
            window.location.href = '?page=cadastro';
        } else { alert(data.erro || 'Erro ao excluir.'); }
    });
}


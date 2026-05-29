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

// Modal de ativação com campo webhook
function kwAtivarApp(appNome) {
    return new Promise(resolve => {
        const overlay  = document.getElementById('kw-ativar-overlay');
        const titleEl  = document.getElementById('kw-ativar-title');
        const msgEl    = document.getElementById('kw-ativar-msg');
        const input    = document.getElementById('kw-ativar-webhook');
        const erro     = document.getElementById('kw-ativar-erro');
        const btnOk    = document.getElementById('kw-ativar-ok');
        const btnCancel = document.getElementById('kw-ativar-cancel');

        titleEl.textContent = 'Ativar aplicação';
        msgEl.textContent   = `Ativar "${appNome}" para este cliente?`;
        input.value         = '';
        erro.style.display  = 'none';
        overlay.style.display = 'flex';
        input.focus();

        const close = (webhook) => {
            overlay.style.display = 'none';
            btnOk.onclick     = null;
            btnCancel.onclick = null;
            overlay.onclick   = null;
            resolve(webhook);
        };

        btnOk.onclick = () => {
            const wh = input.value.trim();
            if (!wh) { erro.style.display = 'block'; return; }
            erro.style.display = 'none';
            close(wh);
        };

        btnCancel.onclick = () => close(null);
        overlay.onclick   = (e) => { if (e.target === overlay) close(null); };

        input.onkeydown = (e) => { if (e.key === 'Enter') btnOk.click(); };
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
        nome:         document.getElementById('novo-nome')?.value.trim(),
        cnpj:         document.getElementById('novo-cnpj')?.value.trim(),
        telefone:     document.getElementById('novo-telefone')?.value.trim(),
        email:        document.getElementById('novo-email')?.value.trim(),
        endereco:     document.getElementById('novo-endereco')?.value.trim(),
        link_bitrix:  document.getElementById('novo-link-bitrix')?.value.trim(),
        chave_acesso: document.getElementById('novo-chave')?.value.trim(),
        id_bitrix:    document.getElementById('novo-id-bitrix')?.value.trim(),
    };

    const obrigatorios = ['nome','cnpj','telefone','email','endereco','link_bitrix','chave_acesso'];
    for (const c of obrigatorios) {
        if (!campos[c]) {
            const erro = document.getElementById('novo-cliente-erro');
            erro.textContent = `Campo obrigatório: ${c.replace('_',' ')}`;
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
            window.location.href = '?page=cadastro';
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
    document.getElementById('pf-chave').textContent      = c.chave_acesso || '—';
    document.getElementById('pf-id-bitrix').textContent  = c.id_bitrix    || '—';

    appsAtivas = apps || [];
    renderAppsAtivas(appsAtivas);

    document.getElementById('panel-loading').style.display  = 'none';
    document.getElementById('panel-conteudo').style.display = 'block';
}

function renderAppsAtivas(apps) {
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
                <div class="app-card-name">${a.nome}</div>
                <div class="app-card-slug">${a.slug}</div>
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

// ===== MODAL CONFIG APP =====

function abrirModalApp(app) {
    document.getElementById('app-modal-icon').innerHTML    = `<i class="${iconeApp[app.slug] || 'fas fa-puzzle-piece'}"></i>`;
    document.getElementById('app-modal-nome').textContent  = app.nome;
    document.getElementById('app-modal-slug').textContent  = app.slug;
    document.getElementById('app-modal-body').innerHTML    = `
        <p style="color:#718096;font-size:.875rem;margin-bottom:1rem">${app.descricao || ''}</p>
        <div style="padding:2rem;background:#f8fafc;border-radius:8px;border:1px dashed #cbd5e0;text-align:center;color:#a0aec0;margin-bottom:1.25rem">
            <i class="fas fa-cog" style="font-size:2.5rem;display:block;margin-bottom:.75rem"></i>
            <strong>Configurações em construção</strong><br>
            <span style="font-size:.8rem">As configurações de <strong>${app.nome}</strong> serão implementadas aqui.</span>
        </div>
        <div style="border-top:1px solid #f0f4f8;padding-top:1rem;display:flex;align-items:center;justify-content:space-between">
            <label class="toggle-switch" onclick="bloquearApp(${app.id},'${app.nome.replace(/'/g,"\\'")}',${app.ativo});event.preventDefault()">
                <input type="checkbox" ${app.ativo ? 'checked' : ''} readonly>
                <span class="toggle-track"><span class="toggle-thumb"></span></span>
                <span class="toggle-label">${app.ativo ? 'Aplicação ativa' : 'Aplicação bloqueada'}</span>
            </label>
            <button onclick="desativarApp(${app.id},'${app.nome.replace(/'/g,"\\'")}')"
                style="padding:.5rem .9rem;border:1px solid #fed7d7;border-radius:8px;background:#fff;color:#c53030;font-size:.8rem;font-weight:600;cursor:pointer">
                <i class="fas fa-trash"></i> Desativar
            </button>
        </div>`;
    document.getElementById('app-config-overlay').classList.add('open');
    document.getElementById('app-config-modal').classList.add('open');
}

async function bloquearApp(appId, appNome, ativo) {
    const acao = ativo ? 'bloquear' : 'desbloquear';
    const msg  = ativo
        ? `Bloquear "${appNome}" para este cliente?\nA app ficará registrada mas inativa.`
        : `Desbloquear "${appNome}" para este cliente?`;
    const ok = await kwConfirm(msg, ativo ? 'Bloquear aplicação' : 'Desbloquear aplicação', ativo ? 'danger' : 'success');
    if (!ok) return;

    fetch('/api/cliente-bloquear-app.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cliente_id: clienteIdAtual, aplicacao_id: appId, ativo: !ativo })
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

async function desativarApp(appId, appNome) {
    const ok = await kwConfirm(
        `Desativar "${appNome}"?\n\nA configuração será removida permanentemente.`,
        'Desativar aplicação'
    );
    if (!ok) return;

    fetch('/api/cliente-desativar-app.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cliente_id: clienteIdAtual, aplicacao_id: appId })
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
        const ativasIds = (detalhe.aplicacoes || []).map(a => parseInt(a.id));
        lista.innerHTML = todas.map(a => `
            <div class="app-disponivel ${ativasIds.includes(parseInt(a.id)) ? 'ja-ativo' : ''}"
                 onclick="ativarApp(${a.id}, '${a.nome.replace(/'/g,"\\'")}')">
                <div class="app-card-icon"><i class="${iconeApp[a.slug] || 'fas fa-puzzle-piece'}"></i></div>
                <div class="app-card-info">
                    <div class="app-card-name">${a.nome}</div>
                    <div class="app-card-slug">${a.slug}</div>
                </div>
                ${ativasIds.includes(parseInt(a.id))
                    ? '<span class="badge-app">Ativo</span>'
                    : '<span style="font-size:.75rem;color:#0DC2FF;font-weight:600">Ativar →</span>'}
            </div>`).join('');
    });
}

function fecharModalAtivar() {
    document.getElementById('ativar-overlay').classList.remove('open');
    document.getElementById('ativar-modal').classList.remove('open');
}

async function ativarApp(appId, appNome) {
    const resultado = await kwAtivarApp(appNome);
    if (!resultado) return;
    const webhook = resultado;
    fetch('/api/cliente-ativar-app.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cliente_id: clienteIdAtual, aplicacao_id: appId, webhook_bitrix: webhook })
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            fecharModalAtivar();
            fetch('/api/cliente-detalhe.php?id=' + clienteIdAtual, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => renderAppsAtivas(d.aplicacoes));
        } else { alert(data.erro || 'Erro ao ativar.'); }
    });
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
     'novo-endereco','novo-link-bitrix','novo-chave','novo-id-bitrix'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    const erroEl = document.getElementById('novo-cliente-erro');
    if (erroEl) erroEl.style.display = 'none';

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

function gerarChave() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let chave = '';
    for (let i = 0; i < 24; i++) chave += chars[Math.floor(Math.random() * chars.length)];
    const el = document.getElementById('novo-chave');
    if (el) el.value = chave;
}

/**
 * KW24 - Painel lateral de cliente
 * Carregado no index.php para estar sempre disponível
 */

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
        <div class="app-card" data-app-index="${i}">
            <div class="app-card-icon"><i class="${iconeApp[a.slug] || 'fas fa-puzzle-piece'}"></i></div>
            <div class="app-card-info">
                <div class="app-card-name">${a.nome}</div>
                <div class="app-card-slug">${a.slug}</div>
            </div>
            <span class="badge-app">Ativo</span>
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
        <div style="padding:2rem;background:#f8fafc;border-radius:8px;border:1px dashed #cbd5e0;text-align:center;color:#a0aec0">
            <i class="fas fa-cog" style="font-size:2.5rem;display:block;margin-bottom:.75rem"></i>
            <strong>Configurações em construção</strong><br>
            <span style="font-size:.8rem">As configurações de <strong>${app.nome}</strong> serão implementadas aqui.</span>
        </div>`;
    document.getElementById('app-config-overlay').classList.add('open');
    document.getElementById('app-config-modal').classList.add('open');
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

function ativarApp(appId, appNome) {
    if (!confirm(`Ativar "${appNome}" para este cliente?`)) return;
    fetch('/api/cliente-ativar-app.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cliente_id: clienteIdAtual, aplicacao_id: appId })
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

// ===== NOVO CLIENTE =====

function abrirNovoCliente() {
    const modal = document.getElementById('novo-cliente-modal');
    if (!modal) return;
    document.getElementById('form-novo-cliente').reset();
    document.getElementById('novo-cliente-erro').style.display = 'none';
    document.getElementById('novo-cliente-overlay').classList.add('open');
    modal.classList.add('open');
    modal.querySelector('input[name=nome]').focus();
}

function fecharNovoCliente() {
    document.getElementById('novo-cliente-overlay').classList.remove('open');
    document.getElementById('novo-cliente-modal').classList.remove('open');
}

function excluirCliente() {
    if (!clienteIdAtual) return;
    const nome = document.getElementById('panel-nome').textContent;
    if (!confirm(`Excluir o cliente "${nome}"?\n\nEsta ação removerá também todas as aplicações vinculadas e não pode ser desfeita.`)) return;

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
    document.getElementById('input-chave').value = chave;
}

function salvarNovoCliente(e) {
    e.preventDefault();
    const form = document.getElementById('form-novo-cliente');
    const erro = document.getElementById('novo-cliente-erro');
    const btn  = document.getElementById('btn-salvar-novo');
    const data = Object.fromEntries(new FormData(form));

    btn.disabled = true;
    btn.textContent = 'Salvando...';
    erro.style.display = 'none';

    fetch('/api/cliente-criar.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.sucesso) {
            fecharNovoCliente();
            // Recarrega a página para mostrar o novo cliente na lista
            window.location.href = '?page=cadastro';
        } else {
            erro.textContent = res.erro || 'Erro ao cadastrar.';
            erro.style.display = 'block';
        }
    })
    .catch(() => { erro.textContent = 'Erro de conexão.'; erro.style.display = 'block'; })
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Cadastrar'; });
}

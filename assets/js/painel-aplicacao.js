/**
 * KW24 - Painel lateral de aplicação
 * Carregado no index.php para estar sempre disponível
 */

const iconeAppMap = {
    clicksign:   'fa-file-signature', deal:        'fa-handshake',
    task:        'fa-tasks',          company:     'fa-building',
    omie:        'fa-calculator',     receita:     'fa-search',
    import:      'fa-upload',         disk:        'fa-hdd',
    calcdata:    'fa-calendar-alt',   mediahora:   'fa-clock',
    scheduler:   'fa-robot',          geraroptnd:  'fa-magic',
    extenso:     'fa-font',           validar_cnpj:'fa-id-card'
};

let appIdAtual  = null;
let appModoNovo = false;
let appEdicoes  = {};

function abrirAplicacao(id) {
    const overlay = document.getElementById('app-overlay');
    const panel   = document.getElementById('app-panel');
    if (!overlay || !panel) return;

    appIdAtual  = id;
    appModoNovo = false;
    appEdicoes  = {};

    overlay.classList.add('open');
    panel.classList.add('open');
    document.getElementById('app-panel-loading').style.display  = 'flex';
    document.getElementById('app-panel-conteudo').style.display = 'none';
    document.getElementById('app-panel-novo').style.display     = 'none';
    document.getElementById('app-save-bar').classList.remove('visivel');
    const btnMenu = document.getElementById('btn-menu-app');
    if (btnMenu) btnMenu.style.visibility = '';

    fetch('/api/aplicacao-detalhe.php?id=' + id, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (data.erro) { alert(data.erro); fecharAplicacao(); return; }
            preencherAplicacao(data.app, data.clientes);
        })
        .catch(() => fecharAplicacao());
}

function preencherAplicacao(a, clientes) {
    const iconEl = document.getElementById('app-panel-icon');
    if (iconEl) iconEl.className = 'fas ' + (iconeAppMap[a.slug] || 'fa-puzzle-piece');
    document.getElementById('app-panel-nome').textContent = a.nome;
    document.getElementById('app-panel-slug').textContent = 'slug: ' + a.slug;
    document.getElementById('apf-id').textContent         = a.id;
    document.getElementById('apf-slug').textContent       = a.slug;
    document.getElementById('apf-nome').textContent       = a.nome;
    document.getElementById('apf-descricao').textContent  = a.descricao || '—';

    const cl = document.getElementById('apf-clientes');
    if (cl) {
        cl.innerHTML = (!clientes || !clientes.length)
            ? '<p style="color:#a0aec0;font-size:.85rem">Nenhum cliente usa esta aplicação.</p>'
            : clientes.map(c => `
                <div style="display:flex;align-items:center;gap:.75rem;padding:.6rem 0;border-bottom:1px solid #f0f4f8">
                    <div class="cliente-avatar" style="width:32px;height:32px;font-size:.75rem">${c.nome.substring(0,2).toUpperCase()}</div>
                    <span style="font-size:.875rem;color:#2d3748">${c.nome}</span>
                </div>`).join('');
    }

    document.getElementById('app-panel-loading').style.display  = 'none';
    document.getElementById('app-panel-conteudo').style.display = 'block';
}

function fecharAplicacao() {
    const overlay = document.getElementById('app-overlay');
    const panel   = document.getElementById('app-panel');
    if (overlay) overlay.classList.remove('open');
    if (panel)   panel.classList.remove('open');
    cancelarEdicoesApp();
    appModoNovo = false;
}

function editarCampoApp(fieldEl) {
    if (fieldEl.classList.contains('editando') || fieldEl.classList.contains('no-edit')) return;
    fieldEl.classList.add('editando');

    const campo = fieldEl.getAttribute('data-app-campo');
    const tipo  = fieldEl.getAttribute('data-tipo') || 'input';
    const span  = fieldEl.querySelector('span');
    const val   = span.textContent === '—' ? '' : span.textContent;

    span.style.display = 'none';
    const input = tipo === 'textarea'
        ? document.createElement('textarea')
        : Object.assign(document.createElement('input'), { type: 'text' });
    input.value = val;
    fieldEl.appendChild(input);
    input.focus();

    document.getElementById('app-save-bar').classList.add('visivel');
    appEdicoes[campo] = val;
    input.addEventListener('input', () => { appEdicoes[campo] = input.value; });
}

function cancelarEdicoesApp() {
    document.querySelectorAll('#app-panel .panel-field.editando').forEach(f => {
        const span  = f.querySelector('span');
        const input = f.querySelector('input, textarea');
        if (input) input.remove();
        if (span)  span.style.display = '';
        f.classList.remove('editando');
    });
    appEdicoes = {};
    const bar = document.getElementById('app-save-bar');
    if (bar) { bar.classList.remove('visivel'); }
    const msg = document.getElementById('app-save-msg');
    if (msg) msg.textContent = '';
}

function cancelarAplicacao() {
    if (appModoNovo) { fecharAplicacao(); } else { cancelarEdicoesApp(); }
}

function salvarAplicacao() {
    if (appModoNovo) { salvarNovaAplicacao(); return; }
    if (!appIdAtual || !Object.keys(appEdicoes).length) return;

    const msg = document.getElementById('app-save-msg');
    msg.textContent = 'Salvando...';

    fetch('/api/aplicacao-atualizar.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: appIdAtual, ...appEdicoes })
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            document.querySelectorAll('#app-panel .panel-field.editando').forEach(f => {
                const campo = f.getAttribute('data-app-campo');
                const span  = f.querySelector('span');
                const input = f.querySelector('input, textarea');
                span.textContent = appEdicoes[campo] || '—';
                if (input) input.remove();
                span.style.display = '';
                f.classList.remove('editando');
            });
            const nomeEl = document.getElementById('app-panel-nome');
            if (appEdicoes['nome'] && nomeEl) nomeEl.textContent = appEdicoes['nome'];
            appEdicoes = {};
            document.getElementById('app-save-bar').classList.remove('visivel');
            msg.textContent = '';
        } else { msg.textContent = data.erro || 'Erro ao salvar.'; }
    })
    .catch(() => { msg.textContent = 'Erro de conexão.'; });
}

function abrirNovaAplicacao() {
    const overlay = document.getElementById('app-overlay');
    const panel   = document.getElementById('app-panel');
    if (!overlay || !panel) return;

    appModoNovo = true;
    appIdAtual  = null;

    ['nova-app-slug','nova-app-nome','nova-app-descricao'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
    const erroEl = document.getElementById('nova-app-erro');
    if (erroEl) erroEl.style.display = 'none';

    document.getElementById('app-panel-icon').className    = 'fas fa-plus';
    document.getElementById('app-panel-nome').textContent  = 'Nova Aplicação';
    document.getElementById('app-panel-slug').textContent  = '';
    document.getElementById('app-panel-loading').style.display  = 'none';
    document.getElementById('app-panel-conteudo').style.display = 'none';
    document.getElementById('app-panel-novo').style.display     = 'block';
    document.getElementById('app-save-bar').classList.add('visivel');
    document.querySelector('#app-save-bar .btn-salvar').innerHTML = '<i class="fas fa-check"></i> Cadastrar';

    const btnMenu = document.getElementById('btn-menu-app');
    if (btnMenu) btnMenu.style.visibility = 'hidden';

    overlay.classList.add('open');
    panel.classList.add('open');
    const slugEl = document.getElementById('nova-app-slug');
    if (slugEl) slugEl.focus();
}

function salvarNovaAplicacao() {
    const slug  = document.getElementById('nova-app-slug')?.value.trim();
    const nome  = document.getElementById('nova-app-nome')?.value.trim();
    const descr = document.getElementById('nova-app-descricao')?.value.trim();
    const erro  = document.getElementById('nova-app-erro');

    if (!slug || !nome) {
        erro.textContent = 'Slug e Nome são obrigatórios.';
        erro.style.display = 'block';
        return;
    }

    const msg = document.getElementById('app-save-msg');
    msg.textContent = 'Cadastrando...';

    fetch('/api/aplicacao-criar.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ slug, nome, descricao: descr })
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) { fecharAplicacao(); window.location.href = '?page=aplicacoes'; }
        else { erro.textContent = data.erro || 'Erro ao cadastrar.'; erro.style.display = 'block'; msg.textContent = ''; }
    })
    .catch(() => { msg.textContent = 'Erro de conexão.'; });
}

async function excluirAplicacao() {
    if (!appIdAtual) return;
    const nome = document.getElementById('app-panel-nome')?.textContent;
    const ok = await kwConfirm(`Deseja excluir a aplicação "${nome}"?\n\nEla será removida de todos os clientes vinculados.`, 'Excluir aplicação');
    if (!ok) return;

    fetch('/api/aplicacao-excluir.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: appIdAtual })
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) { fecharAplicacao(); window.location.href = '?page=aplicacoes'; }
        else { alert(data.erro || 'Erro ao excluir.'); }
    });
}

function toggleMenuApp(e) {
    e.stopPropagation();
    const menu = document.getElementById('menu-app-dropdown');
    if (menu) menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

document.addEventListener('click', () => {
    const menu = document.getElementById('menu-app-dropdown');
    if (menu) menu.style.display = 'none';
});

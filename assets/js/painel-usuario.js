/**
 * KW24 - Painel lateral de usuário
 */

let usrIdAtual  = null;
let usrModoNovo = false;
let usrEdicoes  = {};

function abrirUsuario(id) {
    const overlay = document.getElementById('usr-overlay');
    const panel   = document.getElementById('usr-panel');
    if (!overlay || !panel) return;

    usrIdAtual  = id;
    usrModoNovo = false;
    usrEdicoes  = {};

    overlay.classList.add('open');
    panel.classList.add('open');
    document.getElementById('usr-panel-loading').style.display  = 'flex';
    document.getElementById('usr-panel-conteudo').style.display = 'none';
    document.getElementById('usr-panel-novo').style.display     = 'none';
    document.getElementById('usr-save-bar').classList.remove('visivel');
    const btnMenu = document.getElementById('btn-menu-usr');
    if (btnMenu) btnMenu.style.visibility = '';

    fetch('/api/usuario-detalhe.php?id=' + id, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (data.erro) { alert(data.erro); fecharUsuario(); return; }
            preencherUsuario(data.usuario);
        })
        .catch(() => fecharUsuario());
}

function preencherUsuario(u) {
    document.getElementById('usr-avatar').textContent      = (u.nome || '--').substring(0, 2).toUpperCase();
    document.getElementById('usr-panel-nome').textContent  = u.nome;
    document.getElementById('usr-panel-username').textContent = '@' + u.username;

    document.getElementById('uf-id').textContent       = u.id;
    document.getElementById('uf-nome').textContent     = u.nome      || '—';
    document.getElementById('uf-username').textContent = u.username  || '—';
    document.getElementById('uf-email').textContent    = u.email     || '—';
    document.getElementById('uf-cargo').textContent    = u.cargo     || '—';
    document.getElementById('uf-telefone').textContent = u.telefone  || '—';

    const perfis = { admin_interno: 'Admin Interno', admin_cliente: 'Admin Cliente', usuario_cliente: 'Usuário Cliente' };
    document.getElementById('uf-perfil').textContent  = perfis[u.perfil] || u.perfil;
    document.getElementById('uf-acesso').textContent  = u.ultimo_acesso ? new Date(u.ultimo_acesso).toLocaleString('pt-BR') : 'Nunca';
    document.getElementById('uf-ativo').textContent   = u.ativo ? 'Ativo' : 'Inativo';

    document.getElementById('usr-panel-loading').style.display  = 'none';
    document.getElementById('usr-panel-conteudo').style.display = 'block';
}

function fecharUsuario() {
    const overlay = document.getElementById('usr-overlay');
    const panel   = document.getElementById('usr-panel');
    if (overlay) overlay.classList.remove('open');
    if (panel)   panel.classList.remove('open');
    cancelarEdicoesUsr();
    usrModoNovo = false;
}

function editarCampoUsr(fieldEl) {
    if (fieldEl.classList.contains('editando') || fieldEl.classList.contains('no-edit')) return;
    fieldEl.classList.add('editando');

    const campo = fieldEl.getAttribute('data-usr-campo');
    const span  = fieldEl.querySelector('span');
    const val   = span.textContent === '—' ? '' : span.textContent;

    span.style.display = 'none';
    const input = Object.assign(document.createElement('input'), { type: 'text', value: val });
    fieldEl.appendChild(input);
    input.focus();

    document.getElementById('usr-save-bar').classList.add('visivel');
    usrEdicoes[campo] = val;
    input.addEventListener('input', () => { usrEdicoes[campo] = input.value; });
}

function cancelarEdicoesUsr() {
    document.querySelectorAll('#usr-panel .panel-field.editando').forEach(f => {
        const span  = f.querySelector('span');
        const input = f.querySelector('input');
        if (input) input.remove();
        if (span)  span.style.display = '';
        f.classList.remove('editando');
    });
    usrEdicoes = {};
    const bar = document.getElementById('usr-save-bar');
    if (bar) bar.classList.remove('visivel');
}

function cancelarUsuario() {
    if (usrModoNovo) { fecharUsuario(); } else { cancelarEdicoesUsr(); }
}

function salvarUsuario() {
    if (usrModoNovo) { salvarNovoUsuario(); return; }
    if (!usrIdAtual || !Object.keys(usrEdicoes).length) return;

    const msg = document.getElementById('usr-save-msg');
    msg.textContent = 'Salvando...';

    fetch('/api/usuario-atualizar.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: usrIdAtual, ...usrEdicoes })
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            document.querySelectorAll('#usr-panel .panel-field.editando').forEach(f => {
                const campo = f.getAttribute('data-usr-campo');
                const span  = f.querySelector('span');
                const input = f.querySelector('input');
                span.textContent = usrEdicoes[campo] || '—';
                if (input) input.remove();
                span.style.display = '';
                f.classList.remove('editando');
            });
            if (usrEdicoes['nome']) document.getElementById('usr-panel-nome').textContent = usrEdicoes['nome'];
            usrEdicoes = {};
            document.getElementById('usr-save-bar').classList.remove('visivel');
            msg.textContent = '';
        } else { msg.textContent = data.erro || 'Erro ao salvar.'; }
    })
    .catch(() => { msg.textContent = 'Erro de conexão.'; });
}

function abrirNovoUsuario() {
    const overlay = document.getElementById('usr-overlay');
    const panel   = document.getElementById('usr-panel');
    if (!overlay || !panel) return;

    usrModoNovo = true;
    usrIdAtual  = null;

    ['novo-usr-nome','novo-usr-cpf','novo-usr-username','novo-usr-email','novo-usr-senha'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
    const erroEl = document.getElementById('novo-usr-erro');
    if (erroEl) erroEl.style.display = 'none';

    document.getElementById('usr-avatar').textContent       = '+';
    document.getElementById('usr-panel-nome').textContent   = 'Novo Usuário';
    document.getElementById('usr-panel-username').textContent = '';
    document.getElementById('usr-panel-loading').style.display  = 'none';
    document.getElementById('usr-panel-conteudo').style.display = 'none';
    document.getElementById('usr-panel-novo').style.display     = 'block';
    document.getElementById('usr-save-bar').classList.add('visivel');
    document.querySelector('#usr-save-bar .btn-salvar').innerHTML = '<i class="fas fa-check"></i> Cadastrar';

    const btnMenu = document.getElementById('btn-menu-usr');
    if (btnMenu) btnMenu.style.visibility = 'hidden';

    overlay.classList.add('open');
    panel.classList.add('open');
    document.getElementById('novo-usr-nome')?.focus();
}

function salvarNovoUsuario() {
    const dados = {
        nome:     document.getElementById('novo-usr-nome')?.value.trim(),
        cpf:      document.getElementById('novo-usr-cpf')?.value.trim(),
        username: document.getElementById('novo-usr-username')?.value.trim(),
        email:    document.getElementById('novo-usr-email')?.value.trim(),
        senha:    document.getElementById('novo-usr-senha')?.value,
        perfil:   document.getElementById('novo-usr-perfil')?.value,
    };

    const erro = document.getElementById('novo-usr-erro');
    if (!dados.nome || !dados.cpf || !dados.username || !dados.email || !dados.senha) {
        erro.textContent = 'Todos os campos obrigatórios devem ser preenchidos.';
        erro.style.display = 'block'; return;
    }
    if (dados.senha.length < 6) {
        erro.textContent = 'Senha deve ter pelo menos 6 caracteres.';
        erro.style.display = 'block'; return;
    }

    const msg = document.getElementById('usr-save-msg');
    msg.textContent = 'Cadastrando...';

    fetch('/api/usuario-criar.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dados)
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) { fecharUsuario(); window.location.href = '?page=usuarios'; }
        else { erro.textContent = data.erro || 'Erro ao cadastrar.'; erro.style.display = 'block'; msg.textContent = ''; }
    })
    .catch(() => { msg.textContent = 'Erro de conexão.'; });
}

function excluirUsuario() {
    if (!usrIdAtual) return;
    const nome = document.getElementById('usr-panel-nome')?.textContent;
    if (!confirm(`Excluir o usuário "${nome}"?`)) return;

    fetch('/api/usuario-excluir.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: usrIdAtual })
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) { fecharUsuario(); window.location.href = '?page=usuarios'; }
        else { alert(data.erro || 'Erro ao excluir.'); }
    });
}

function toggleMenuUsr(e) {
    e.stopPropagation();
    const menu = document.getElementById('menu-usr-dropdown');
    if (menu) menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

document.addEventListener('click', () => {
    const menu = document.getElementById('menu-usr-dropdown');
    if (menu) menu.style.display = 'none';
});

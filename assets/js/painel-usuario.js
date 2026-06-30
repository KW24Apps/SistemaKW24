/**
 * KW24 - Painel lateral de usuário
 */

let usrIdAtual      = null;
let usrModoNovo     = false;
let usrEdicoes      = {};
let usrProfileOrig  = '';   // profile_id original do usuário em edição

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
            preencherUsuario(data.usuario, data.clientes || []);
        })
        .catch(() => fecharUsuario());
}

function preencherUsuario(u, clientes) {
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
    document.getElementById('uf-perfil').textContent   = perfis[u.perfil] || u.perfil;
    document.getElementById('uf-acesso').textContent   = u.ultimo_acesso ? new Date(u.ultimo_acesso).toLocaleString('pt-BR') : 'Nunca';
    document.getElementById('uf-ativo').textContent    = u.ativo ? 'Ativo' : 'Inativo';

    usrProfileOrig = u.profile_id != null ? String(u.profile_id) : '';

    // Popula e pré-seleciona o select de Perfil de Permissão
    const sel = document.getElementById('uf-profile-sel');
    if (sel) {
        fetch('/api/permission-profiles.php?action=list', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(json => {
                sel.innerHTML = '<option value="">Sem perfil específico</option>';
                (json.data || []).forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = String(p.id);
                    opt.textContent = p.nome;
                    if (String(p.id) === usrProfileOrig) opt.selected = true;
                    sel.appendChild(opt);
                });
            }).catch(() => {});
    }

    renderUsuarioClientes(clientes || []);

    document.getElementById('usr-panel-loading').style.display  = 'none';
    document.getElementById('usr-panel-conteudo').style.display = 'block';
}

function usrProfileChanged() {
    document.getElementById('usr-save-bar').classList.add('visivel');
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
    // Restaura select de perfil ao valor original
    const sel = document.getElementById('uf-profile-sel');
    if (sel) sel.value = usrProfileOrig;
    usrEdicoes = {};
    const bar = document.getElementById('usr-save-bar');
    if (bar) bar.classList.remove('visivel');
}

function cancelarUsuario() {
    if (usrModoNovo) { fecharUsuario(); } else { cancelarEdicoesUsr(); }
}

function salvarUsuario() {
    if (usrModoNovo) { salvarNovoUsuario(); return; }

    // Inclui profile_id se foi alterado (select) mesmo que usrEdicoes esteja vazio
    const selProfile = document.getElementById('uf-profile-sel');
    const profileAtual = selProfile ? selProfile.value : usrProfileOrig;
    if (profileAtual !== usrProfileOrig) {
        usrEdicoes['profile_id'] = profileAtual === '' ? null : parseInt(profileAtual, 10);
    }

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
            if ('profile_id' in usrEdicoes) {
                usrProfileOrig = usrEdicoes['profile_id'] != null ? String(usrEdicoes['profile_id']) : '';
            }
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

    // Carrega perfis de permissão
    fetch('/api/permission-profiles.php?action=list', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(({ data }) => {
            const sel = document.getElementById('novo-usr-profile-id');
            if (!sel) return;
            sel.innerHTML = '<option value="">Sem perfil específico</option>';
            (data || []).forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.nome;
                sel.appendChild(opt);
            });
        }).catch(() => {});

    // Carrega lista de empresas (clientes)
    fetch('/api/permission-profiles.php?action=clientes', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(({ data }) => {
            const sel = document.getElementById('novo-usr-cliente-id');
            if (!sel) return;
            sel.innerHTML = '<option value="">Nenhuma</option>';
            (data || []).forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.nome;
                sel.appendChild(opt);
            });
        }).catch(() => {});

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
    const profileRaw  = document.getElementById('novo-usr-profile-id')?.value;
    const clienteRaw  = document.getElementById('novo-usr-cliente-id')?.value;
    const dados = {
        nome:       document.getElementById('novo-usr-nome')?.value.trim(),
        cpf:        document.getElementById('novo-usr-cpf')?.value.trim(),
        username:   document.getElementById('novo-usr-username')?.value.trim(),
        email:      document.getElementById('novo-usr-email')?.value.trim(),
        senha:      document.getElementById('novo-usr-senha')?.value,
        perfil:     document.getElementById('novo-usr-perfil')?.value,
        profile_id: profileRaw  ? parseInt(profileRaw, 10)  : null,
        cliente_id: clienteRaw  ? parseInt(clienteRaw, 10)  : null,
    };

    const erro = document.getElementById('novo-usr-erro');
    if (!dados.nome || !dados.cpf || !dados.username || !dados.senha) {
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

async function excluirUsuario() {
    if (!usrIdAtual) return;
    const nome = document.getElementById('usr-panel-nome')?.textContent;
    const ok = await kwConfirm(`Deseja excluir o usuário "${nome}"?`, 'Excluir usuário');
    if (!ok) return;

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

// ===== CLIENTES VINCULADOS AO USUÁRIO =====

function renderUsuarioClientes(clientes) {
    const lista = document.getElementById('usr-clientes-lista');
    if (!lista) return;
    if (!clientes.length) {
        lista.innerHTML = '<p style="color:#a0aec0;font-size:.82rem">Nenhum cliente vinculado.</p>';
        return;
    }
    lista.innerHTML = clientes.map(c => `
        <div style="display:flex;align-items:center;gap:.5rem;padding:.4rem .5rem;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;margin-bottom:.4rem">
            <div style="flex:1;font-size:.82rem;font-weight:600;color:#2d3748;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${_esc(c.nome)}</div>
            <button onclick="desvincularClienteUsuario(${c.id},'${_esc(c.nome)}')"
                style="background:none;border:none;cursor:pointer;color:#a0aec0;font-size:.75rem;padding:.2rem .35rem;border-radius:4px;flex-shrink:0"
                title="Desvincular" onmouseover="this.style.color='#c53030'" onmouseout="this.style.color='#a0aec0'">
                <i class="fas fa-unlink"></i>
            </button>
        </div>`).join('');
}

function abrirVincularCliente() {
    if (!usrIdAtual) return;
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(6,25,32,.55);backdrop-filter:blur(3px);z-index:9999;display:flex;align-items:center;justify-content:center';
    overlay.innerHTML = `
        <div style="background:#fff;border-radius:16px;padding:2rem;width:440px;max-width:92vw;box-shadow:0 24px 60px rgba(0,0,0,.25);animation:kwPop .18s ease">
            <h3 style="font-size:1rem;font-weight:700;color:#1a202c;margin:0 0 1rem">Vincular cliente</h3>
            <div style="margin-bottom:1rem">
                <label style="font-size:.72rem;font-weight:700;color:#4a5568;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.35rem">Cliente *</label>
                <select id="vcl-select" class="form-input" style="font-size:.85rem">
                    <option value="">Carregando...</option>
                </select>
            </div>
            <div id="vcl-erro" style="display:none;color:#c53030;font-size:.78rem;margin-bottom:.5rem"></div>
            <div style="display:flex;gap:.75rem">
                <button id="vcl-cancel" style="flex:1;padding:.6rem;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:#718096;font-size:.875rem;cursor:pointer;font-weight:600">Cancelar</button>
                <button id="vcl-ok" style="flex:2;padding:.6rem;border:none;border-radius:8px;background:#0DC2FF;color:#fff;font-size:.875rem;cursor:pointer;font-weight:700"><i class="fas fa-link"></i> Vincular</button>
            </div>
        </div>`;
    document.body.appendChild(overlay);

    const sel    = overlay.querySelector('#vcl-select');
    const erroEl = overlay.querySelector('#vcl-erro');
    const close  = () => overlay.remove();
    overlay.querySelector('#vcl-cancel').onclick = close;
    overlay.addEventListener('click', e => { if (e.target === overlay) close(); });

    fetch('/api/permission-profiles.php?action=clientes', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(({ data }) => {
            if (!data || !data.length) { sel.innerHTML = '<option value="">Nenhum cliente disponível</option>'; overlay.querySelector('#vcl-ok').disabled = true; return; }
            sel.innerHTML = '<option value="">— Selecione —</option>' +
                (data || []).map(c => `<option value="${c.id}">${_esc(c.nome)}</option>`).join('');
        }).catch(() => { sel.innerHTML = '<option value="">Erro ao carregar</option>'; });

    overlay.querySelector('#vcl-ok').onclick = () => {
        const cid = parseInt(sel.value, 10);
        if (!cid) { erroEl.textContent = 'Selecione um cliente.'; erroEl.style.display = 'block'; return; }
        const btn = overlay.querySelector('#vcl-ok');
        btn.disabled = true; btn.textContent = 'Vinculando...';
        fetch('/api/cliente-vincular-usuario.php', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cliente_id: cid, usuario_id: usrIdAtual })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-link"></i> Vincular'; erroEl.textContent = data.erro || 'Erro.'; erroEl.style.display = 'block'; return; }
            close();
            fetch('/api/usuario-detalhe.php?id=' + usrIdAtual, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => { if (d.clientes) renderUsuarioClientes(d.clientes); });
        })
        .catch(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-link"></i> Vincular'; erroEl.textContent = 'Erro de conexão.'; erroEl.style.display = 'block'; });
    };
}

async function desvincularClienteUsuario(clienteId, nome) {
    if (!usrIdAtual) return;
    const ok = await kwConfirm(`Desvincular do cliente "${nome}"?`, 'Desvincular cliente');
    if (!ok) return;
    fetch('/api/cliente-desvincular-usuario.php', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cliente_id: clienteId, usuario_id: usrIdAtual })
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            fetch('/api/usuario-detalhe.php?id=' + usrIdAtual, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => { if (d.clientes) renderUsuarioClientes(d.clientes); });
        } else { alert(data.erro || 'Erro ao desvincular.'); }
    });
}

function _esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

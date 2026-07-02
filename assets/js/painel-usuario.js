/**
 * KW24 - Painel lateral de usuário
 */

let usrIdAtual      = null;
let usrModoNovo     = false;
let usrEdicoes      = {};
let usrProfileOrig  = '';   // profile_id original do usuário em edição
let usrPerfilOrig   = '';   // perfil original do usuário em edição

// Perfil do usuário LOGADO (injetado em public/usuarios.php)
const USR_PERFIL       = window.USR_PERFIL || '';
const USR_READONLY     = USR_PERFIL === 'usuario_cliente';   // só a própria ficha, leitura
const USR_IS_ADMIN_CLI = USR_PERFIL === 'admin_cliente';
let   usrAcessosCatalogo = [];   // catálogo de relatórios (list-relatorios)

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
            preencherUsuario(data.usuario, data.clientes || [], data.criado_por, data.acessos || []);
        })
        .catch(() => fecharUsuario());
}

function preencherUsuario(u, clientes, criadoPor, acessos) {
    document.getElementById('usr-avatar').textContent      = (u.nome || '--').substring(0, 2).toUpperCase();
    document.getElementById('usr-panel-nome').textContent  = u.nome;
    document.getElementById('usr-panel-username').textContent = '@' + u.username;

    document.getElementById('uf-id').textContent       = u.id;
    document.getElementById('uf-nome').textContent     = u.nome      || '—';
    document.getElementById('uf-username').textContent = u.username  || '—';
    document.getElementById('uf-email').textContent    = u.email     || '—';
    document.getElementById('uf-cargo').textContent    = u.cargo     || '—';
    document.getElementById('uf-telefone').textContent = u.telefone  || '—';
    const cpEl = document.getElementById('uf-criado-por');
    if (cpEl) cpEl.textContent = (criadoPor && criadoPor.nome) ? criadoPor.nome : '—';

    document.getElementById('uf-acesso').textContent   = u.ultimo_acesso ? new Date(u.ultimo_acesso).toLocaleString('pt-BR') : 'Nunca';
    document.getElementById('uf-ativo').textContent    = u.ativo ? 'Ativo' : 'Inativo';

    // Perfil (select editável). admin_cliente não pode definir admin_interno;
    // usuario_cliente (leitura) fica desabilitado.
    usrPerfilOrig = u.perfil || '';
    const perfilSel = document.getElementById('uf-perfil-sel');
    if (perfilSel) {
        const optAI = perfilSel.querySelector('option[value="admin_interno"]');
        if (USR_IS_ADMIN_CLI && optAI) optAI.remove();
        perfilSel.value = usrPerfilOrig;
        perfilSel.disabled = USR_READONLY;
    }

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
    usrCarregarAcessos(acessos || []);

    // usuario_cliente: modo leitura total — sem menu, sem barra de salvar, sem vincular.
    const btnMenu = document.getElementById('btn-menu-usr');
    if (btnMenu) btnMenu.style.visibility = USR_READONLY ? 'hidden' : '';
    document.querySelectorAll('#usr-panel-conteudo [onclick="abrirVincularCliente()"]').forEach(b => {
        b.style.display = USR_READONLY ? 'none' : '';
    });
    if (USR_READONLY) document.getElementById('usr-save-bar').classList.remove('visivel');

    document.getElementById('usr-panel-loading').style.display  = 'none';
    document.getElementById('usr-panel-conteudo').style.display = 'block';
}

function usrProfileChanged() {
    document.getElementById('usr-save-bar').classList.add('visivel');
}
function usrPerfilChanged() {
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
    if (USR_READONLY) return;
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
    const selP = document.getElementById('uf-perfil-sel');
    if (selP) selP.value = usrPerfilOrig;
    usrEdicoes = {};
    const bar = document.getElementById('usr-save-bar');
    if (bar) bar.classList.remove('visivel');
}

function cancelarUsuario() {
    if (usrModoNovo) { fecharUsuario(); } else { cancelarEdicoesUsr(); }
}

function salvarUsuario() {
    if (usrModoNovo) { salvarNovoUsuario(); return; }

    // Inclui profile_id/perfil se foram alterados (selects) mesmo que usrEdicoes esteja vazio
    const selProfile = document.getElementById('uf-profile-sel');
    const profileAtual = selProfile ? selProfile.value : usrProfileOrig;
    if (profileAtual !== usrProfileOrig) {
        usrEdicoes['profile_id'] = profileAtual === '' ? null : parseInt(profileAtual, 10);
    }
    const selPerfil = document.getElementById('uf-perfil-sel');
    const perfilAtual = selPerfil ? selPerfil.value : usrPerfilOrig;
    if (perfilAtual && perfilAtual !== usrPerfilOrig) {
        usrEdicoes['perfil'] = perfilAtual;
    }

    if (!usrIdAtual) return;

    const msg = document.getElementById('usr-save-msg');
    msg.textContent = 'Salvando...';

    // 1) salva os campos editados (se houver); 2) salva os acessos (sempre).
    const temEdits = Object.keys(usrEdicoes).length > 0;
    const passoCampos = temEdits
        ? fetch('/api/usuario-atualizar.php', {
              method: 'POST', credentials: 'same-origin',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ id: usrIdAtual, ...usrEdicoes })
          }).then(r => r.json())
        : Promise.resolve({ sucesso: true });

    passoCampos.then(data => {
        if (!data.sucesso) { msg.textContent = data.erro || 'Erro ao salvar.'; throw new Error('stop'); }
        document.querySelectorAll('#usr-panel .panel-field.editando').forEach(f => {
            const campo = f.getAttribute('data-usr-campo');
            const span  = f.querySelector('span');
            const input = f.querySelector('input');
            if (span) span.textContent = usrEdicoes[campo] || '—';
            if (input) input.remove();
            if (span) span.style.display = '';
            f.classList.remove('editando');
        });
        if (usrEdicoes['nome']) document.getElementById('usr-panel-nome').textContent = usrEdicoes['nome'];
        if ('profile_id' in usrEdicoes) usrProfileOrig = usrEdicoes['profile_id'] != null ? String(usrEdicoes['profile_id']) : '';
        if ('perfil' in usrEdicoes) usrPerfilOrig = usrEdicoes['perfil'];
        usrEdicoes = {};
        return fetch('/api/usuario-acessos.php?action=salvar', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ usuario_id: usrIdAtual, acessos: coletarAcessos() })
        }).then(r => r.json());
    })
    .then(res => {
        if (res && res.sucesso === false) { msg.textContent = res.erro || 'Erro ao salvar acessos.'; return; }
        document.getElementById('usr-save-bar').classList.remove('visivel');
        msg.textContent = '';
    })
    .catch(e => { if (e.message !== 'stop') msg.textContent = 'Erro de conexão.'; });
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

    // Carrega empresas: admin_cliente vê só as suas (minhas-empresas); demais, todas.
    const empUrl = USR_IS_ADMIN_CLI ? '/api/usuarios.php?action=minhas-empresas' : '/api/permission-profiles.php?action=clientes';
    fetch(empUrl, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(json => {
            const lista = json.empresas || json.data || [];
            const sel = document.getElementById('novo-usr-cliente-id');
            if (!sel) return;
            sel.innerHTML = '<option value="">Nenhuma</option>';
            lista.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.nome;
                sel.appendChild(opt);
            });
        }).catch(() => {});

    // admin_cliente: perfil só Admin Cliente / Usuário Cliente; sem "Perfil de Permissão".
    if (USR_IS_ADMIN_CLI) {
        const perfilSel = document.getElementById('novo-usr-perfil');
        const optAI = perfilSel ? perfilSel.querySelector('option[value="admin_interno"]') : null;
        if (optAI) optAI.remove();
        if (perfilSel) perfilSel.value = 'usuario_cliente';
        const profileField = document.getElementById('novo-usr-profile-id')?.closest('.panel-field');
        if (profileField) profileField.style.display = 'none';
    }

    // Seção "Acessos a Relatórios" (vazia)
    usrCarregarAcessos([]);

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
        acessos:    coletarAcessos(),
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

// ===== ACESSOS A RELATÓRIOS =====

// Mostra e popula a seção de acessos. `atuais` = [{relatorio_id, pode_portal}] (edição) ou [] (criação).
function usrCarregarAcessos(atuais) {
    const wrap = document.getElementById('usr-acessos-wrap');
    const list = document.getElementById('usr-acessos-list');
    if (!wrap || !list) return;
    if (USR_READONLY) { wrap.style.display = 'none'; return; }  // usuario_cliente não gerencia acessos
    const sel = {};
    (atuais || []).forEach(a => { sel[a.relatorio_id] = !!a.pode_portal; });
    list.innerHTML = '<p style="color:#a0aec0;font-size:.82rem">Carregando…</p>';
    wrap.style.display = 'block';
    fetch('/api/usuario-acessos.php?action=list-relatorios', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(d => { usrAcessosCatalogo = d.relatorios || []; renderAcessos(usrAcessosCatalogo, sel); })
        .catch(() => { list.innerHTML = '<p style="color:#fc8181;font-size:.82rem">Erro ao carregar relatórios.</p>'; });
}

function renderAcessos(relatorios, sel) {
    const list = document.getElementById('usr-acessos-list');
    if (!relatorios.length) { list.innerHTML = '<p style="color:#a0aec0;font-size:.82rem">Nenhum relatório disponível.</p>'; return; }
    const grupos = {};
    relatorios.forEach(r => { const g = r.grupo || 'outros'; (grupos[g] = grupos[g] || []).push(r); });
    let html = '';
    Object.keys(grupos).forEach(g => {
        const gl = g.charAt(0).toUpperCase() + g.slice(1);
        html += `<div class="acesso-grupo" data-grupo="${_esc(g)}" style="margin-bottom:.6rem">
            <label style="display:flex;align-items:center;gap:.5rem;font-weight:700;color:#2d3748;font-size:.82rem;cursor:pointer;margin-bottom:.3rem">
                <input type="checkbox" class="acesso-grupo-chk" onchange="acessoGrupoToggle(this)"> ${_esc(gl)}
            </label>`;
        grupos[g].forEach(r => {
            const has = (r.id in sel);
            const portalOn = has && sel[r.id];
            const portalAllowed = r.admin_pode_portal !== false;
            html += `<div class="acesso-rel-item" data-rid="${r.id}" data-portal-allowed="${portalAllowed ? 1 : 0}"
                    style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.25rem .5rem .25rem 1.4rem">
                <label style="display:flex;align-items:center;gap:.5rem;font-size:.8rem;color:#4a5568;cursor:pointer;flex:1">
                    <input type="checkbox" class="acesso-rel-chk" value="${r.id}" ${has ? 'checked' : ''} onchange="acessoRelToggle(this)"> ${_esc(r.nome_amigavel)}
                </label>
                <label class="acesso-portal" style="display:flex;align-items:center;gap:.35rem;font-size:.72rem;color:#718096;cursor:pointer;white-space:nowrap${portalAllowed ? '' : ';opacity:.4'}">
                    <input type="checkbox" class="acesso-portal-chk" ${portalOn ? 'checked' : ''} ${(has && portalAllowed) ? '' : 'disabled'} onchange="usrMarcarAlterado()"> Pode criar portal
                </label>
            </div>`;
        });
        html += `</div>`;
    });
    list.innerHTML = html;
    document.querySelectorAll('#usr-acessos-list .acesso-grupo').forEach(syncGrupoChk);
}

function acessoGrupoToggle(chk) {
    const grupo = chk.closest('.acesso-grupo');
    grupo.querySelectorAll('.acesso-rel-chk').forEach(rc => { rc.checked = chk.checked; acessoRelToggle(rc, true); });
    usrMarcarAlterado();
}
function acessoRelToggle(rc, skipMark) {
    const item    = rc.closest('.acesso-rel-item');
    const portal  = item.querySelector('.acesso-portal-chk');
    const allowed = item.getAttribute('data-portal-allowed') === '1';
    if (rc.checked && allowed) { portal.disabled = false; }
    else { portal.disabled = true; portal.checked = false; }
    syncGrupoChk(item.closest('.acesso-grupo'));
    if (!skipMark) usrMarcarAlterado();
}
function syncGrupoChk(grupo) {
    const rels    = grupo.querySelectorAll('.acesso-rel-chk');
    const marcado = grupo.querySelectorAll('.acesso-rel-chk:checked');
    const gchk    = grupo.querySelector('.acesso-grupo-chk');
    if (gchk) gchk.checked = rels.length > 0 && marcado.length === rels.length;
}
function coletarAcessos() {
    const out = [];
    document.querySelectorAll('#usr-acessos-list .acesso-rel-item').forEach(item => {
        const rc = item.querySelector('.acesso-rel-chk');
        if (rc && rc.checked) {
            const portal = item.querySelector('.acesso-portal-chk');
            out.push({ relatorio_id: parseInt(rc.value, 10), pode_portal: !!(portal && portal.checked) });
        }
    });
    return out;
}
// Mostra a barra de salvar (usado quando só os acessos mudam, sem editar campos).
function usrMarcarAlterado() {
    if (usrModoNovo) return;
    const bar = document.getElementById('usr-save-bar');
    if (bar) bar.classList.add('visivel');
}

// ===== REDEFINIR SENHA =====
function abrirResetSenha() {
    const menu = document.getElementById('menu-usr-dropdown');
    if (menu) menu.style.display = 'none';
    if (!usrIdAtual) return;
    const nome = document.getElementById('usr-panel-nome')?.textContent || 'usuário';
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(6,25,32,.55);backdrop-filter:blur(3px);z-index:9999;display:flex;align-items:center;justify-content:center';
    overlay.innerHTML = `
        <div style="background:#fff;border-radius:16px;padding:2rem;width:420px;max-width:92vw;box-shadow:0 24px 60px rgba(0,0,0,.25)">
            <h3 style="font-size:1rem;font-weight:700;color:#1a202c;margin:0 0 .35rem">Redefinir senha</h3>
            <p style="font-size:.82rem;color:#718096;margin:0 0 1rem">${_esc(nome)}</p>
            <div style="margin-bottom:.75rem">
                <label style="font-size:.72rem;font-weight:700;color:#4a5568;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.3rem">Nova senha *</label>
                <input id="rs-senha" type="password" class="form-input" placeholder="Mínimo 6 caracteres" autocomplete="new-password" style="font-size:.85rem">
            </div>
            <div style="margin-bottom:1rem">
                <label style="font-size:.72rem;font-weight:700;color:#4a5568;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.3rem">Confirmar senha *</label>
                <input id="rs-senha2" type="password" class="form-input" placeholder="Repita a senha" autocomplete="new-password" style="font-size:.85rem">
            </div>
            <div id="rs-erro" style="display:none;color:#c53030;font-size:.78rem;margin-bottom:.5rem"></div>
            <div style="display:flex;gap:.75rem">
                <button id="rs-cancel" style="flex:1;padding:.6rem;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:#718096;font-size:.875rem;cursor:pointer;font-weight:600">Cancelar</button>
                <button id="rs-ok" style="flex:2;padding:.6rem;border:none;border-radius:8px;background:#0DC2FF;color:#fff;font-size:.875rem;cursor:pointer;font-weight:700"><i class="fas fa-key"></i> Redefinir</button>
            </div>
        </div>`;
    document.body.appendChild(overlay);
    const erroEl = overlay.querySelector('#rs-erro');
    const close  = () => overlay.remove();
    overlay.querySelector('#rs-cancel').onclick = close;
    overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
    overlay.querySelector('#rs-ok').onclick = () => {
        const s1 = overlay.querySelector('#rs-senha').value;
        const s2 = overlay.querySelector('#rs-senha2').value;
        if (s1.length < 6) { erroEl.textContent = 'A senha deve ter pelo menos 6 caracteres.'; erroEl.style.display = 'block'; return; }
        if (s1 !== s2)     { erroEl.textContent = 'As senhas não coincidem.'; erroEl.style.display = 'block'; return; }
        const btn = overlay.querySelector('#rs-ok'); btn.disabled = true; btn.textContent = 'Salvando...';
        fetch('/api/usuario-senha.php', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: usrIdAtual, senha: s1 })
        })
        .then(r => r.json())
        .then(d => {
            if (d.sucesso) { close(); }
            else { btn.disabled = false; btn.innerHTML = '<i class="fas fa-key"></i> Redefinir'; erroEl.textContent = d.erro || 'Erro.'; erroEl.style.display = 'block'; }
        })
        .catch(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-key"></i> Redefinir'; erroEl.textContent = 'Erro de conexão.'; erroEl.style.display = 'block'; });
    };
}

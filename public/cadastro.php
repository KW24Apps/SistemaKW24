<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php');
    exit;
}
?>
<link rel="stylesheet" href="/assets/css/painel-cliente.css">
<?php

require_once __DIR__ . '/../helpers/Database.php';

try {
    $db    = Database::getInstance();
    $busca = trim($_GET['busca'] ?? '');

    if ($busca) {
        $clientes = $db->fetchAll(
            "SELECT id, nome, cnpj, telefone, email FROM clientes
             WHERE nome ILIKE :b OR cnpj ILIKE :b OR email ILIKE :b
             ORDER BY nome ASC",
            ['b' => "%{$busca}%"]
        );
    } else {
        $clientes = $db->fetchAll("SELECT id, nome, cnpj, telefone, email FROM clientes ORDER BY nome ASC");
    }
    $total = count($clientes);

} catch (Exception $e) {
    echo '<div style="color:#e53e3e;padding:2rem">Erro ao carregar clientes: ' . htmlspecialchars($e->getMessage()) . '</div>';
    return;
}
?>
<link rel="stylesheet" href="/assets/css/clientes.css">

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-building"></i> Clientes</h1>
    <div class="page-header-actions">
        <form method="GET" style="display:contents">
            <input type="hidden" name="page" value="cadastro">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" name="busca" placeholder="Buscar por nome, CNPJ ou e-mail..."
                       value="<?= htmlspecialchars($busca) ?>" autocomplete="off">
            </div>
        </form>
        <a href="?page=cadastro&action=novo" class="btn-primary">
            <i class="fas fa-plus"></i> Novo Cliente
        </a>
    </div>
</div>

<div class="table-panel">
    <table class="clientes-table">
        <thead>
            <tr>
                <th><input type="checkbox"></th>
                <th>ID</th>
                <th>Cliente</th>
                <th>CNPJ</th>
                <th>Telefone</th>
                <th>E-mail</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($clientes)): ?>
            <tr><td colspan="8">
                <div class="empty-state">
                    <i class="fas fa-building"></i>
                    <p>Nenhum cliente encontrado.</p>
                </div>
            </td></tr>
        <?php else: ?>
            <?php foreach ($clientes as $c): ?>
            <tr ondblclick="abrirCliente(<?= $c['id'] ?>)" style="cursor:pointer">
                <td onclick="event.stopPropagation()"><input type="checkbox"></td>
                <td style="color:#4a5568;font-size:.85rem"><?= $c['id'] ?></td>
                <td>
                    <div class="cliente-info">
                        <div class="cliente-avatar"><?= mb_strtoupper(mb_substr($c['nome'], 0, 2)) ?></div>
                        <span class="cliente-nome" onclick="abrirCliente(<?= $c['id'] ?>); event.stopPropagation()">
                            <?= htmlspecialchars($c['nome']) ?>
                        </span>
                    </div>
                </td>
                <td><?= htmlspecialchars($c['cnpj'] ?? '—') ?></td>
                <td><?= htmlspecialchars($c['telefone'] ?? '—') ?></td>
                <td><?= htmlspecialchars($c['email'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    <div class="table-footer"><?= $total ?> cliente<?= $total !== 1 ? 's' : '' ?> encontrado<?= $total !== 1 ? 's' : '' ?></div>
</div>

<!-- Modal de configuração de app -->
<div id="app-config-overlay" class="cliente-overlay" onclick="fecharModalApp()" style="z-index:1100"></div>
<div id="app-config-modal" class="app-config-modal">
    <div class="app-modal-header">
        <div class="app-modal-icon" id="app-modal-icon"><i class="fas fa-puzzle-piece"></i></div>
        <div>
            <h3 id="app-modal-nome" style="margin:0;font-size:1rem;font-weight:700;color:#1a202c"></h3>
            <p id="app-modal-slug" style="margin:0;font-size:.75rem;color:#a0aec0"></p>
        </div>
        <button class="panel-close" onclick="fecharModalApp()" style="margin-left:auto"><i class="fas fa-times"></i></button>
    </div>
    <div class="app-modal-body" id="app-modal-body">
        <p style="color:#718096;font-size:.9rem">Configurações em construção.</p>
    </div>
</div>

<!-- Modal de ativar aplicação -->
<div id="ativar-overlay" class="cliente-overlay" onclick="fecharModalAtivar()" style="z-index:1100"></div>
<div id="ativar-modal" class="app-config-modal" style="width:480px">
    <div class="app-modal-header">
        <div><h3 style="margin:0;font-size:1rem;font-weight:700;color:#1a202c">Ativar Aplicação</h3></div>
        <button class="panel-close" onclick="fecharModalAtivar()" style="margin-left:auto"><i class="fas fa-times"></i></button>
    </div>
    <div class="app-modal-body" id="ativar-lista"></div>
</div>

<!-- Overlay -->
<div id="cliente-overlay" class="cliente-overlay" onclick="fecharPainel()"></div>

<!-- Painel lateral -->
<div id="cliente-panel" class="cliente-panel">
    <div class="panel-header">
        <div class="panel-avatar" id="panel-avatar">--</div>
        <div class="panel-header-info">
            <h2 class="panel-title" id="panel-nome">Carregando...</h2>
            <p class="panel-subtitle" id="panel-cnpj"></p>
        </div>
        <button class="panel-close" onclick="fecharPainel()"><i class="fas fa-times"></i></button>
    </div>

    <div class="panel-body">
        <div id="panel-loading" class="panel-loading">
            <i class="fas fa-spinner fa-spin"></i> Carregando...
        </div>

        <div id="panel-conteudo" style="display:none">
            <div class="panel-grid">
                <!-- Coluna esquerda: dados editáveis -->
                <div>
                    <div class="panel-section-title">Dados do Cliente</div>
                    <div class="panel-field no-edit"><label>ID</label><span id="pf-id"></span></div>
                    <div class="panel-field" data-campo="nome" onclick="editarCampo(this)"><label>Nome</label><span id="pf-nome"></span></div>
                    <div class="panel-field" data-campo="cnpj" onclick="editarCampo(this)"><label>CNPJ</label><span id="pf-cnpj"></span></div>
                    <div class="panel-field" data-campo="telefone" onclick="editarCampo(this)"><label>Telefone</label><span id="pf-telefone"></span></div>
                    <div class="panel-field" data-campo="email" onclick="editarCampo(this)"><label>E-mail</label><span id="pf-email"></span></div>
                    <div class="panel-field" data-campo="endereco" data-tipo="textarea" onclick="editarCampo(this)"><label>Endereço</label><span id="pf-endereco"></span></div>
                    <div class="panel-divider"></div>
                    <div class="panel-section-title">Integração Bitrix24</div>
                    <div class="panel-field" data-campo="link_bitrix" onclick="editarCampo(this)"><label>Link Bitrix24</label><span id="pf-bitrix"></span></div>
                    <div class="panel-field" data-campo="chave_acesso" onclick="editarCampo(this)"><label>Chave de Acesso</label><span id="pf-chave" style="font-family:monospace;font-size:.8rem;word-break:break-all"></span></div>
                    <div class="panel-field" data-campo="id_bitrix" onclick="editarCampo(this)"><label>ID Bitrix24</label><span id="pf-id-bitrix"></span></div>
                </div>

                <!-- Coluna direita: apps ativas -->
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
                        <div class="panel-apps-title" style="margin:0">Aplicações</div>
                        <button class="btn-ativar-app" onclick="abrirModalAtivar()">
                            <i class="fas fa-plus"></i> Ativar
                        </button>
                    </div>
                    <div id="panel-apps-lista"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de salvar (aparece quando há edições) -->
    <div class="panel-save-bar" id="panel-save-bar">
        <button class="btn-salvar" onclick="salvarEdicoes()"><i class="fas fa-check"></i> Salvar</button>
        <button class="btn-cancelar-edit" onclick="cancelarEdicoes()">Cancelar</button>
        <span class="save-bar-msg" id="save-msg"></span>
    </div>
</div>

<script>
const iconeApp = {
    clicksign: 'fas fa-file-signature',
    deal:      'fas fa-handshake',
    task:      'fas fa-tasks',
    company:   'fas fa-building',
    omie:      'fas fa-calculator',
    receita:   'fas fa-search',
    import:    'fas fa-upload',
    disk:      'fas fa-hdd',
    calcdata:  'fas fa-calendar-alt',
    mediahora: 'fas fa-clock',
    scheduler: 'fas fa-robot',
    geraroptnd:'fas fa-magic',
    extenso:   'fas fa-font',
    validar_cnpj: 'fas fa-id-card'
};

function abrirCliente(id) {
    clienteIdAtual = id;
    edicoesPendentes = {};
    document.getElementById('cliente-overlay').classList.add('open');
    document.getElementById('cliente-panel').classList.add('open');
    document.getElementById('panel-loading').style.display = 'flex';
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');

    fetch('/api/cliente-detalhe.php?id=' + id, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (data.erro) { alert(data.erro); fecharPainel(); return; }
            preencherPainel(data.cliente, data.aplicacoes);
        })
        .catch(() => fecharPainel());
}

let todasApps = [];
let appsAtivas = [];

function preencherPainel(c, apps) {
    // Header
    document.getElementById('panel-avatar').textContent = (c.nome || '--').substring(0, 2).toUpperCase();
    document.getElementById('panel-nome').textContent   = c.nome || '—';
    document.getElementById('panel-cnpj').textContent   = c.cnpj ? 'CNPJ: ' + c.cnpj : '';

    // Campos
    document.getElementById('pf-id').textContent        = c.id;
    document.getElementById('pf-nome').textContent      = c.nome         || '—';
    document.getElementById('pf-cnpj').textContent      = c.cnpj         || '—';
    document.getElementById('pf-telefone').textContent  = c.telefone     || '—';
    document.getElementById('pf-email').textContent     = c.email        || '—';
    document.getElementById('pf-endereco').textContent  = c.endereco     || '—';
    document.getElementById('pf-bitrix').textContent    = c.link_bitrix  || '—';
    document.getElementById('pf-chave').textContent     = c.chave_acesso || '—';
    document.getElementById('pf-id-bitrix').textContent = c.id_bitrix    || '—';

    // Guarda apps ativas globalmente
    appsAtivas = apps || [];
    renderAppsAtivas(appsAtivas);

    document.getElementById('panel-loading').style.display = 'none';
    document.getElementById('panel-conteudo').style.display = 'block';
}

function renderAppsAtivas(apps) {
    const lista = document.getElementById('panel-apps-lista');
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

// ===== MODAL DE CONFIGURAÇÃO DE APP =====
function abrirModalApp(app) {
    document.getElementById('app-modal-icon').innerHTML = `<i class="${iconeApp[app.slug] || 'fas fa-puzzle-piece'}"></i>`;
    document.getElementById('app-modal-nome').textContent = app.nome;
    document.getElementById('app-modal-slug').textContent = app.slug;
    document.getElementById('app-modal-body').innerHTML = `
        <p style="color:#718096;font-size:.875rem;margin-bottom:1rem">${app.descricao || ''}</p>
        <div style="padding:2rem;background:#f8fafc;border-radius:8px;border:1px dashed #cbd5e0;text-align:center;color:#a0aec0">
            <i class="fas fa-cog" style="font-size:2.5rem;display:block;margin-bottom:.75rem"></i>
            <strong>Configurações em construção</strong><br>
            <span style="font-size:.8rem">As configurações específicas de ${app.nome} serão implementadas aqui.</span>
        </div>`;
    document.getElementById('app-config-overlay').classList.add('open');
    document.getElementById('app-config-modal').classList.add('open');
}

function fecharModalApp() {
    document.getElementById('app-config-overlay').classList.remove('open');
    document.getElementById('app-config-modal').classList.remove('open');
}

// ===== MODAL DE ATIVAR APP =====
function abrirModalAtivar() {
    fetch('/api/cliente-detalhe.php?id=' + clienteIdAtual, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            const ativasIds = (data.aplicacoes || []).map(a => a.id);
            fetch('/api/aplicacoes-lista.php', { credentials: 'same-origin' })
                .then(r => r.json())
                .then(todas => {
                    const lista = document.getElementById('ativar-lista');
                    lista.innerHTML = todas.map(a => `
                        <div class="app-disponivel ${ativasIds.includes(a.id) ? 'ja-ativo' : ''}"
                             onclick="ativarApp(${a.id}, '${a.nome}')">
                            <div class="app-card-icon"><i class="${iconeApp[a.slug] || 'fas fa-puzzle-piece'}"></i></div>
                            <div class="app-card-info">
                                <div class="app-card-name">${a.nome}</div>
                                <div class="app-card-slug">${a.slug}</div>
                            </div>
                            ${ativasIds.includes(a.id) ? '<span class="badge-app">Ativo</span>' : '<span style="font-size:.75rem;color:#0DC2FF;font-weight:600">Ativar →</span>'}
                        </div>`).join('');
                });
        });

    document.getElementById('ativar-overlay').classList.add('open');
    document.getElementById('ativar-modal').classList.add('open');
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
            // Recarrega dados do painel
            fetch('/api/cliente-detalhe.php?id=' + clienteIdAtual, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => renderAppsAtivas(d.aplicacoes));
        } else { alert(data.erro || 'Erro ao ativar.'); }
    });
}

function fecharPainel() {
    document.getElementById('cliente-overlay').classList.remove('open');
    document.getElementById('cliente-panel').classList.remove('open');
}

function mudarTab(tab, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).style.display = 'block';
    if (btn) btn.classList.add('active');
}

// Fechar com ESC
document.addEventListener('keydown', e => { if (e.key === 'Escape') fecharPainel(); });

// ===== EDIÇÃO INLINE =====
let clienteIdAtual = null;
let edicoesPendentes = {};

function editarCampo(fieldEl) {
    if (fieldEl.classList.contains('editando')) return;
    fieldEl.classList.add('editando');

    const campo = fieldEl.getAttribute('data-campo');
    const tipo  = fieldEl.getAttribute('data-tipo') || 'input';
    const span  = fieldEl.querySelector('span');
    const valorAtual = span.textContent === '—' ? '' : span.textContent;

    span.style.display = 'none';

    let input;
    if (tipo === 'textarea') {
        input = document.createElement('textarea');
    } else {
        input = document.createElement('input');
        input.type = 'text';
    }
    input.value = valorAtual;
    input.setAttribute('data-campo', campo);
    input.setAttribute('data-original', valorAtual);
    fieldEl.appendChild(input);
    input.focus();

    // Mostra barra de salvar
    document.getElementById('panel-save-bar').classList.add('visivel');

    input.addEventListener('input', () => {
        edicoesPendentes[campo] = input.value;
    });
    // Inicializa com valor atual
    edicoesPendentes[campo] = valorAtual;
}

function cancelarEdicoes() {
    document.querySelectorAll('.panel-field.editando').forEach(f => {
        const span  = f.querySelector('span');
        const input = f.querySelector('input, textarea');
        if (input) { input.remove(); }
        span.style.display = '';
        f.classList.remove('editando');
    });
    edicoesPendentes = {};
    document.getElementById('panel-save-bar').classList.remove('visivel');
    document.getElementById('save-msg').textContent = '';
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
            // Atualiza os spans com os novos valores
            document.querySelectorAll('.panel-field.editando').forEach(f => {
                const campo = f.getAttribute('data-campo');
                const span  = f.querySelector('span');
                const input = f.querySelector('input, textarea');
                const novoValor = edicoesPendentes[campo] || '—';
                span.textContent = novoValor || '—';
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
</script>

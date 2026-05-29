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

    <!-- Tabs: Geral + uma por app ativa -->
    <div class="panel-tabs" id="panel-tabs">
        <button class="tab-btn active" data-tab="geral" onclick="mudarTab('geral', this)">
            <i class="fas fa-info-circle"></i> Geral
        </button>
        <!-- Tabs dinâmicas das apps ativas vêm aqui via JS -->
    </div>

    <div class="panel-body">
        <div id="panel-loading" class="panel-loading">
            <i class="fas fa-spinner fa-spin"></i> Carregando...
        </div>

        <!-- Aba Geral -->
        <div class="tab-content" id="tab-geral" style="display:none">
            <div class="panel-grid">
                <!-- Coluna esquerda: dados editáveis -->
                <div>
                    <div class="panel-section-title">Dados do Cliente</div>
                    <div class="panel-field no-edit"><label>ID</label><span id="pf-id"></span></div>
                    <div class="panel-field" data-campo="nome" onclick="editarCampo(this)"><label>Nome <small style="color:#0DC2FF">✎</small></label><span id="pf-nome"></span></div>
                    <div class="panel-field" data-campo="cnpj" onclick="editarCampo(this)"><label>CNPJ <small style="color:#0DC2FF">✎</small></label><span id="pf-cnpj"></span></div>
                    <div class="panel-field" data-campo="telefone" onclick="editarCampo(this)"><label>Telefone <small style="color:#0DC2FF">✎</small></label><span id="pf-telefone"></span></div>
                    <div class="panel-field" data-campo="email" onclick="editarCampo(this)"><label>E-mail <small style="color:#0DC2FF">✎</small></label><span id="pf-email"></span></div>
                    <div class="panel-field" data-campo="endereco" data-tipo="textarea" onclick="editarCampo(this)"><label>Endereço <small style="color:#0DC2FF">✎</small></label><span id="pf-endereco"></span></div>
                    <div class="panel-divider"></div>
                    <div class="panel-section-title">Integração Bitrix24</div>
                    <div class="panel-field" data-campo="link_bitrix" onclick="editarCampo(this)"><label>Link Bitrix24 <small style="color:#0DC2FF">✎</small></label><span id="pf-bitrix"></span></div>
                    <div class="panel-field" data-campo="chave_acesso" onclick="editarCampo(this)"><label>Chave de Acesso <small style="color:#0DC2FF">✎</small></label><span id="pf-chave" style="font-family:monospace;font-size:.8rem;word-break:break-all"></span></div>
                    <div class="panel-field" data-campo="id_bitrix" onclick="editarCampo(this)"><label>ID Bitrix24 <small style="color:#0DC2FF">✎</small></label><span id="pf-id-bitrix"></span></div>
                </div>

                <!-- Coluna direita: ativar/desativar apps -->
                <div>
                    <div class="panel-apps-title">Aplicações</div>
                    <p style="color:#a0aec0;font-size:.78rem;margin-bottom:.75rem">
                        Ative as aplicações disponíveis para este cliente. Cada app ativada ganha um menu de configuração acima.
                    </p>
                    <div id="panel-apps-lista"></div>
                </div>
            </div>
        </div>

        <!-- Tabs de apps ativas (conteúdo dinâmico) -->
        <div id="panel-apps-tabs-content"></div>
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
    document.getElementById('tab-geral').style.display = 'none';
    document.getElementById('tab-aplicacoes').style.display = 'none';

    fetch('/api/cliente-detalhe.php?id=' + id, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (data.erro) { alert(data.erro); fecharPainel(); return; }
            preencherPainel(data.cliente, data.aplicacoes);
        })
        .catch(() => fecharPainel());
}

function preencherPainel(c, apps) {
    // Header
    document.getElementById('panel-avatar').textContent = (c.nome || '--').substring(0, 2).toUpperCase();
    document.getElementById('panel-nome').textContent   = c.nome || '—';
    document.getElementById('panel-cnpj').textContent   = c.cnpj ? 'CNPJ: ' + c.cnpj : '';

    // Campos
    document.getElementById('pf-id').textContent       = c.id;
    document.getElementById('pf-nome').textContent     = c.nome      || '—';
    document.getElementById('pf-cnpj').textContent     = c.cnpj      || '—';
    document.getElementById('pf-telefone').textContent = c.telefone  || '—';
    document.getElementById('pf-email').textContent    = c.email     || '—';
    document.getElementById('pf-endereco').textContent = c.endereco  || '—';
    document.getElementById('pf-bitrix').textContent   = c.link_bitrix  || '—';
    document.getElementById('pf-chave').textContent    = c.chave_acesso || '—';
    document.getElementById('pf-id-bitrix').textContent = c.id_bitrix || '—';

    // Tabs dinâmicas: remove tabs antigas de apps
    const tabsBar = document.getElementById('panel-tabs');
    tabsBar.querySelectorAll('.tab-btn[data-app]').forEach(t => t.remove());

    // Conteúdo dinâmico das tabs de apps
    const appsTabsContent = document.getElementById('panel-apps-tabs-content');
    appsTabsContent.innerHTML = '';

    // Lista de apps com toggle ativo/inativo
    const lista = document.getElementById('panel-apps-lista');
    if (!apps || !apps.length) {
        lista.innerHTML = '<p style="color:#a0aec0;font-size:.85rem">Nenhuma aplicação ativa.</p>';
    } else {
        lista.innerHTML = apps.map(a => `
            <div class="app-card" onclick="mudarTab('app-${a.slug}', document.querySelector('[data-tab=app-${a.slug}]'))">
                <div class="app-card-icon"><i class="${iconeApp[a.slug] || 'fas fa-puzzle-piece'}"></i></div>
                <div class="app-card-info">
                    <div class="app-card-name">${a.nome}</div>
                    <div class="app-card-slug">${a.slug}</div>
                </div>
                <span class="badge-app">Ativo</span>
            </div>`).join('');

        // Cria tab e conteúdo para cada app ativa
        apps.forEach(a => {
            // Tab button
            const btn = document.createElement('button');
            btn.className = 'tab-btn';
            btn.setAttribute('data-tab', 'app-' + a.slug);
            btn.setAttribute('data-app', a.slug);
            btn.onclick = function() { mudarTab('app-' + a.slug, this); };
            btn.innerHTML = `<i class="${iconeApp[a.slug] || 'fas fa-puzzle-piece'}"></i> ${a.nome}`;
            tabsBar.appendChild(btn);

            // Tab content
            const div = document.createElement('div');
            div.className = 'tab-content';
            div.id = 'tab-app-' + a.slug;
            div.style.display = 'none';
            div.innerHTML = `
                <div class="panel-section-title">${a.nome}</div>
                <p style="color:#718096;font-size:.9rem;margin-bottom:1rem">${a.descricao || ''}</p>
                <div style="padding:1.5rem;background:#f8fafc;border-radius:8px;border:1px dashed #cbd5e0;text-align:center;color:#a0aec0">
                    <i class="fas fa-cog" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>
                    Configurações da aplicação <strong>${a.nome}</strong> em construção.
                </div>`;
            appsTabsContent.appendChild(div);
        });
    }

    document.getElementById('panel-loading').style.display = 'none';
    mudarTab('geral', tabsBar.querySelector('[data-tab=geral]'));
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

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

    <div class="panel-tabs">
        <button class="tab-btn active" onclick="mudarTab('geral', this)">
            <i class="fas fa-info-circle"></i> Geral
        </button>
        <button class="tab-btn" onclick="mudarTab('aplicacoes', this)">
            <i class="fas fa-th"></i> Aplicações
        </button>
    </div>

    <div class="panel-body">
        <div id="panel-loading" class="panel-loading">
            <i class="fas fa-spinner fa-spin"></i> Carregando...
        </div>

        <!-- Aba Geral -->
        <div class="tab-content" id="tab-geral" style="display:none">
            <div class="panel-grid">
                <div>
                    <div class="panel-section-title">Dados do Cliente</div>
                    <div class="panel-field"><label>ID</label><span id="pf-id"></span></div>
                    <div class="panel-field"><label>Nome</label><span id="pf-nome"></span></div>
                    <div class="panel-field"><label>CNPJ</label><span id="pf-cnpj"></span></div>
                    <div class="panel-field"><label>Telefone</label><span id="pf-telefone"></span></div>
                    <div class="panel-field"><label>E-mail</label><span id="pf-email"></span></div>
                    <div class="panel-field"><label>Endereço</label><span id="pf-endereco"></span></div>
                    <div class="panel-divider"></div>
                    <div class="panel-section-title">Integração</div>
                    <div class="panel-field"><label>Link Bitrix24</label><span id="pf-bitrix"></span></div>
                    <div class="panel-field"><label>Chave de Acesso</label><span id="pf-chave" style="font-family:monospace;font-size:.8rem;word-break:break-all"></span></div>
                </div>
                <div>
                    <div class="panel-apps-title">Aplicações Ativas</div>
                    <div id="panel-apps-lista"></div>
                </div>
            </div>
        </div>

        <!-- Aba Aplicações -->
        <div class="tab-content" id="tab-aplicacoes" style="display:none">
            <div class="panel-section-title">Gestão de Aplicações</div>
            <p style="color:#718096;font-size:.9rem">Em construção — aqui será possível ativar e configurar aplicações por cliente.</p>
        </div>
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
    const ini = (c.nome || '--').substring(0, 2).toUpperCase();
    document.getElementById('panel-avatar').textContent = ini;
    document.getElementById('panel-nome').textContent   = c.nome || '—';
    document.getElementById('panel-cnpj').textContent   = c.cnpj ? 'CNPJ: ' + c.cnpj : '';

    const v = (val) => val || '<span class="vazio">Não informado</span>';
    document.getElementById('pf-id').textContent        = c.id;
    document.getElementById('pf-nome').textContent      = c.nome || '—';
    document.getElementById('pf-cnpj').textContent      = c.cnpj || '—';
    document.getElementById('pf-telefone').textContent  = c.telefone || '—';
    document.getElementById('pf-email').textContent     = c.email || '—';
    document.getElementById('pf-endereco').textContent  = c.endereco || '—';
    document.getElementById('pf-bitrix').textContent    = c.link_bitrix || '—';
    document.getElementById('pf-chave').textContent     = c.chave_acesso || '—';

    // Apps
    const lista = document.getElementById('panel-apps-lista');
    if (!apps || !apps.length) {
        lista.innerHTML = '<p style="color:#a0aec0;font-size:.85rem">Nenhuma aplicação ativa.</p>';
    } else {
        lista.innerHTML = apps.map(a => `
            <div class="app-card">
                <div class="app-card-icon"><i class="${iconeApp[a.slug] || 'fas fa-puzzle-piece'}"></i></div>
                <div class="app-card-info">
                    <div class="app-card-name">${a.nome}</div>
                    <div class="app-card-slug">${a.slug}</div>
                </div>
                <span class="badge-app">Ativo</span>
            </div>`).join('');
    }

    document.getElementById('panel-loading').style.display = 'none';
    document.getElementById('tab-geral').style.display = 'block';
    mudarTab('geral', document.querySelector('.tab-btn'));
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
</script>

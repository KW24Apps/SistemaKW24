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

<!-- JS em assets/js/painel-cliente.js -->

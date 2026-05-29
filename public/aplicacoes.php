<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../helpers/Database.php';

try {
    $db    = Database::getInstance();
    $busca = trim($_GET['busca'] ?? '');

    if ($busca) {
        $apps = $db->fetchAll(
            "SELECT id, slug, nome, descricao FROM aplicacoes
             WHERE nome ILIKE :b OR slug ILIKE :b ORDER BY nome ASC",
            ['b' => "%{$busca}%"]
        );
    } else {
        $apps = $db->fetchAll("SELECT id, slug, nome, descricao FROM aplicacoes ORDER BY nome ASC");
    }
    $total = count($apps);

} catch (Exception $e) {
    echo '<div style="color:#e53e3e;padding:2rem">Erro: ' . htmlspecialchars($e->getMessage()) . '</div>';
    return;
}

$icones = [
    'clicksign' => 'fa-file-signature', 'deal' => 'fa-handshake',
    'task' => 'fa-tasks', 'company' => 'fa-building', 'omie' => 'fa-calculator',
    'receita' => 'fa-search', 'import' => 'fa-upload', 'disk' => 'fa-hdd',
    'calcdata' => 'fa-calendar-alt', 'mediahora' => 'fa-clock',
    'scheduler' => 'fa-robot', 'geraroptnd' => 'fa-magic',
    'extenso' => 'fa-font', 'validar_cnpj' => 'fa-id-card'
];
?>
<link rel="stylesheet" href="/assets/css/clientes.css">
<link rel="stylesheet" href="/assets/css/painel-cliente.css">

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-th"></i> Aplicações</h1>
    <div class="page-header-actions">
        <form method="GET" style="display:contents">
            <input type="hidden" name="page" value="aplicacoes">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" name="busca" placeholder="Buscar por nome ou slug..."
                       value="<?= htmlspecialchars($busca) ?>" autocomplete="off">
            </div>
        </form>
        <?php if ($user_data['perfil'] === 'admin_interno'): ?>
        <button onclick="abrirNovaAplicacao()" class="btn-primary">
            <i class="fas fa-plus"></i> Nova Aplicação
        </button>
        <?php endif; ?>
    </div>
</div>

<div class="table-panel">
    <div class="table-scroll">
    <table class="clientes-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Aplicação</th>
                <th>Slug</th>
                <th>Descrição</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($apps)): ?>
            <tr><td colspan="4">
                <div class="empty-state">
                    <i class="fas fa-th"></i>
                    <p>Nenhuma aplicação encontrada.</p>
                </div>
            </td></tr>
        <?php else: ?>
            <?php foreach ($apps as $a): ?>
            <tr ondblclick="abrirAplicacao(<?= $a['id'] ?>)" style="cursor:pointer">
                <td style="color:#4a5568;font-size:.85rem"><?= $a['id'] ?></td>
                <td>
                    <div class="cliente-info">
                        <div class="cliente-avatar" style="background:linear-gradient(135deg,#086B8D,#033140)">
                            <i class="fas <?= $icones[$a['slug']] ?? 'fa-puzzle-piece' ?>"></i>
                        </div>
                        <span class="cliente-nome" style="color:#1a202c"
                              onclick="abrirAplicacao(<?= $a['id'] ?>); event.stopPropagation()">
                            <?= htmlspecialchars($a['nome']) ?>
                        </span>
                    </div>
                </td>
                <td style="font-family:monospace;font-size:.82rem;color:#718096"><?= htmlspecialchars($a['slug']) ?></td>
                <td style="color:#718096;font-size:.85rem"><?= htmlspecialchars($a['descricao'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    </div><!-- /table-scroll -->
    <div class="table-footer"><?= $total ?> aplicaç<?= $total !== 1 ? 'ões' : 'ão' ?> encontrada<?= $total !== 1 ? 's' : '' ?></div>
</div>

<!-- Painel lateral de aplicação (reutiliza estrutura do painel-cliente) -->
<div id="app-overlay" class="cliente-overlay" onclick="fecharAplicacao()"></div>

<div id="app-panel" class="cliente-panel" style="width:min(700px,calc(100vw - 160px))">
    <div class="panel-header">
        <div class="panel-avatar" id="app-panel-avatar" style="background:linear-gradient(135deg,#086B8D,#033140)">
            <i id="app-panel-icon" class="fas fa-puzzle-piece"></i>
        </div>
        <div class="panel-header-info">
            <h2 class="panel-title" id="app-panel-nome">Carregando...</h2>
            <p class="panel-subtitle" id="app-panel-slug"></p>
        </div>
        <div style="position:relative;margin-left:auto">
            <button id="btn-menu-app" onclick="toggleMenuApp(event)"
                style="width:36px;height:36px;border:none;background:#f0f4f8;border-radius:50%;cursor:pointer;font-size:1.1rem;color:#718096;display:flex;align-items:center;justify-content:center">
                &#8942;
            </button>
            <div id="menu-app-dropdown" style="display:none;position:absolute;right:0;top:42px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.12);min-width:180px;z-index:100;overflow:hidden">
                <button onclick="excluirAplicacao()" style="width:100%;padding:.7rem 1rem;border:none;background:none;text-align:left;cursor:pointer;color:#c53030;font-size:.875rem;display:flex;align-items:center;gap:.6rem"
                    onmouseover="this.style.background='#fff5f5'" onmouseout="this.style.background='none'">
                    <i class="fas fa-trash" style="width:16px"></i> Excluir aplicação
                </button>
            </div>
        </div>
    </div>

    <div class="panel-body">
        <div id="app-panel-loading" class="panel-loading">
            <i class="fas fa-spinner fa-spin"></i> Carregando...
        </div>

        <!-- Modo visualizar/editar -->
        <div id="app-panel-conteudo" style="display:none">
            <div class="panel-section-title">Dados da Aplicação</div>
            <div class="panel-field no-edit"><label>ID</label><span id="apf-id"></span></div>
            <div class="panel-field" data-app-campo="slug" onclick="editarCampoApp(this)"><label>Slug</label><span id="apf-slug" style="font-family:monospace"></span></div>
            <div class="panel-field" data-app-campo="nome" onclick="editarCampoApp(this)"><label>Nome</label><span id="apf-nome"></span></div>
            <div class="panel-field" data-app-campo="descricao" data-tipo="textarea" onclick="editarCampoApp(this)"><label>Descrição</label><span id="apf-descricao"></span></div>

            <div class="panel-divider"></div>
            <div class="panel-section-title">Clientes com esta aplicação</div>
            <div id="apf-clientes"></div>
        </div>

        <!-- Modo novo -->
        <div id="app-panel-novo" style="display:none">
            <div class="panel-section-title">Nova Aplicação</div>
            <div style="display:grid;gap:.75rem">
                <div class="panel-field no-edit"><label>Slug * <small style="color:#a0aec0;font-weight:400">(identificador único, sem espaços)</small></label>
                    <input type="text" id="nova-app-slug" class="form-input" placeholder="ex: clicksign" required>
                </div>
                <div class="panel-field no-edit"><label>Nome *</label>
                    <input type="text" id="nova-app-nome" class="form-input" placeholder="Nome da aplicação" required>
                </div>
                <div class="panel-field no-edit"><label>Descrição</label>
                    <textarea id="nova-app-descricao" class="form-input" placeholder="Breve descrição da aplicação" rows="3"></textarea>
                </div>
                <div id="nova-app-erro" style="color:#e53e3e;font-size:.85rem;display:none"></div>
            </div>
        </div>
    </div>

    <div class="panel-save-bar" id="app-save-bar">
        <button class="btn-salvar" onclick="salvarAplicacao()"><i class="fas fa-check"></i> Salvar</button>
        <button class="btn-cancelar-edit" onclick="cancelarAplicacao()">Cancelar</button>
        <span id="app-save-msg" class="save-bar-msg"></span>
    </div>
</div>

<!-- JS em assets/js/painel-aplicacao.js (carregado no index.php) -->
<script>
// JS em assets/js/painel-aplicacao.js
</script>

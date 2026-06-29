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
            "SELECT id, slug, nome, descricao, valor FROM aplicacoes
             WHERE nome ILIKE :b OR slug ILIKE :b ORDER BY nome ASC",
            ['b' => "%{$busca}%"]
        );
    } else {
        $apps = $db->fetchAll("SELECT id, slug, nome, descricao, valor FROM aplicacoes ORDER BY nome ASC");
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
                <th>Valor</th>
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
            <tr onclick="abrirAplicacao(<?= $a['id'] ?>)" style="cursor:pointer">
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
                <td style="color:#2d3748;font-size:.85rem;font-weight:600"><?= $a['valor'] ? 'R$ ' . number_format((float)$a['valor'], 2, ',', '.') : '—' ?></td>
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
    </div>

    <div class="panel-body">
        <div id="app-panel-loading" class="panel-loading">
            <i class="fas fa-spinner fa-spin"></i> Carregando...
        </div>

        <!-- Modo visualizar/editar -->
        <div id="app-panel-conteudo" style="display:none">
            <div class="panel-section-title">Dados da Aplicação</div>
            <div class="panel-field no-edit"><label>ID</label><span id="apf-id"></span></div>
            <div class="panel-field no-edit"><label>Slug</label><span id="apf-slug" style="font-family:monospace;color:#718096"></span></div>
            <div class="panel-field" data-app-campo="nome" onclick="editarCampoApp(this)"><label>Nome</label><span id="apf-nome"></span></div>
            <div class="panel-field" data-app-campo="descricao" data-tipo="textarea" onclick="editarCampoApp(this)"><label>Descrição</label><span id="apf-descricao"></span></div>
            <div class="panel-field" data-app-campo="valor" onclick="editarCampoApp(this)"><label>Valor sugerido (R$)</label><span id="apf-valor"></span></div>

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

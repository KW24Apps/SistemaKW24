<?php
/**
 * CADASTRO - Lista de Clientes
 */

if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../dao/ClienteDAO.php';

$clienteDAO = new ClienteDAO();
$busca      = trim($_GET['busca'] ?? '');
$clientes   = $clienteDAO->findAll($busca);
$total      = count($clientes);
?>

<link rel="stylesheet" href="/assets/css/clientes.css">

<!-- Cabeçalho da página -->
<div class="page-header">
    <h1 class="page-title"><i class="fas fa-building"></i> Clientes</h1>

    <div class="page-header-actions">
        <!-- Busca -->
        <form method="GET" action="" style="display:contents">
            <input type="hidden" name="page" value="cadastro">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text"
                       name="busca"
                       placeholder="Buscar por nome, CNPJ ou e-mail..."
                       value="<?= htmlspecialchars($busca) ?>"
                       autocomplete="off">
            </div>
        </form>

        <?php if ($user_data['perfil'] === 'admin_interno'): ?>
        <a href="?page=cadastro&action=novo" class="btn-primary">
            <i class="fas fa-plus"></i> Novo Cliente
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Tabela -->
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
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($clientes)): ?>
            <tr>
                <td colspan="8">
                    <div class="empty-state">
                        <i class="fas fa-building"></i>
                        <p><?= $busca ? "Nenhum cliente encontrado para \"" . htmlspecialchars($busca) . "\"" : "Nenhum cliente cadastrado ainda." ?></p>
                    </div>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($clientes as $c): ?>
            <tr onclick="window.location='?page=cadastro&action=ver&id=<?= $c['id'] ?>'" title="Ver detalhes">
                <td onclick="event.stopPropagation()">
                    <input type="checkbox">
                </td>
                <td style="color:#718096;font-size:0.8rem"><?= $c['id'] ?></td>
                <td>
                    <div class="cliente-info">
                        <div class="cliente-avatar">
                            <?= mb_strtoupper(mb_substr($c['nome'], 0, 2)) ?>
                        </div>
                        <a href="?page=cadastro&action=ver&id=<?= $c['id'] ?>"
                           class="cliente-nome"
                           onclick="event.stopPropagation()">
                            <?= htmlspecialchars($c['nome']) ?>
                        </a>
                    </div>
                </td>
                <td><?= htmlspecialchars($c['cnpj'] ?? '—') ?></td>
                <td><?= htmlspecialchars($c['telefone'] ?? '—') ?></td>
                <td><?= htmlspecialchars($c['email'] ?? '—') ?></td>
                <td>
                    <?php if ($c['ativo']): ?>
                        <span class="badge badge-ativo"><i class="fas fa-circle" style="font-size:0.5rem"></i> Ativo</span>
                    <?php else: ?>
                        <span class="badge badge-inativo"><i class="fas fa-circle" style="font-size:0.5rem"></i> Inativo</span>
                    <?php endif; ?>
                </td>
                <td onclick="event.stopPropagation()">
                    <div class="row-actions">
                        <a href="?page=cadastro&action=editar&id=<?= $c['id'] ?>"
                           class="btn-icon btn-icon-edit" title="Editar">
                            <i class="fas fa-pen"></i>
                        </a>
                        <a href="?page=cadastro&action=aplicacoes&id=<?= $c['id'] ?>"
                           class="btn-icon btn-icon-apps" title="Aplicações">
                            <i class="fas fa-th"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="table-footer">
        <?= $total ?> cliente<?= $total !== 1 ? 's' : '' ?> encontrado<?= $total !== 1 ? 's' : '' ?>
        <?= $busca ? " para \"" . htmlspecialchars($busca) . "\"" : '' ?>
    </div>
</div>

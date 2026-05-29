<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../helpers/Database.php';

$db     = Database::getInstance();
$busca  = trim($_GET['busca'] ?? '');

if ($busca) {
    $clientes = $db->fetchAll(
        "SELECT id, nome, cnpj, telefone, email, ativo FROM clientes
         WHERE nome ILIKE :b OR cnpj ILIKE :b OR email ILIKE :b
         ORDER BY nome ASC",
        ['b' => "%{$busca}%"]
    );
} else {
    $clientes = $db->fetchAll("SELECT id, nome, cnpj, telefone, email, ativo FROM clientes ORDER BY nome ASC");
}

$total = count($clientes);
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
                <th>Status</th>
                <th>Ações</th>
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
            <tr>
                <td><input type="checkbox"></td>
                <td style="color:#718096;font-size:.8rem"><?= $c['id'] ?></td>
                <td>
                    <div class="cliente-info">
                        <div class="cliente-avatar"><?= mb_strtoupper(mb_substr($c['nome'], 0, 2)) ?></div>
                        <a href="?page=cadastro&action=ver&id=<?= $c['id'] ?>" class="cliente-nome">
                            <?= htmlspecialchars($c['nome']) ?>
                        </a>
                    </div>
                </td>
                <td><?= htmlspecialchars($c['cnpj'] ?? '—') ?></td>
                <td><?= htmlspecialchars($c['telefone'] ?? '—') ?></td>
                <td><?= htmlspecialchars($c['email'] ?? '—') ?></td>
                <td>
                    <span class="badge <?= $c['ativo'] ? 'badge-ativo' : 'badge-inativo' ?>">
                        <i class="fas fa-circle" style="font-size:.5rem"></i>
                        <?= $c['ativo'] ? 'Ativo' : 'Inativo' ?>
                    </span>
                </td>
                <td>
                    <div class="row-actions">
                        <a href="?page=cadastro&action=editar&id=<?= $c['id'] ?>" class="btn-icon btn-icon-edit" title="Editar">
                            <i class="fas fa-pen"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    <div class="table-footer"><?= $total ?> cliente<?= $total !== 1 ? 's' : '' ?> encontrado<?= $total !== 1 ? 's' : '' ?></div>
</div>

<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Acesso.php';

try {
    $db    = Database::getInstance();
    $busca = trim($_GET['busca'] ?? '');
    // admin_cliente enxerga apenas usuários das empresas vinculadas a ele.
    $ehAdminCli = ($user_data['perfil'] ?? '') === 'admin_cliente';

    if ($ehAdminCli) {
        $sql = "SELECT DISTINCT u.id, u.nome, u.username, u.email, u.perfil, u.ativo, u.ultimo_acesso
                  FROM usuarios u
                  JOIN cliente_usuarios cu ON cu.usuario_id = u.id
                 WHERE cu.cliente_id IN (SELECT cliente_id FROM cliente_usuarios WHERE usuario_id = :admin)";
        $params = ['admin' => (int)$user_data['id']];
        if ($busca) { $sql .= " AND (u.nome ILIKE :b OR u.username ILIKE :b OR u.email ILIKE :b)"; $params['b'] = "%{$busca}%"; }
        $sql .= " ORDER BY u.nome ASC";
        $users = $db->fetchAll($sql, $params);
    } elseif ($busca) {
        $users = $db->fetchAll(
            "SELECT id, nome, username, email, perfil, ativo, ultimo_acesso FROM usuarios
             WHERE nome ILIKE :b OR username ILIKE :b OR email ILIKE :b ORDER BY nome ASC",
            ['b' => "%{$busca}%"]
        );
    } else {
        $users = $db->fetchAll("SELECT id, nome, username, email, perfil, ativo, ultimo_acesso FROM usuarios ORDER BY nome ASC");
    }
    $total = count($users);

} catch (Exception $e) {
    echo '<div style="color:#e53e3e;padding:2rem">Erro: ' . htmlspecialchars($e->getMessage()) . '</div>';
    return;
}

$perfilLabel = ['admin_interno' => 'Admin Interno', 'admin_cliente' => 'Admin Cliente', 'usuario_cliente' => 'Usuário Cliente'];
$perfilCor   = ['admin_interno' => '#0DC2FF', 'admin_cliente' => '#26FF93', 'usuario_cliente' => '#a0aec0'];
$_perfilLogado = $user_data['perfil'] ?? '';
$_podeGerenciar = in_array($_perfilLogado, ['admin_interno', 'admin_cliente'], true);
?>
<script>
  window.USR_PERFIL = <?= json_encode($_perfilLogado) ?>;
  window.USR_ID     = <?= (int)($user_data['id'] ?? 0) ?>;
</script>
<div class="page-header">
    <h1 class="page-title"><i class="fas fa-users"></i> Usuários</h1>
    <div class="page-header-actions">
        <form method="GET" style="display:contents">
            <input type="hidden" name="page" value="usuarios">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" name="busca" placeholder="Buscar por nome, usuário ou e-mail..."
                       value="<?= htmlspecialchars($busca) ?>" autocomplete="off">
            </div>
        </form>
        <?php if ($_podeGerenciar): ?>
        <button onclick="abrirNovoUsuario()" class="btn-primary">
            <i class="fas fa-plus"></i> Novo Usuário
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
                <th>Usuário</th>
                <th>Username</th>
                <th>E-mail</th>
                <th>Perfil</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($users)): ?>
            <tr><td colspan="6">
                <div class="empty-state"><i class="fas fa-users"></i><p>Nenhum usuário encontrado.</p></div>
            </td></tr>
        <?php else: ?>
            <?php foreach ($users as $u): ?>
            <tr onclick="abrirUsuario(<?= $u['id'] ?>)" style="cursor:pointer">
                <td style="color:#4a5568;font-size:.85rem"><?= $u['id'] ?></td>
                <td>
                    <div class="cliente-info">
                        <div class="cliente-avatar"><?= mb_strtoupper(mb_substr($u['nome'], 0, 2)) ?></div>
                        <span class="cliente-nome" style="color:#1a202c"
                              onclick="abrirUsuario(<?= $u['id'] ?>); event.stopPropagation()">
                            <?= htmlspecialchars($u['nome']) ?>
                        </span>
                    </div>
                </td>
                <td style="font-family:monospace;font-size:.82rem;color:#718096"><?= htmlspecialchars($u['username']) ?></td>
                <td style="color:#718096;font-size:.85rem"><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                <td>
                    <span style="font-size:.75rem;font-weight:600;color:<?= $perfilCor[$u['perfil']] ?? '#718096' ?>">
                        <?= $perfilLabel[$u['perfil']] ?? $u['perfil'] ?>
                    </span>
                </td>
                <td>
                    <span class="badge <?= $u['ativo'] ? 'badge-ativo' : 'badge-inativo' ?>">
                        <i class="fas fa-circle" style="font-size:.5rem"></i>
                        <?= $u['ativo'] ? 'Ativo' : 'Inativo' ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    </div><!-- /table-scroll -->
    <div class="table-footer"><?= $total ?> usuário<?= $total !== 1 ? 's' : '' ?> encontrado<?= $total !== 1 ? 's' : '' ?></div>
</div>

<!-- Painel lateral de usuário -->
<div id="usr-overlay" class="cliente-overlay" onclick="fecharUsuario()"></div>

<div id="usr-panel" class="cliente-panel" style="width:min(600px,calc(100vw - 160px))">
    <div class="panel-header">
        <div class="panel-avatar" id="usr-avatar">--</div>
        <div class="panel-header-info">
            <h2 class="panel-title" id="usr-panel-nome">Carregando...</h2>
            <p class="panel-subtitle" id="usr-panel-username"></p>
        </div>
        <div style="position:relative;margin-left:auto">
            <button id="btn-menu-usr" onclick="toggleMenuUsr(event)"
                style="width:36px;height:36px;border:none;background:#f0f4f8;border-radius:50%;cursor:pointer;font-size:1.1rem;color:#718096;display:flex;align-items:center;justify-content:center">
                &#8942;
            </button>
            <div id="menu-usr-dropdown" style="display:none;position:absolute;right:0;top:42px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.12);min-width:180px;z-index:100;overflow:hidden">
                <button onclick="abrirResetSenha()" style="width:100%;padding:.7rem 1rem;border:none;background:none;text-align:left;cursor:pointer;color:#2d3748;font-size:.875rem;display:flex;align-items:center;gap:.6rem"
                    onmouseover="this.style.background='#f7fafc'" onmouseout="this.style.background='none'">
                    <i class="fas fa-key" style="width:16px"></i> Redefinir senha
                </button>
                <button onclick="excluirUsuario()" style="width:100%;padding:.7rem 1rem;border:none;background:none;text-align:left;cursor:pointer;color:#c53030;font-size:.875rem;display:flex;align-items:center;gap:.6rem"
                    onmouseover="this.style.background='#fff5f5'" onmouseout="this.style.background='none'">
                    <i class="fas fa-trash" style="width:16px"></i> Excluir usuário
                </button>
            </div>
        </div>
    </div>

    <div class="panel-body">
        <div id="usr-panel-loading" class="panel-loading">
            <i class="fas fa-spinner fa-spin"></i> Carregando...
        </div>

        <div id="usr-panel-conteudo" style="display:none">
            <div class="panel-section-title">Dados do Usuário</div>
            <div class="panel-field no-edit"><label>ID</label><span id="uf-id"></span></div>
            <div class="panel-field" data-usr-campo="nome" onclick="editarCampoUsr(this)"><label>Nome</label><span id="uf-nome"></span></div>
            <div class="panel-field" data-usr-campo="username" onclick="editarCampoUsr(this)"><label>Username</label><span id="uf-username" style="font-family:monospace"></span></div>
            <div class="panel-field" data-usr-campo="email" onclick="editarCampoUsr(this)"><label>E-mail</label><span id="uf-email"></span></div>
            <div class="panel-field" data-usr-campo="cargo" onclick="editarCampoUsr(this)"><label>Cargo</label><span id="uf-cargo"></span></div>
            <div class="panel-field" data-usr-campo="telefone" onclick="editarCampoUsr(this)"><label>Telefone</label><span id="uf-telefone"></span></div>
            <div class="panel-field no-edit"><label>Criado por</label><span id="uf-criado-por">—</span></div>
            <div class="panel-divider"></div>
            <div class="panel-section-title">Acesso</div>
            <div class="panel-field no-edit">
                <label>Perfil</label>
                <select id="uf-perfil-sel" class="form-input" onchange="usrPerfilChanged()" style="margin-top:.25rem">
                    <option value="admin_interno">Admin Interno</option>
                    <option value="admin_cliente">Admin Cliente</option>
                    <option value="usuario_cliente">Usuário Cliente</option>
                </select>
            </div>
            <div class="panel-field no-edit">
                <label>Perfil de Permissão</label>
                <select id="uf-profile-sel" class="form-input" onchange="usrProfileChanged()" style="margin-top:.25rem">
                    <option value="">Sem perfil específico</option>
                </select>
            </div>
            <div class="panel-field no-edit"><label>Último Acesso</label><span id="uf-acesso"></span></div>
            <div class="panel-field no-edit"><label>Status</label><span id="uf-ativo"></span></div>
            <div class="panel-divider" style="margin-top:1rem"></div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
                <div class="panel-section-title" style="margin:0">Clientes vinculados</div>
                <button class="btn-ativar-app" onclick="abrirVincularCliente()">
                    <i class="fas fa-plus"></i> Vincular
                </button>
            </div>
            <div id="usr-clientes-lista" style="min-height:1.5rem"></div>
        </div>

        <div id="usr-panel-novo" style="display:none">
            <div class="panel-section-title">Novo Usuário</div>
            <div style="display:grid;gap:.75rem">
                <div class="panel-field no-edit"><label>Nome *</label>
                    <input type="text" id="novo-usr-nome" class="form-input" placeholder="Nome completo" required></div>
                <div class="panel-field no-edit"><label>CPF *</label>
                    <input type="text" id="novo-usr-cpf" class="form-input" placeholder="000.000.000-00" required></div>
                <div class="panel-field no-edit"><label>Username *</label>
                    <input type="text" id="novo-usr-username" class="form-input" placeholder="nome.sobrenome" required></div>
                <div class="panel-field no-edit"><label>E-mail</label>
                    <input type="email" id="novo-usr-email" class="form-input" placeholder="email@empresa.com"></div>
                <div class="panel-field no-edit"><label>Senha *</label>
                    <input type="password" id="novo-usr-senha" class="form-input" placeholder="Mínimo 6 caracteres" required></div>
                <div class="panel-field no-edit"><label>Perfil *</label>
                    <select id="novo-usr-perfil" class="form-input" required>
                        <option value="admin_interno">Admin Interno</option>
                        <option value="admin_cliente">Admin Cliente</option>
                        <option value="usuario_cliente" selected>Usuário Cliente</option>
                    </select>
                </div>
                <div class="panel-field no-edit"><label>Perfil de Permissão</label>
                    <select id="novo-usr-profile-id" class="form-input">
                        <option value="">Sem perfil específico</option>
                    </select>
                </div>
                <div class="panel-field no-edit"><label>Empresa</label>
                    <select id="novo-usr-cliente-id" class="form-input">
                        <option value="">Nenhuma</option>
                    </select>
                </div>
                <div id="novo-usr-erro" style="color:#e53e3e;font-size:.85rem;display:none"></div>
            </div>
        </div>

        <!-- Acessos a Relatórios (criar/editar) — populado por painel-usuario.js -->
        <div id="usr-acessos-wrap" style="display:none">
            <div class="panel-divider" style="margin-top:1rem"></div>
            <div class="panel-section-title">Acessos a Relatórios</div>
            <div id="usr-acessos-list" style="min-height:1.5rem"></div>
        </div>
    </div>

    <div class="panel-save-bar" id="usr-save-bar">
        <button class="btn-salvar" onclick="salvarUsuario()"><i class="fas fa-check"></i> Salvar</button>
        <button class="btn-cancelar-edit" onclick="cancelarUsuario()">Cancelar</button>
        <span id="usr-save-msg" class="save-bar-msg"></span>
    </div>
</div>

<!-- JS em assets/js/painel-usuario.js (carregado no index.php) -->

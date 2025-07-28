<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';
requireAuthentication();

$pageTitle = 'Cadastro - Sistema KW24';
$activeMenu = 'cadastro';

// Determina qual submenu mostrar
$sub = isset($_GET['sub']) ? $_GET['sub'] : 'clientes';

// Valida subpáginas permitidas
$validSubs = ['clientes', 'contatos', 'aplicacoes'];
if (!in_array($sub, $validSubs)) {
    $sub = 'clientes';
}

// Função para formatar telefone
function formatTelefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    if (strlen($telefone) === 13) {
        return sprintf('(%s) %s-%s', substr($telefone,2,2), substr($telefone,4,5), substr($telefone,9,4));
    } elseif (strlen($telefone) === 12) {
        return sprintf('(%s) %s-%s', substr($telefone,2,2), substr($telefone,4,4), substr($telefone,8,4));
    } elseif (strlen($telefone) === 11) {
        return sprintf('(%s) %s-%s', substr($telefone,0,2), substr($telefone,2,5), substr($telefone,7,4));
    } elseif (strlen($telefone) === 10) {
        return sprintf('(%s) %s-%s', substr($telefone,0,2), substr($telefone,2,4), substr($telefone,6,4));
    }
    return $telefone;
}

// Se for clientes, carrega dados
if ($sub === 'clientes') {
    require_once __DIR__ . '/../dao/DAO.php';
    $dao = new DAO();
    $clientes = $dao->getClientesCampos();
}

ob_start();
?>
<!-- Submenu na topbar -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const submenuHtml = `
        <div class="cadastro-submenu">
            <button class="cadastro-submenu-btn <?= $sub === 'clientes' ? 'active' : '' ?>" data-page="clientes">
                <i class="fas fa-users"></i> Clientes
            </button>
            <button class="cadastro-submenu-btn <?= $sub === 'contatos' ? 'active' : '' ?>" data-page="contatos">
                <i class="fas fa-address-book"></i> Contatos
            </button>
            <button class="cadastro-submenu-btn <?= $sub === 'aplicacoes' ? 'active' : '' ?>" data-page="aplicacoes">
                <i class="fas fa-cogs"></i> Aplicações
            </button>
        </div>
        <div class="cadastro-submenu-separator"></div>
    `;
    
    const submenuContainer = document.querySelector('.topbar-submenu');
    if (submenuContainer) {
        submenuContainer.innerHTML = submenuHtml;
        
        // Adiciona eventos aos botões
        document.querySelectorAll('.cadastro-submenu-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const page = this.dataset.page;
                window.location.href = '/Apps/public/cadastro.php?sub=' + page;
            });
        });
    }
});
</script>

<div class="cadastro-content">
    <?php if ($sub === 'clientes'): ?>
        <!-- CONTEÚDO CLIENTES -->
        <div class="clientes-page-wrapper">
            <div class="clientes-header">
                <h1>Clientes</h1>
                <div class="clientes-actions">
                    <input type="text" id="clientes-search" class="clientes-search" placeholder="Filtrar e pesquisar clientes..." autocomplete="off">
                </div>
            </div>

            <div id="clientes-loader" class="clientes-loader" style="display:none">
                <span class="loading-spinner"></span>
                <span class="loading-text">Atualizando...</span>
            </div>

            <div class="clientes-container">
                <div id="clientes-table-wrapper" class="clientes-table-wrapper">
                    <table id="clientes-table" class="clientes-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Empresa</th>
                                <th>CNPJ</th>
                                <th>Link Bitrix</th>
                                <th>Email</th>
                                <th>Telefone</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Os dados serão carregados via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal de detalhes -->
        <div id="cliente-detail-modal" class="cliente-detail-modal" style="display:none;">
            <div class="cliente-detail-overlay"></div>
            <div class="cliente-detail-content">
                <div id="cliente-detail-body">
                    <!-- Conteúdo via AJAX -->
                </div>
            </div>
        </div>

        <!-- Filtro avançado -->
        <div id="clientes-filter-panel" class="clientes-filter-panel" style="display:none;">
            <form id="clientes-filter-form">
                <h3>Filtro avançado</h3>
                <label for="filter-aplicacao">Aplicação</label>
                <input type="text" id="filter-aplicacao" name="aplicacao" placeholder="Aplicação">
                <label for="filter-cnpj">CNPJ</label>
                <input type="text" id="filter-cnpj" name="cnpj" placeholder="CNPJ">
                <button type="submit" class="btn-aplicar-filtro">Aplicar filtro</button>
                <button type="button" class="btn-fechar-filtro" id="btn-fechar-filtro">Fechar</button>
            </form>
        </div>

    <?php elseif ($sub === 'contatos'): ?>
        <!-- CONTEÚDO CONTATOS -->
        <div class="contatos-container">
            <h1>Contatos</h1>
            <p>Área de gerenciamento de contatos.</p>
            <!-- Aqui será implementado o conteúdo de contatos -->
        </div>

    <?php elseif ($sub === 'aplicacoes'): ?>
        <!-- CONTEÚDO APLICAÇÕES -->
        <div class="aplicacoes-container">
            <h1>Aplicações</h1>
            <p>Área de gerenciamento de aplicações.</p>
            <!-- Aqui será implementado o conteúdo de aplicações -->
        </div>

    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();

$additionalCSS = '<link rel="stylesheet" href="/Apps/assets/css/cadastro.css">';
$additionalJS  = '<script src="/Apps/assets/js/cadastro.js"></script>';

// Layout base
include __DIR__ . '/../views/layouts/main.php';

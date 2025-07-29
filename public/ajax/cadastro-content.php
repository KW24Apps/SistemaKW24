<?php
// AJAX endpoint para carregar conteúdo do cadastro
session_start();
require_once __DIR__ . '/../../includes/helpers.php';
requireAuthentication();

$sub = isset($_GET['sub']) ? $_GET['sub'] : 'clientes';

// Valida subpáginas
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
    require_once __DIR__ . '/../../dao/DAO.php';
    $dao = new DAO();
    $clientes = $dao->getClientesCampos();
}

// Retorna apenas o conteúdo (sem layout)
?>
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

    <!-- Reinicializa JavaScript dos clientes -->
    <script>
    // Recarrega funcionalidades específicas dos clientes
    if (typeof initClientesTable === 'function') {
        initClientesTable();
    }
    </script>

<?php elseif ($sub === 'contatos'): ?>
    <!-- CONTEÚDO CONTATOS -->
    <div class="contatos-page-wrapper">
        <!-- Header dos contatos -->
        <div class="contatos-header">
            <h1>Contatos</h1>
            <div class="contatos-actions">
                <button class="btn-criar-contato" onclick="abrirModalContato('criar')">
                    <i class="fas fa-plus"></i> Criar
                </button>
                <input type="text" 
                       class="contatos-search" 
                       id="contatos-search" 
                       placeholder="Buscar contatos..."
                       autocomplete="off">
            </div>
        </div>

        <!-- Container da tabela -->
        <div class="contatos-container-table">
            <div class="contatos-table-wrapper">
                <table class="contatos-table">
                    <thead>
                        <tr>
                            <th class="sortable" data-column="id">ID</th>
                            <th class="sortable" data-column="nome">Nome</th>
                            <th class="sortable" data-column="cargo">Cargo</th>
                            <th class="sortable" data-column="email">Email</th>
                            <th>Telefone</th>
                        </tr>
                    </thead>
                    <tbody id="contatos-table-body">
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #666;">
                                Carregando contatos...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Loader -->
        <div class="contatos-loader" id="contatos-loader">
            <div class="loading-spinner"></div>
            <span class="loading-text">Carregando contatos...</span>
        </div>
    </div>

<?php elseif ($sub === 'aplicacoes'): ?>
    <!-- CONTEÚDO APLICAÇÕES -->
    <div class="aplicacoes-container">
        <h1>Aplicações</h1>
        <p>Área de gerenciamento de aplicações.</p>
        <div class="aplicacoes-actions">
            <button class="btn-nova-aplicacao">Nova Aplicação</button>
        </div>
        <!-- Aqui será implementado o conteúdo de aplicações -->
    </div>

<?php endif; ?>

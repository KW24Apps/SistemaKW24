<?php
// public/clientes.php

session_start();
require_once __DIR__ . '/../includes/helpers.php';
requireAuthentication();

$pageTitle = 'Clientes - Sistema KW24';
$activeMenu = 'clientes';

// Conteúdo da página Clientes
ob_start();
?>
<!-- Header fora da área branca, similar ao Bitrix -->
<div class="clientes-header">
    <h1 id="clientes-title">Clientes</h1>
    <div class="clientes-actions">
        <input type="text" id="clientes-search" class="clientes-search" placeholder="Filtrar e pesquisar clientes..." autocomplete="off">
        <button id="clientes-filter-btn" class="clientes-filter-btn" title="Filtro avançado"><i class="fas fa-filter"></i></button>
        <button id="btn-refresh-clientes" class="btn-refresh-clientes" title="Atualizar lista"><i class="fas fa-sync-alt"></i></button>
    </div>
</div>

<div id="clientes-loader" class="clientes-loader" style="display:none">
    <span class="loading-spinner"></span>
    <span class="loading-text">Atualizando...</span>
</div>

<!-- Container branco apenas com a tabela -->
<div class="clientes-container">
    <div id="clientes-table-wrapper" class="clientes-table-wrapper">
        <table id="clientes-table" class="clientes-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Empresa</th>
                    <th>CNPJ</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <!-- Linhas de clientes serão carregadas via AJAX -->
            </tbody>
        </table>
    </div>
</div>

    <!-- Modal/Painel de detalhes do cliente -->
    <div id="cliente-detail-modal" class="cliente-detail-modal" style="display:none;">
        <div class="cliente-detail-overlay"></div>
        <div class="cliente-detail-content">
            <button class="cliente-detail-close" id="cliente-detail-close" title="Fechar"><i class="fas fa-times"></i></button>
            <div id="cliente-detail-body">
                <!-- Conteúdo do cliente será carregado via AJAX -->
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

<?php
$content = ob_get_clean();

$additionalCSS = '<link rel="stylesheet" href="/Apps/assets/css/clientes.css">';
$additionalJS  = '<script src="/Apps/assets/js/clientes.js"></script>';

// Layout base (sidebar, etc)
include __DIR__ . '/../views/layouts/main.php';

<?php
// Conteúdo dinâmico para cadastro (Clientes, Contatos ou Aplicações)
$page = isset($_GET['sub']) ? $_GET['sub'] : 'clientes';
?>
<script>
// Garante que o submenu de cadastro sempre aparece ao trocar de aba
fetch('/Apps/public/partials/cadastro-submenu.php<?php echo "?sub=" . $page; ?>')
    .then(response => response.text())
    .then(html => {
        var submenu = document.querySelector('.topbar-submenu');
        if (submenu) submenu.innerHTML = html;
    });
</script>
<div class="cadastro-content-centralizado">
    <?php if ($page === 'clientes'): ?>
        <h2>Clientes</h2>
        
        <!-- Aqui você pode mover o conteúdo atual da página clientes.php -->
        <div class="clientes-container">
            <div id="clientes-date" class="clientes-date" aria-live="polite"><?php echo date('Y-m-d H:i:s'); ?></div>
            <button id="btn-refresh-clientes">Atualizar Clientes</button>
            <div id="clientes-loader" class="clientes-loader" style="display:none">
                <span class="loading-spinner"></span>
                <span class="loading-text">Atualizando...</span>
            </div>
            
            <!-- Aqui seria a tabela/lista de clientes -->
            <div class="clientes-list">
                <p style="text-align:center;">Lista de clientes será exibida aqui.</p>
            </div>
        </div>
        
    <?php elseif ($page === 'contatos'): ?>
        <h2>Contatos</h2>
        <div class="contatos-container">
            <p style="text-align:center;">Você está na subpágina <strong>Contatos</strong>.</p>
            <!-- Aqui será o formulário/lista de contatos -->
        </div>
        
    <?php elseif ($page === 'aplicacoes'): ?>
        <h2>Aplicações</h2>
        <div class="aplicacoes-container">
            <div id="aplicacoes-date" class="aplicacoes-date" aria-live="polite"><?php echo date('Y-m-d H:i:s'); ?></div>
            <button id="btn-refresh-aplicacoes">Atualizar Aplicações</button>
            <div id="aplicacoes-loader" class="aplicacoes-loader" style="display:none">
                <span class="loading-spinner"></span>
                <span class="loading-text">Atualizando...</span>
            </div>
            <!-- Aqui seria o conteúdo de aplicações -->
        </div>
        
    <?php else: ?>
        <h2>Página não encontrada</h2>
        <p style="text-align:center;">A subpágina solicitada não existe.</p>
    <?php endif; ?>
</div>

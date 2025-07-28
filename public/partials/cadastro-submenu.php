<?php
$sub = isset($_GET['sub']) ? $_GET['sub'] : 'clientes';
?>
<div class="cadastro-submenu">
    <button class="cadastro-submenu-btn<?php echo ($sub === 'clientes') ? ' active' : ''; ?>" data-page="clientes">
        <i class="fas fa-users"></i> Clientes
    </button>
    <button class="cadastro-submenu-btn<?php echo ($sub === 'contatos') ? ' active' : ''; ?>" data-page="contatos">
        <i class="fas fa-address-book"></i> Contatos
    </button>
    <button class="cadastro-submenu-btn<?php echo ($sub === 'aplicacoes') ? ' active' : ''; ?>" data-page="aplicacoes">
        <i class="fas fa-cogs"></i> Aplicações
    </button>
</div>
<div class="cadastro-submenu-separator"></div>

<script>
// JavaScript para navegação dos submenus
document.querySelectorAll('.cadastro-submenu-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const page = this.dataset.page;
        window.location.href = '/Apps/public/cadastro.php?sub=' + page;
    });
});
</script>

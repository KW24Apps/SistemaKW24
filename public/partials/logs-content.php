<?php
// Conteúdo dinâmico para logs (Filtro ou Download)
$page = isset($_GET['sub']) ? $_GET['sub'] : 'filtro';
?>
<script>
// Garante que o submenu de logs sempre aparece ao trocar de aba
fetch('/Apps/public/partials/logs-submenu.php')
    .then(response => response.text())
    .then(html => {
        var submenu = document.querySelector('.topbar-submenu');
        if (submenu) submenu.innerHTML = html;
    });
</script>
<div class="logs-content-centralizado">
    <?php if ($page === 'download'): ?>
        <h2>Download</h2>
        <p style="text-align:center;">Você está na subpágina <strong>Download</strong>.</p>
    <?php else: ?>
        <h2>Filtro</h2>
        <p style="text-align:center;">Você está na subpágina <strong>Filtro</strong>.</p>
    <?php endif; ?>
</div>

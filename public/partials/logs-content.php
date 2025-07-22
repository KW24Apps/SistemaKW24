<?php
// Conteúdo dinâmico para logs (Filtro ou Download)
$page = $_GET['sub'] ?? 'filtro';
?>
<div class="logs-content-centralizado">
    <?php if ($page === 'download'): ?>
        <h2>Download</h2>
        <p style="text-align:center;">Você está na subpágina <strong>Download</strong>.</p>
    <?php else: ?>
        <h2>Filtro</h2>
        <p style="text-align:center;">Você está na subpágina <strong>Filtro</strong>.</p>
    <?php endif; ?>
</div>

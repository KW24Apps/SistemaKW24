<?php
$sub = isset($_GET['sub']) ? $_GET['sub'] : 'filtro';
?>
<div class="logs-submenu">
    <button class="logs-submenu-btn<?php echo ($sub === 'filtro') ? ' active' : ''; ?>" data-page="filtro">Filtro</button>
    <button class="logs-submenu-btn<?php echo ($sub === 'download') ? ' active' : ''; ?>" data-page="download">Download</button>
</div>
<div class="logs-submenu-separator"></div>

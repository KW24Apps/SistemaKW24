<?php
// main.php

// Carrega o CSS do FontAwesome e da sidebar
echo '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="/Apps/assets/css/sidebar.css">
';

// Inclui a barra lateral (sidebar/cyberbar)
include __DIR__ . '/sidebar.php';

// Exibe o conteúdo principal da página (dashboard, clientes, etc)
echo isset($content) ? $content : '';
?>

<!-- JS: Sidebar (deixe sempre no final) -->
<script src="/Apps/assets/js/sidebar.js"></script>

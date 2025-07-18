<?php
// Carrega o CSS do FontAwesome e da sidebar
echo '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="/Apps/assets/css/sidebar.css">
';

// Script para aplicar o estado do sidebar antes do HTML aparecer
echo '<script>
  try {
    var state = localStorage.getItem("sidebarState");
    if (state === "collapsed") {
      document.addEventListener("DOMContentLoaded", function() {
        var sidebar = document.getElementById("sidebar");
        if (sidebar) sidebar.classList.add("collapsed");
      });
    }
  } catch(e){}
</script>';

// Inclui a barra lateral (sidebar/cyberbar)
include __DIR__ . '/sidebar.php';

// Exibe o conteúdo principal da página (dashboard, clientes, etc)
echo isset($content) ? $content : '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>KW24</title>
    <link rel="stylesheet" href="/Apps/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preload" as="image" href="/Apps/assets/img/Fundo_Login.webp">
</head>
<!-- JS: Sidebar (deixe sempre no final) -->
<script src="/Apps/assets/js/sidebar.js"></script>
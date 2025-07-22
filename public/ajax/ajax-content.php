<?php
// AJAX genérico para carregar só o conteúdo principal de cada tela
$page = $_GET['page'] ?? 'home';

switch ($page) {
    case 'logs':
        include __DIR__ . '/../partials/logs-content.php';
        break;
    case 'cadastro':
        include __DIR__ . '/../partials/cadastro-content.php';
        break;
    // Adicione outros cases conforme necessário
    default:
        echo '<p>Página não encontrada.</p>';
}

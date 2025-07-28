<?php
session_start();

// Se não estiver logado, redireciona para login, mantendo parâmetro page se existir
if (!isset($_SESSION['logviewer_auth']) || $_SESSION['logviewer_auth'] !== true) {
    $page = isset($_GET['page']) ? $_GET['page'] : '';
    $loginUrl = 'login.php';
    if ($page) {
        $loginUrl .= '?page=' . urlencode($page);
    }
    header('Location: ' . $loginUrl);
    exit;
}

// Descobre qual página mostrar, padrão é dashboard
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Protege para só permitir páginas válidas
$validPages = ['dashboard', 'cadastro', 'aplicacoes', 'logs'];
if (!in_array($page, $validPages)) {
    $page = 'dashboard';
}

// Inclui a página certa
include __DIR__ . '/' . $page . '.php';

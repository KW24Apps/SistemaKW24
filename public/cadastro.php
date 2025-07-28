<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';
requireAuthentication();

$pageTitle = 'Cadastro - Sistema KW24';
$activeMenu = 'cadastro';

// Redireciona sempre para Clientes ao acessar cadastro pelo menu lateral
if (!isset($_GET['sub'])) {
    header('Location: /Apps/public/cadastro.php?sub=clientes');
    exit;
}

// Conteúdo da página Cadastro
ob_start();
include __DIR__ . '/partials/cadastro-content.php';
$content = ob_get_clean();

$additionalCSS = '<link rel="stylesheet" href="/Apps/assets/css/cadastro.css">';
$additionalJS  = '<script src="/Apps/assets/js/cadastro.js"></script>';

// Layout base (sidebar, etc)
include __DIR__ . '/../views/layouts/main.php';

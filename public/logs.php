<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';
requireAuthentication();

$pageTitle = 'Logs - Sistema KW24';
$activeMenu = 'logs';

// Redireciona sempre para Filtro ao acessar logs pelo menu lateral
if (!isset($_GET['sub'])) {
    header('Location: /Apps/public/logs.php?sub=filtro');
    exit;
}

// Conteúdo da página Logs
ob_start();
include __DIR__ . '/partials/logs-content.php';
$content = ob_get_clean();

$additionalCSS = '<link rel="stylesheet" href="/Apps/assets/css/logs.css">';
$additionalJS  = '<script src="/Apps/assets/js/logs.js"></script>';

// Layout base (sidebar, etc)
include __DIR__ . '/../views/layouts/main.php';

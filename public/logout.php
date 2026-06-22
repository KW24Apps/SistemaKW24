<?php
/**
 * LOGOUT V2 - KW24 APPS
 * Sistema de logout com novo AuthenticationService
 */

session_start();

// Importa serviço de autenticação
require_once __DIR__ . '/../services/AuthenticationService.php';

$authService = new AuthenticationService();

// Log da ação de logout (para auditoria futura)
if (isset($_SESSION['user_name'])) {
    $user = $_SESSION['user_name'];
    $timestamp = date('Y-m-d H:i:s');
    error_log("Logout realizado - Usuário: {$user} - {$timestamp}");
}

// Destrói sessão de forma segura
$authService->destroySession();

// Headers de segurança para evitar cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Redireciona para login
header('Location: login.php?logout=success');
exit;

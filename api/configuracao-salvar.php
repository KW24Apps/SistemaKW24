<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../dao/ConfiguracaoDAO.php';

header('Content-Type: application/json');

$auth = new AuthenticationService();
if (!$auth->validateSession()) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autenticado']);
    exit;
}

$user = $auth->getCurrentUser();
if (!$user || $user['perfil'] !== 'admin_interno') {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$chave = trim($body['chave'] ?? '');
$valor = trim($body['valor'] ?? '');

$chaves_permitidas = ['financeiro_dia_inicio', 'financeiro_webhook_bitrix'];

if (!in_array($chave, $chaves_permitidas, true)) {
    echo json_encode(['erro' => 'Chave inválida']);
    exit;
}

if ($chave === 'financeiro_dia_inicio') {
    $dia = (int)$valor;
    if ($dia < 1 || $dia > 28) {
        echo json_encode(['erro' => 'Dia de início deve ser entre 1 e 28']);
        exit;
    }
    $valor = (string)$dia;
}

if ($chave === 'financeiro_webhook_bitrix' && $valor !== '') {
    if (strpos($valor, 'https://') !== 0) {
        echo json_encode(['erro' => 'Webhook deve começar com https://']);
        exit;
    }
}

try {
    $dao = new ConfiguracaoDAO();
    $dao->set($chave, $valor);
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}

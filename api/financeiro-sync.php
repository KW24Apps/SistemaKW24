<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../dao/ConfiguracaoDAO.php';
require_once __DIR__ . '/../services/BitrixService.php';
require_once __DIR__ . '/../services/FinanceiroSync.php';

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

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$period = isset($body['period']) ? trim($body['period']) : null;

// Aceita também via GET para cron (sem sessão via CLI — ver crons/financeiro-sync.php)
if (!$period && isset($_GET['period'])) {
    $period = trim($_GET['period']);
}

if ($period !== null && !preg_match('/^\d{4}-\d{2}$/', $period)) {
    echo json_encode(['erro' => "Formato de período inválido (esperado: YYYY-MM)"]);
    exit;
}

try {
    $sync     = new FinanceiroSync();
    $demandas = $sync->run($period ?: null);
    $infra    = $sync->syncInfra($period ?: null);
    echo json_encode(['sucesso' => true, 'demandas' => $demandas, 'infra' => $infra]);
} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}

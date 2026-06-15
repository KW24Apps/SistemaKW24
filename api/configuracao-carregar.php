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

try {
    $dao = new ConfiguracaoDAO();
    echo json_encode(['sucesso' => true, 'dados' => $dao->getAll()]);
} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}

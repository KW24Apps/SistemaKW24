<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../dao/ConfiguracaoDAO.php';

header('Content-Type: application/json');

$auth = new AuthenticationService();
if (!$auth->validateSession()) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autenticado']);
    exit;
}
$user = $auth->getCurrentUser();
if (($user['perfil'] ?? '') !== 'admin_interno') {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

try {
    $dao     = new ConfiguracaoDAO();
    $webhook = rtrim($dao->get('financeiro_webhook_bitrix') ?? '', '/');

    if (strlen($webhook) < 15) {
        echo json_encode(['empresas' => []]);
        exit;
    }

    $empresas = [];
    $start    = 0;

    do {
        $url  = $webhook . '/crm.company.list.json?start=' . $start . '&select[]=ID&select[]=TITLE&order[TITLE]=ASC';
        $ch   = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($resp, true);
        if (!isset($data['result'])) break;

        foreach ($data['result'] as $row) {
            $empresas[] = ['id' => (int)$row['ID'], 'name' => $row['TITLE']];
        }

        $start = $data['next'] ?? null;
    } while ($start !== null);

    usort($empresas, fn($a, $b) => strcmp($a['name'], $b['name']));
    echo json_encode(['empresas' => $empresas]);

} catch (Throwable $e) {
    error_log('[portais-empresas] ' . $e->getMessage());
    echo json_encode(['empresas' => []]);
}

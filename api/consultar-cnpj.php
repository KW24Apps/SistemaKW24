<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';

header('Content-Type: application/json');

$auth = new AuthenticationService();
if (!$auth->validateSession()) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autenticado']);
    exit;
}

$cnpj = preg_replace('/\D/', '', $_GET['cnpj'] ?? '');

if (strlen($cnpj) !== 14) {
    echo json_encode(['erro' => 'CNPJ inválido (deve ter 14 dígitos)']);
    exit;
}

$url = 'https://minhareceita.org/' . $cnpj;

$ctx = stream_context_create([
    'http' => [
        'timeout' => 10,
        'header'  => "Accept: application/json\r\nUser-Agent: KW24-App/1.0\r\n",
    ],
]);

$raw = @file_get_contents($url, false, $ctx);

if ($raw === false) {
    echo json_encode(['erro' => 'CNPJ não encontrado ou serviço temporariamente indisponível']);
    exit;
}

$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode(['erro' => 'Resposta inválida da Receita Federal']);
    exit;
}

// API retorna campo 'status' com 'ERROR' em caso de erro
if (isset($data['status']) && strtoupper($data['status']) === 'ERROR') {
    $msg = $data['message'] ?? ($data['titulo'] ?? 'CNPJ não encontrado');
    echo json_encode(['erro' => $msg]);
    exit;
}

// Monta endereço completo
$tipo_log   = trim($data['descricao_tipo_de_logradouro'] ?? '');
$logradouro = trim($data['logradouro'] ?? '');
$numero     = trim($data['numero'] ?? '');
$complemento = trim($data['complemento'] ?? '');
$bairro     = trim($data['bairro'] ?? '');
$municipio  = trim($data['municipio'] ?? '');
$uf         = trim($data['uf'] ?? '');
$cep        = trim($data['cep'] ?? '');

$partes = [];

$log = trim($tipo_log . ' ' . $logradouro);
if ($log !== '') $partes[] = $log;

if ($numero !== '' && $complemento !== '') {
    $partes[] = $numero . ' - ' . $complemento;
} elseif ($numero !== '') {
    $partes[] = $numero;
} elseif ($complemento !== '') {
    $partes[] = $complemento;
}

if ($bairro !== '') $partes[] = $bairro;

if ($municipio !== '' && $uf !== '') {
    $partes[] = $municipio . ' - ' . $uf;
} elseif ($municipio !== '') {
    $partes[] = $municipio;
}

if ($cep !== '') $partes[] = strlen($cep) === 8 ? substr($cep, 0, 5) . '-' . substr($cep, 5) : $cep;

$endereco = implode(', ', $partes);

echo json_encode([
    'razao_social' => $data['razao_social'] ?? '',
    'endereco'     => $endereco,
]);

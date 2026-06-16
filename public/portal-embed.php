<?php
/**
 * Portal Embed — sem login, autenticado por token na URL
 * Chamado pelo portal-router.php com $embed_token definido
 */
if (!defined('PORTAL_ACCESS')) { http_response_code(403); exit; }

require_once __DIR__ . '/../helpers/Database.php';

// Permitir embedding em iframes externos
header('X-Frame-Options: ALLOWALL');
header('Content-Security-Policy: frame-ancestors *');

$token = $embed_token ?? '';
if (!$token || !preg_match('/^[0-9a-f]{64}$/', $token)) {
    http_response_code(403);
    echo '<p style="font-family:sans-serif;color:#888;padding:2rem">Embed não disponível.</p>';
    exit;
}

$pdo  = Database::getInstance()->getConnection();
$stmt = $pdo->prepare('SELECT * FROM portais_cliente WHERE embed_token = ? AND ativo = true');
$stmt->execute([$token]);
$portal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$portal) {
    http_response_code(403);
    echo '<p style="font-family:sans-serif;color:#888;padding:2rem">Embed não disponível.</p>';
    exit;
}

$portalCompanyId   = (int)$portal['company_id'];
$portalCompanyName = (string)$portal['company_name'];
$isPortalMode      = true;

define('SYSTEM_ACCESS', true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($portalCompanyName) ?> — Relatório</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        background: linear-gradient(150deg, #0d2f3f 0%, #0a2233 50%, #061920 100%);
        min-height: 100vh;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        color: #fff;
        padding: 1.5rem 2rem;
    }
    #kw24-bg { position: fixed; inset: 0; z-index: -1; pointer-events: none; }
    </style>
</head>
<body>
<canvas id="kw24-bg"></canvas>
<?php
include __DIR__ . '/financeiro-relatorios.php';
?>
<script src="/assets/js/bg-dashboard.js"></script>
</body>
</html>

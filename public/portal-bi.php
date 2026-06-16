<?php
/**
 * Portal BI — exibe o relatório financeiro para um portal autenticado
 * Chamado pelo portal-router.php com $portal_slug definido
 */
if (!defined('PORTAL_ACCESS')) { http_response_code(403); exit; }

// Auth check
if (
    empty($_SESSION['portal_company_id']) ||
    empty($_SESSION['portal_slug']) ||
    $_SESSION['portal_slug'] !== ($portal_slug ?? '')
) {
    header('Location: /portal/' . ($portal_slug ?? ''));
    exit;
}

$portalCompanyId   = (int)$_SESSION['portal_company_id'];
$portalCompanyName = (string)$_SESSION['portal_company_name'];
$isPortalMode      = true;

// Necessário para o guard em financeiro-relatorios.php
define('SYSTEM_ACCESS', true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($portalCompanyName) ?> — Relatório Financeiro | KW24</title>
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
    }
    .portal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: .6rem 2rem;
        background: rgba(13,194,255,0.04);
        border-bottom: 1px solid rgba(13,194,255,0.10);
        position: sticky;
        top: 0;
        z-index: 100;
        backdrop-filter: blur(6px);
    }
    .portal-header-logo img { height: 26px; filter: drop-shadow(0 2px 4px rgba(0,0,0,.3)); }
    .portal-company-badge {
        font-size: .72rem; font-weight: 700;
        color: rgba(255,255,255,.75);
        background: rgba(255,255,255,0.07);
        padding: .3rem .9rem; border-radius: 20px;
        border: 1px solid rgba(255,255,255,0.1);
    }
    .portal-logout {
        font-size: .72rem; color: rgba(255,255,255,.35);
        text-decoration: none; display: flex; align-items: center; gap: .35rem;
        transition: color .15s;
    }
    .portal-logout:hover { color: rgba(255,255,255,.8); }
    .portal-content { padding: 1.5rem 2rem; }
    </style>
</head>
<body>
<canvas id="kw24-bg"></canvas>

<header class="portal-header">
    <div class="portal-header-logo">
        <img src="/assets/img/03_KW24_BRANCO1.png" alt="KW24">
    </div>
    <span class="portal-company-badge">
        <i class="fas fa-building" style="margin-right:.35rem;font-size:.65rem;opacity:.6"></i>
        <?= htmlspecialchars($portalCompanyName) ?>
    </span>
    <a href="/portal/<?= htmlspecialchars($portal_slug) ?>/sair" class="portal-logout">
        <i class="fas fa-sign-out-alt"></i> Sair
    </a>
</header>

<div class="portal-content">
<?php
// $isPortalMode e $portalCompanyId já estão definidos acima
// financeiro-relatorios.php lê essas variáveis para ajustar a UI e as chamadas de API
include __DIR__ . '/financeiro-relatorios.php';
?>
</div>

<script src="/assets/js/bg-dashboard.js"></script>
</body>
</html>

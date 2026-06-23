<?php
/**
 * Portal Router
 * Chamado pelo nginx: try_files $uri $uri/ /portal-router.php?$query_string
 * Interpreta a URI e inclui a página correta — nunca expõe caminhos internos em erros.
 */

// Portal BI embed runs inside a cross-site iframe — SameSite=None required so
// the session cookie survives the redirect from /portal/... to /relatorios-bi/...
if (isset($_GET['embed'])) {
    $embedPath = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    if (preg_match('#^/portal/[a-z0-9-]+/[a-z0-9-]+$#', $embedPath)) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'None',
        ]);
    }
}
session_start();

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = rtrim($uri, '/');

define('PORTAL_ACCESS', true);

// /portal/embed/{64-char hex token}
if (preg_match('#^/portal/embed/([0-9a-f]{64})$#', $uri, $m)) {
    $embed_token = $m[1];
    require __DIR__ . '/public/portal-embed.php';
    exit;
}

// /portal/{slug}/sair
if (preg_match('#^/portal/([a-z0-9\-]+)/sair$#', $uri, $m)) {
    $slug = $m[1];
    unset(
        $_SESSION['portal_slug'],
        $_SESSION['portal_company_id'],
        $_SESSION['portal_company_name'],
        $_SESSION['portal_mode']
    );
    header('Location: /portal/' . $slug);
    exit;
}

// /portal/{slug}/bi
if (preg_match('#^/portal/([a-z0-9\-]+)/bi$#', $uri, $m)) {
    $portal_slug = $m[1];
    require __DIR__ . '/public/portal-bi.php';
    exit;
}

// /portal/{relatorio_slug}/{portal_slug}  — BI portals (must come before /portal/{slug})
if (preg_match('#^/portal/([a-z0-9\-]+)/([a-z0-9\-]+)$#', $uri, $m)) {
    $relatorio_slug = $m[1];
    $portal_slug    = $m[2];
    require __DIR__ . '/public/portal-bi-acesso.php';
    exit;
}

// /portal/{slug}
if (preg_match('#^/portal/([a-z0-9\-]+)$#', $uri, $m)) {
    $portal_slug = $m[1];
    require __DIR__ . '/public/portal-login.php';
    exit;
}

http_response_code(404);
echo 'Página não encontrada.';

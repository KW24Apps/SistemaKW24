<?php
// Used exclusively by nginx auth_request for /relatorios-bi/* routes.
// Checks normal user session OR portal_bi session (for external portal access).
// On portal session: injects X-Portal-Filter-Type/Values headers for nginx proxy.
session_start();

// ── Portal BI session (external portal access) ─────────────────────────────
if (isset($_SESSION['portal_bi'])) {
    $pb = $_SESSION['portal_bi'];

    // Validate that this portal session is for the requested relatorio_slug
    $requestUri = $_SERVER['ORIGINAL_URI'] ?? '';
    if ($requestUri) {
        preg_match('#^/relatorios-bi/([a-z0-9\-]+)/#', $requestUri, $m);
        $requestedSlug = $m[1] ?? '';
        if ($requestedSlug && $requestedSlug !== ($pb['relatorio_slug'] ?? '')) {
            http_response_code(401);
            exit;
        }
    }

    // Check session expiry (0 = no expiry for embed sessions)
    $expires = $pb['expires'] ?? 0;
    if ($expires > 0 && time() > $expires) {
        unset($_SESSION['portal_bi']);
        http_response_code(401);
        exit;
    }

    // Valid portal session — return filter headers for nginx to inject
    $filterType   = $pb['filter_type']   ?? '';
    $filterValues = implode(',', $pb['filter_values'] ?? []);
    $portalName   = $pb['nome']          ?? '';
    header('X-Portal-Filter-Type: '   . $filterType);
    header('X-Portal-Filter-Values: ' . $filterValues);
    header('X-Portal-Name: '          . rawurlencode($portalName));
    http_response_code(200);
    exit;
}

// ── Normal authenticated user session ─────────────────────────────────────
if (empty($_SESSION['user_authenticated'])) {
    http_response_code(401);
    exit;
}

$config          = require __DIR__ . '/../config/config.php';
$sessionLifetime = $config['security']['session_lifetime'] ?? 3600;
$lastActivity    = $_SESSION['last_activity'] ?? 0;

if ($sessionLifetime > 0 && (time() - $lastActivity) > $sessionLifetime) {
    http_response_code(401);
    exit;
}

// No filter headers for internal users — they see all data
http_response_code(200);

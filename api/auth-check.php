<?php
// Used exclusively by nginx auth_request for /relatorios-bi/* routes.
// Replicates AuthenticationService::validateSession() logic without DB overhead.
session_start();

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

http_response_code(200);

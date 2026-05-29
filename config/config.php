<?php
/**
 * CONFIGURAÇÃO GERAL - KW24 APPS V2
 */

// ===== AMBIENTE =====
// 'teste' | 'producao'
$env = 'producao';

$configs = [
    'teste' => [
        'driver'   => 'pgsql',
        'host'     => '127.0.0.1',
        'port'     => '5432',
        'dbname'   => 'kwconfig',
        'username' => 'postgres',
        'password' => '12Qwaszx!@',
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ] 
    ],
    'producao' => [
        'driver'   => 'pgsql',
        'host'     => '127.0.0.1',
        'port'     => '5432',
        'dbname'   => 'kwconfig',
        'username' => 'postgres',
        'password' => '159Qwaszx753!@*',
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    ],
];

$dbConfig = $configs[$env];

return [
    'database' => $dbConfig,
    'security' => [
        'password_algorithm' => PASSWORD_DEFAULT, // Usa o algoritmo padrão mais seguro disponível
        'session_lifetime' => 3600, // 1 hora
        'max_login_attempts' => 5,
        'csrf_token_name' => 'kw24_csrf_token'
    ]
];

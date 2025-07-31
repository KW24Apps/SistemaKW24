<?php
/**
 * CONFIGURAÇÃO GERAL - KW24 APPS V2
 * Sistema de autenticação - AMBIENTE DE PRODUÇÃO
 */

$dbConfig = [
    'host' => 'localhost',
    'dbname' => 'kw24co49_api_kwconfig',
    'username' => 'kw24co49_kw24',
    'password' => 'BlFOyf%X}#jXwrR-vi',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]
];

return [
    'database' => $dbConfig,
    'security' => [
        'password_algorithm' => PASSWORD_DEFAULT, // Usa o algoritmo padrão mais seguro disponível
        'session_lifetime' => 3600, // 1 hora
        'max_login_attempts' => 5,
        'csrf_token_name' => 'kw24_csrf_token'
    ]
];

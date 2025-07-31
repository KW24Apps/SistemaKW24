<?php
/**
 * CONFIGURAÇÃO DO BANCO DE DADOS - KW24 APPS V2
 * Forçando configuração de produção que funciona
 */

// USANDO SEMPRE A CONFIGURAÇÃO QUE FUNCIONA
$config = [
    'database' => [
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
    ],
    'security' => [
        'password_algorithm' => PASSWORD_ARGON2ID,
        'session_lifetime' => 3600, // 1 hora
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutos
        'csrf_token_name' => 'kw24_csrf_token'
    ],
    'logging' => [
        'enabled' => true,
        'level' => 'DEBUG',
        'file' => '../logs/app.log'
    ]
];

return $config;

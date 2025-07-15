<?php
/**
 * Configuração de diretórios de logs para diferentes domínios/subdomínios
 * 
 * Este arquivo define os caminhos para os logs de diferentes domínios/subdomínios
 * que o sistema pode visualizar.
 */

return [
    // Domínio principal
    'apis.kw24.com.br' => [
        'name' => 'APIs KW24',
        'path' => __DIR__ . '/../logs/', // Caminho relativo à pasta config
        'description' => 'Logs do sistema principal de APIs'
    ],
    
    // Exemplos de outros subdomínios
    'app.kw24.com.br' => [
        'name' => 'App KW24',
        'path' => '/home/kw24co49/app.kw24.com.br/logs/', // Use caminhos absolutos para outros domínios
        'description' => 'Logs do painel administrativo'
    ],
    
    // Adicione mais subdomínios conforme necessário
    // 'cliente.kw24.com.br' => [
    //     'name' => 'Portal Cliente',
    //     'path' => '/home/kw24co49/cliente.kw24.com.br/logs/',
    //     'description' => 'Logs do portal de clientes'
    // ],
];

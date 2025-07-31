<?php
/**
 * TEMPLATE PARA NOVAS PÁGINAS
 * Copie este arquivo e adapte para criar novas páginas do sistema
 * 
 * INSTRUÇÕES:
 * 1. Copie este arquivo para public/[nome_da_pagina].php
 * 2. Altere o título e conteúdo específico
 * 3. Adicione a página no array $allowed_pages do index.php
 * 4. Crie link no sidebar apontando para ?page=[nome_da_pagina]
 */

// Verificação de segurança - este arquivo só pode ser incluído
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /Apps/public/login.php');
    exit;
}
?>

<h1>[NOME DA PÁGINA] - KW24 Sistema</h1>

<div class="welcome-section">
    <p><strong>Usuário:</strong> <?php echo htmlspecialchars($user_data['nome']); ?></p>
    <p><strong>Seção:</strong> [Nome da Seção]</p>
</div>

<div class="page-content">
    <h2>[Título da Página]</h2>
    <p>[Descrição da funcionalidade da página]</p>
    
    <!-- Conteúdo específico da página aqui -->
    <div class="page-actions">
        <h3>Funcionalidades</h3>
        <p>Implementar conforme necessidades específicas da página.</p>
    </div>
</div>

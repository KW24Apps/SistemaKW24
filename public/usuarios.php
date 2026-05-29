<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php');
    exit;
}
?>
<link rel="stylesheet" href="/assets/css/clientes.css">

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-users"></i> Usuários</h1>
    <?php if ($user_data['perfil'] === 'admin_interno'): ?>
    <div class="page-header-actions">
        <a href="?page=usuarios&action=novo" class="btn-primary">
            <i class="fas fa-plus"></i> Novo Usuário
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="table-panel">
    <p style="padding:2rem;color:#718096;text-align:center">
        <i class="fas fa-users" style="font-size:2rem;display:block;margin-bottom:.5rem;color:#cbd5e0"></i>
        Gestão de usuários em construção.
    </p>
</div>

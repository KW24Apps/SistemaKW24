<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php'); exit;
}
?>

<style>
.finrel-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 40vh;
    text-align: center;
    color: rgba(255,255,255,.35);
}
.finrel-placeholder i {
    font-size: 3rem;
    margin-bottom: 1.25rem;
    color: rgba(13,194,255,.3);
}
.finrel-placeholder h2 {
    font-size: 1.1rem;
    font-weight: 600;
    color: rgba(255,255,255,.5);
    margin-bottom: .5rem;
}
.finrel-placeholder p {
    font-size: .875rem;
    line-height: 1.6;
    max-width: 360px;
}
</style>

<div class="finrel-placeholder">
    <i class="fas fa-file-invoice-dollar"></i>
    <h2>Relatórios Financeiros</h2>
    <p>Esta seção está em construção. Em breve estará disponível.</p>
</div>

<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php'); exit;
}
?>
<link rel="stylesheet" href="/assets/css/base-conhecimento.css">

<!-- ── Landing panel (AJAX / sistema interno) ────────────────────────────── -->
<div class="bc-wrap">

    <div class="bc-page-header">
        <i class="fas fa-book-open bc-page-icon"></i>
        <span class="bc-page-title">Base de Conhecimento</span>
    </div>

    <div class="bc-section">
        <div class="bc-section-label">Empresas</div>
        <div class="bc-cards-grid">

            <a href="?page=base-conhecimento&empresa=nimbus-tax" class="bc-card">
                <i class="fas fa-file-invoice-dollar bc-card-icon"></i>
                <span class="bc-card-title">Nimbus TAX</span>
            </a>

            <a href="?page=base-conhecimento&empresa=grupo-nimbus" class="bc-card">
                <i class="fas fa-layer-group bc-card-icon"></i>
                <span class="bc-card-title">Grupo Nimbus</span>
            </a>

            <a href="?page=base-conhecimento&empresa=altura-assessoria" class="bc-card">
                <i class="fas fa-chart-line bc-card-icon"></i>
                <span class="bc-card-title">Altura Assessoria</span>
            </a>

            <a href="?page=base-conhecimento&empresa=kw24" class="bc-card">
                <i class="fas fa-server bc-card-icon"></i>
                <span class="bc-card-title">KW24</span>
            </a>

            <a href="?page=base-conhecimento&empresa=contabilidade" class="bc-card">
                <i class="fas fa-calculator bc-card-icon"></i>
                <span class="bc-card-title">Contabilidade</span>
                <span class="bc-card-subtitle">Capiton &middot; ContaFarma &middot; FF Contabilidade</span>
            </a>

        </div>
    </div>

    <div class="bc-section">
        <div class="bc-section-label">Comunicação e Processos</div>
        <div class="bc-cards-grid">

            <a href="?page=base-conhecimento&topic=tarefas" class="bc-card">
                <i class="fas fa-tasks bc-card-icon"></i>
                <span class="bc-card-title">Grupos de Trabalho e Tarefas</span>
            </a>

            <a href="?page=base-conhecimento&topic=telefonia" class="bc-card">
                <i class="fas fa-phone bc-card-icon"></i>
                <span class="bc-card-title">Telefonia</span>
            </a>

            <a href="?page=base-conhecimento&topic=whatsapp" class="bc-card">
                <i class="fab fa-whatsapp bc-card-icon"></i>
                <span class="bc-card-title">WhatsApp</span>
            </a>

            <a href="?page=base-conhecimento&topic=marketing" class="bc-card">
                <i class="fas fa-envelope-open-text bc-card-icon"></i>
                <span class="bc-card-title">Marketing</span>
                <span class="bc-card-subtitle">Disparos de E-mail</span>
            </a>

        </div>
    </div>

</div>

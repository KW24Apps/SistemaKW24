<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php'); exit;
}

$empresa = isset($_GET['empresa']) ? trim($_GET['empresa']) : null;
$topic   = isset($_GET['topic'])   ? trim($_GET['topic'])   : null;
$isInner = $empresa !== null || $topic !== null;

$slugLabels = [
    'nimbus-tax'        => 'Nimbus TAX',
    'grupo-nimbus'      => 'Grupo Nimbus',
    'altura-assessoria' => 'Altura Assessoria',
    'kw24'              => 'KW24',
    'contabilidade'     => 'Contabilidade',
    'tarefas'           => 'Grupos de Trabalho e Tarefas',
    'telefonia'         => 'Telefonia',
    'whatsapp'          => 'WhatsApp',
    'marketing'         => 'Marketing — Disparos de E-mail',
];

$currentSlug  = $empresa ?? $topic;
$currentLabel = htmlspecialchars($slugLabels[$currentSlug] ?? ucfirst($currentSlug ?? ''));
?>
<link rel="stylesheet" href="/assets/css/base-conhecimento.css">

<?php if ($isInner): ?>

<?php if ($empresa === 'nimbus-tax'): ?>
<?php include __DIR__ . '/bc-nimbus-tax.php'; ?>
<?php else: ?>
<!-- ── Placeholder: página ainda não construída ─────────────────────────── -->
<div class="bc-wrap">

    <div class="bc-page-header">
        <i class="fas fa-book-open bc-page-icon"></i>
        <span class="bc-page-title">Base de Conhecimento</span>
    </div>

    <div class="bc-breadcrumb">
        <a href="?page=base-conhecimento">Base de Conhecimento</a>
        <span class="bc-breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
        <span class="bc-breadcrumb-current"><?= $currentLabel ?></span>
    </div>

    <div class="bc-placeholder">
        <i class="fas fa-hard-hat bc-placeholder-icon"></i>
        <div class="bc-placeholder-title">Em construção</div>
        <div class="bc-placeholder-sub">Esta seção ainda não tem conteúdo disponível.</div>
        <a href="?page=base-conhecimento" class="bc-back-btn">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

</div>
<?php endif; ?>

<?php else: ?>
<!-- ── Landing panel ────────────────────────────────────────────────────── -->
<div class="bc-wrap">

    <div class="bc-page-header">
        <i class="fas fa-book-open bc-page-icon"></i>
        <span class="bc-page-title">Base de Conhecimento</span>
    </div>

    <!-- Seção: Empresas -->
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

    <!-- Seção: Comunicação e Processos -->
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
<?php endif; ?>

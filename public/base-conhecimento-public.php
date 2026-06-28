<?php
/**
 * Base de Conhecimento — standalone public layout.
 * Included by index.php before the auth check.
 * No sidebar, no topbar. Accessible without login.
 */

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
$currentLabel = htmlspecialchars($slugLabels[$currentSlug] ?? ucfirst(str_replace('-', ' ', $currentSlug ?? '')));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isInner ? htmlspecialchars($currentLabel) . ' — ' : '' ?>Base de Conhecimento | KW24</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/base-conhecimento.css">
    <?php if ($isInner): ?><link rel="stylesheet" href="/assets/css/bc-automacoes.css"><?php endif; ?>
</head>
<body class="bc-public-body">
    <canvas id="kw24-bg"></canvas>

    <div class="bc-public-wrap">

        <header class="bc-public-header">
            <a href="?page=base-conhecimento">
                <img src="/assets/img/03_KW24_BRANCO1.png" alt="KW24 - Sistemas Harmônicos" class="bc-public-logo">
            </a>
        </header>

        <main class="bc-public-main">

            <?php if ($isInner): ?>

            <?php if ($empresa === 'nimbus-tax'): ?>
            <?php include __DIR__ . '/bc-nimbus-tax.php'; ?>
            <?php else: ?>
            <!-- ── Placeholder ────────────────────────────────────────────── -->
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
            <!-- ── Landing panel ──────────────────────────────────────────── -->
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

        </main>

        <footer class="bc-public-footer">
            <p>&copy; <?= date('Y') ?> KW24 - Sistemas Harmônicos</p>
        </footer>

    </div>

    <script src="/assets/js/bg-dashboard.js"></script>
    <?php if ($empresa === 'nimbus-tax'): ?>
    <script src="/assets/js/bc-automacoes.js"></script>
    <script>
    (function () {
        var wrapper = document.querySelector('.bc-inner');
        if (!wrapper) return;
        bcAuto.restorePage('bc_nimbus_tax', wrapper);
    }());
    </script>
    <?php endif; ?>
</body>
</html>

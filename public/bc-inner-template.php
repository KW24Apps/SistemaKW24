<!DOCTYPE html>
<?php
/**
 * bc-inner-template.php — Validation template for the bc-automacoes design system.
 * Not routed in index.php. Access directly via server for Architect inspection.
 *
 * Demonstrates: bc-sidenav navigation, sections (toggle), aside-box (toggleAside),
 * aside-item (toggleField), all four auto-item variants, tag types, and bcAuto.restorePage.
 */
?>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>bc-inner — Template de Validação</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/bc-automacoes.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f0f2f5; }
    </style>
</head>
<body>

<div class="bc-inner">
<div class="bc-inner-layout">

    <!-- ── Side nav ──────────────────────────────────────────────────────────── -->
    <nav class="bc-sidenav">
        <div class="bc-sidenav-title">Template</div>
        <div class="bc-sidenav-item active"
             data-page="bc-page-a"
             onclick="bcAuto.showPage('bc-page-a', this, 'bc_template')">
            Page A — Exemplo
        </div>
        <div class="bc-sidenav-item"
             data-page="bc-page-b"
             onclick="bcAuto.showPage('bc-page-b', this, 'bc_template')">
            Page B — Placeholder
        </div>
    </nav>

    <!-- ── Main ──────────────────────────────────────────────────────────────── -->
    <div class="bc-inner-main">

        <!-- ═══════════════════════════════════════════════════════════════
             PAGE A — demonstrates sections, auto-items, tags, aside-box
        ════════════════════════════════════════════════════════════════ -->
        <div class="bc-page active" id="bc-page-a">

            <div class="bc-page-header">
                <h1>Page A — Exemplo Completo</h1>
                <div class="bc-page-meta">Template de validação · Arquivo: bc-inner-template.php</div>
                <div class="bc-page-intro">
                    Esta página demonstra todos os padrões visuais do design system
                    <strong>bc-automacoes</strong>: seções colapsáveis, itens de automação
                    nas quatro variantes (default, warning, trigger, api), sistema de tags
                    e aside-box com campos colapsáveis.
                </div>
            </div>

            <div class="bc-page-layout">

                <!-- Stages column -->
                <div class="bc-page-stages">

                    <!-- Section 1 — collapsible, 2 auto-items -->
                    <div class="section">
                        <div class="section-header" onclick="bcAuto.toggle(this)">
                            <div class="section-title">
                                Etapa de Entrada
                                <span class="section-badge badge-stage">Etapa</span>
                            </div>
                            <div class="section-toggle">▾</div>
                        </div>
                        <div class="section-body">

                            <!-- auto-item default (blue) -->
                            <div class="auto-item">
                                <div class="auto-name">Automação Padrão de Entrada</div>
                                <div class="auto-desc">
                                    Roda ao entrar na etapa. Executa configurações iniciais
                                    e verifica vínculos necessários antes de continuar o fluxo.
                                </div>
                                <span class="tag tag-modelo">Automação de Etapa</span>
                                <span class="tag tag-modelo">Modelo</span>
                            </div>

                            <!-- auto-item warning (amber) -->
                            <div class="auto-item warning">
                                <div class="auto-name">Validação de Campo Obrigatório</div>
                                <div class="auto-desc">
                                    Verifica se o campo obrigatório está preenchido.
                                    Se vazio, move o card para a etapa de exceção e encerra o fluxo.
                                </div>
                                <span class="tag tag-modelo">Automação de Etapa</span>
                            </div>

                        </div>
                    </div>

                    <!-- Section 2 — collapsible, trigger + api items -->
                    <div class="section">
                        <div class="section-header" onclick="bcAuto.toggle(this)">
                            <div class="section-title">
                                Etapa de Processamento
                                <span class="section-badge badge-stage">Etapa</span>
                            </div>
                            <div class="section-toggle">▾</div>
                        </div>
                        <div class="section-body">

                            <!-- auto-item trigger (green) -->
                            <div class="auto-item trigger">
                                <div class="auto-name">Gatilho — Campo Alterado</div>
                                <div class="auto-desc">
                                    Disparado automaticamente quando o campo monitorado
                                    é preenchido. Não depende de mudança de etapa.
                                </div>
                                <span class="tag tag-etapa">Gatilho</span>
                            </div>

                            <!-- auto-item api (purple) -->
                            <div class="auto-item api">
                                <div class="auto-name">Integração via API Externa</div>
                                <div class="auto-desc">
                                    Envia os dados do card para o endpoint externo,
                                    aguarda o retorno e grava os resultados de volta no card.
                                </div>
                                <span class="tag tag-api-ext">API Externa</span>
                            </div>

                        </div>
                    </div>

                    <!-- Section 3 — no-toggle (static header) -->
                    <div class="section">
                        <div class="section-header no-toggle">
                            <div class="section-title">
                                Etapa Sem Automações
                                <span class="section-badge badge-empty">Manual</span>
                            </div>
                        </div>
                        <div class="section-body" style="display:block;">
                            <div class="bc-empty">
                                Etapa de controle sem automações. O responsável trabalha o card manualmente.
                            </div>
                        </div>
                    </div>

                </div><!-- /bc-page-stages -->

                <!-- Aside column -->
                <div class="bc-page-aside">

                    <!-- Aside box 1 — Automações Gerais (yellow, collapsed by default) -->
                    <div class="aside-box">
                        <div class="aside-title" onclick="bcAuto.toggleAside(this)">
                            Automações Gerais do Funil
                            <span class="aside-title-arrow collapsed">▾</span>
                        </div>
                        <div class="aside-body collapsed">

                            <div class="aside-item">
                                <div class="aside-item-header" onclick="bcAuto.toggleField(this)">
                                    <div class="aside-item-name">Controle de Etapas</div>
                                    <div class="aside-item-arrow">▾</div>
                                </div>
                                <div class="aside-item-desc">
                                    Fluxo de registro executado a cada mudança de etapa.
                                    Grava automaticamente no card a data, o responsável
                                    e a transição de etapa.
                                </div>
                            </div>

                            <div class="aside-item">
                                <div class="aside-item-header" onclick="bcAuto.toggleField(this)">
                                    <div class="aside-item-name">Controle de Retorno de API</div>
                                    <div class="aside-item-arrow">▾</div>
                                </div>
                                <div class="aside-item-desc">
                                    Fluxo que monitora os campos de retorno de API do card
                                    e executa a ação correspondente quando um retorno chega.
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Aside box 2 — Campos Utilizados (blue variant, collapsed by default) -->
                    <div class="aside-box">
                        <div class="aside-title campos" onclick="bcAuto.toggleAside(this)">
                            Campos Utilizados
                            <span class="aside-title-arrow collapsed">▾</span>
                        </div>
                        <div class="aside-body collapsed">

                            <!-- aside-item — purple variant (campos) -->
                            <div class="aside-item" style="border-left-color:#6366f1;background:#f5f3ff;">
                                <div class="aside-item-header" onclick="bcAuto.toggleField(this)">
                                    <div class="aside-item-name">
                                        Nome do Campo A
                                        <span style="font-weight:400;color:#999">(nativo)</span>
                                    </div>
                                    <div class="aside-item-arrow">▾</div>
                                </div>
                                <div class="aside-item-desc">
                                    Descrição do Campo A. Explica de onde o dado vem,
                                    quando é preenchido e como é utilizado pelas automações.
                                </div>
                            </div>

                            <div class="aside-item" style="border-left-color:#6366f1;background:#f5f3ff;">
                                <div class="aside-item-header" onclick="bcAuto.toggleField(this)">
                                    <div class="aside-item-name">Nome do Campo B</div>
                                    <div class="aside-item-arrow">▾</div>
                                </div>
                                <div class="aside-item-desc">
                                    Descrição do Campo B. Campo customizado criado especificamente
                                    para o processo — não existe nativamente no Bitrix24.
                                </div>
                            </div>

                        </div>
                    </div>

                </div><!-- /bc-page-aside -->

            </div><!-- /bc-page-layout -->
        </div><!-- /bc-page#bc-page-a -->


        <!-- ═══════════════════════════════════════════════════════════════
             PAGE B — empty placeholder
        ════════════════════════════════════════════════════════════════ -->
        <div class="bc-page" id="bc-page-b">

            <div class="bc-page-header">
                <h1>Page B — Placeholder</h1>
                <div class="bc-page-meta">Conteúdo ainda não disponível</div>
            </div>

            <div class="bc-empty">Página em construção.</div>

        </div><!-- /bc-page#bc-page-b -->

    </div><!-- /bc-inner-main -->
</div><!-- /bc-inner-layout -->
</div><!-- /bc-inner -->

<script src="/assets/js/bc-automacoes.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var wrapper = document.querySelector('.bc-inner');
    bcAuto.restorePage('bc_template', wrapper);
});
</script>

</body>
</html>

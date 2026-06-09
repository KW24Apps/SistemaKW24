<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php');
    exit;
}
?>
<style>
/* ── Reset & container ──────────────────────────────────────────────────── */
.rt-wrap {
    display: grid;
    grid-template-rows: 52px 1fr;
    gap: 0;
    flex: 1;
    overflow: hidden;
    font-family: 'Inter', 'Rubik', sans-serif;
    color: #1a202c;
    min-height: 0;
}

/* ── Header bar ─────────────────────────────────────────────────────────── */
.rt-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0 .25rem .5rem;
    border-bottom: 1px solid rgba(255,255,255,.1);
    flex-shrink: 0;
}

.rt-logo-text {
    font-size: 1.1rem;
    font-weight: 800;
    color: #0DC2FF;
    letter-spacing: -.02em;
    white-space: nowrap;
}

.rt-header-title {
    font-size: .95rem;
    font-weight: 600;
    color: #e2e8f0;
    flex: 1;
    text-align: center;
}

/* ── Tab bar ────────────────────────────────────────────────────────────── */
.rt-tabs {
    display: flex;
    gap: 4px;
    align-items: center;
}

.rt-tab-btn {
    padding: .35rem .9rem;
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 6px;
    background: transparent;
    color: #a0aec0;
    font-size: .8rem;
    font-weight: 500;
    cursor: pointer;
    transition: background .15s, color .15s, border-color .15s;
    white-space: nowrap;
    font-family: inherit;
}
.rt-tab-btn:hover {
    background: rgba(255,255,255,.07);
    color: #e2e8f0;
}
.rt-tab-btn.rt-tab-active {
    background: #0DC2FF;
    border-color: #0DC2FF;
    color: #fff;
    font-weight: 700;
}

.rt-refresh-btn {
    margin-left: auto;
    padding: .35rem .9rem;
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 6px;
    background: transparent;
    color: #a0aec0;
    font-size: .78rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    transition: border-color .15s, color .15s;
    font-family: inherit;
}
.rt-refresh-btn:hover { border-color: #0DC2FF; color: #0DC2FF; }

/* ── Tab panels ─────────────────────────────────────────────────────────── */
.rt-tab-panel {
    display: contents; /* active panel uses grid-area of parent */
}

/* ── Diagnóstico layout ─────────────────────────────────────────────────── */
.rt-diag-grid {
    display: grid;
    grid-template-rows: 1fr 210px;
    gap: 8px;
    overflow: hidden;
    min-height: 0;
}

.rt-diag-top {
    display: grid;
    grid-template-columns: 48fr 52fr;
    gap: 8px;
    overflow: hidden;
    min-height: 0;
}

/* Left column: stage table */
.rt-left {
    background: #fff;
    border-radius: 10px;
    display: grid;
    grid-template-rows: 36px 1fr;
    overflow: hidden;
    min-height: 0;
}

/* Right column: status table + KPIs + donut */
.rt-right {
    display: grid;
    grid-template-rows: auto 60px 1fr;
    gap: 8px;
    overflow: hidden;
    min-height: 0;
}

.rt-right-status {
    background: #fff;
    border-radius: 10px;
    display: grid;
    grid-template-rows: 36px 1fr;
    overflow: hidden;
    min-height: 0;
    max-height: 180px;
}

.rt-kpi-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    flex-shrink: 0;
}

.rt-kpi-card {
    background: #fff;
    border-radius: 10px;
    padding: .6rem 1rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.rt-kpi-label {
    font-size: .65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #a0aec0;
    margin-bottom: .2rem;
}

.rt-kpi-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: #1a202c;
    line-height: 1;
}

.rt-right-donut {
    background: #fff;
    border-radius: 10px;
    display: grid;
    grid-template-rows: 36px 1fr;
    overflow: hidden;
    min-height: 0;
}

/* Bottom: detail table */
.rt-bottom {
    background: #fff;
    border-radius: 10px;
    display: grid;
    grid-template-rows: 36px 1fr;
    overflow: hidden;
    min-height: 0;
}

/* ── Panel header (title bar in each card) ──────────────────────────────── */
.rt-panel-title {
    padding: 0 .875rem;
    display: flex;
    align-items: center;
    gap: .4rem;
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #a0aec0;
    border-bottom: 1px solid #f0f4f8;
    flex-shrink: 0;
}

.rt-panel-title i { color: #0DC2FF; }

/* ── Table styles ───────────────────────────────────────────────────────── */
.rt-table-scroll {
    overflow-y: auto;
    overflow-x: hidden;
    height: 100%;
}

.rt-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .78rem;
}

.rt-table thead th {
    position: sticky;
    top: 0;
    background: #f7fafc;
    padding: .35rem .75rem;
    font-size: .65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #718096;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
    z-index: 1;
}

.rt-table tbody tr {
    border-bottom: 1px solid #f7fafc;
    transition: background .1s;
}
.rt-table tbody tr:hover { background: #f0f7ff; }

.rt-table tbody td {
    padding: .3rem .75rem;
    color: #1a202c;
    vertical-align: middle;
}

.rt-status-row { cursor: pointer; }
.rt-row-active { background: #e8f8ff !important; }
.rt-row-active td { font-weight: 600; color: #0a6a8a; }

.rt-td-clip {
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.rt-deal-link {
    color: #0DC2FF;
    text-decoration: none;
    font-weight: 600;
}
.rt-deal-link:hover { text-decoration: underline; }

/* ── Misc states ────────────────────────────────────────────────────────── */
.rt-spin, .rt-empty, .rt-error {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    min-height: 60px;
    font-size: .82rem;
    color: #a0aec0;
    gap: .5rem;
}
.rt-error { color: #e53e3e; }

/* ── Placeholder panels (tabs not yet built) ────────────────────────────── */
.rt-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 1;
    background: rgba(255,255,255,.04);
    border-radius: 10px;
    border: 1px dashed rgba(255,255,255,.15);
}
.rt-placeholder-inner {
    text-align: center;
    color: #718096;
}
.rt-placeholder-inner i {
    font-size: 2rem;
    display: block;
    margin-bottom: .75rem;
    color: #a0aec0;
}
.rt-placeholder-inner p { font-size: .875rem; }

/* ECharts container */
#rt-donut-chart { width: 100%; height: 100%; }
</style>

<div class="rt-wrap">

    <!-- ── Header ─────────────────────────────────────────────────────────── -->
    <div class="rt-header">
        <span class="rt-logo-text">NimbusTax</span>

        <!-- Tabs -->
        <div class="rt-tabs">
            <button class="rt-tab-btn rt-tab-active" data-tab="diagnostico">Funil Diagnóstico</button>
            <button class="rt-tab-btn" data-tab="operacional">Funil Operacional</button>
            <button class="rt-tab-btn" data-tab="retificacao">Funil Retificação</button>
            <button class="rt-tab-btn" data-tab="faturamento">Faturamento</button>
            <button class="rt-tab-btn" data-tab="dashboard">Dashboard</button>
        </div>

        <button class="rt-refresh-btn" id="rt-refresh-btn">
            <i class="fas fa-sync-alt" id="rt-refresh-icon" style="font-size:.75rem"></i>
            Atualizar
        </button>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB: Funil Diagnóstico                                                -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="rt-tab-panel" id="rt-panel-diagnostico">
        <div class="rt-diag-grid">

            <!-- Top row: two columns -->
            <div class="rt-diag-top">

                <!-- ── LEFT: Nome da Etapa Numerado ──────────────────────── -->
                <div class="rt-left">
                    <div class="rt-panel-title">
                        <i class="fas fa-list-ol"></i> Nome da Etapa Numerado
                    </div>
                    <div class="rt-table-scroll" id="rt-etapa-table">
                        <div class="rt-spin"><i class="fas fa-spinner fa-spin"></i></div>
                    </div>
                </div>

                <!-- ── RIGHT column ───────────────────────────────────────── -->
                <div class="rt-right">

                    <!-- Status table (cross-filter source) -->
                    <div class="rt-right-status">
                        <div class="rt-panel-title">
                            <i class="fas fa-filter"></i> Etapas Oportunidades
                            <span style="margin-left:auto;font-size:.6rem;font-weight:400;color:#cbd5e0">clique para filtrar</span>
                        </div>
                        <div class="rt-table-scroll" id="rt-status-table">
                            <div class="rt-spin"><i class="fas fa-spinner fa-spin"></i></div>
                        </div>
                    </div>

                    <!-- KPI cards -->
                    <div class="rt-kpi-row">
                        <div class="rt-kpi-card">
                            <div class="rt-kpi-label"><i class="fas fa-hashtag" style="color:#0DC2FF;margin-right:.25rem"></i> Total de Oportunidades</div>
                            <div class="rt-kpi-value" id="rt-kpi-total">—</div>
                        </div>
                        <div class="rt-kpi-card">
                            <div class="rt-kpi-label"><i class="fas fa-dollar-sign" style="color:#26FF93;margin-right:.25rem"></i> Valor Total</div>
                            <div class="rt-kpi-value" id="rt-kpi-valor" style="font-size:1rem">—</div>
                        </div>
                    </div>

                    <!-- Donut chart -->
                    <div class="rt-right-donut">
                        <div class="rt-panel-title">
                            <i class="fas fa-chart-pie"></i> Contagem Top 9 + Outros por Produto
                        </div>
                        <div id="rt-donut-chart">
                            <div class="rt-spin"><i class="fas fa-spinner fa-spin"></i></div>
                        </div>
                    </div>

                </div><!-- /rt-right -->
            </div><!-- /rt-diag-top -->

            <!-- Bottom row: detail table -->
            <div class="rt-bottom">
                <div class="rt-panel-title">
                    <i class="fas fa-table"></i> Detalhe
                    <span style="margin-left:auto;font-size:.6rem;font-weight:400;color:#cbd5e0">máx. 500 registros · ID clicável abre o negócio no Bitrix</span>
                </div>
                <div class="rt-table-scroll" id="rt-detalhe-table">
                    <div class="rt-spin"><i class="fas fa-spinner fa-spin"></i></div>
                </div>
            </div>

        </div><!-- /rt-diag-grid -->
    </div><!-- /rt-panel-diagnostico -->

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB: Funil Operacional (placeholder)                                   -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="rt-tab-panel" id="rt-panel-operacional" style="display:none">
        <div class="rt-placeholder">
            <div class="rt-placeholder-inner">
                <i class="fas fa-cogs"></i>
                <p>Funil Operacional — em construção</p>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB: Funil Retificação (placeholder)                                   -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="rt-tab-panel" id="rt-panel-retificacao" style="display:none">
        <div class="rt-placeholder">
            <div class="rt-placeholder-inner">
                <i class="fas fa-redo"></i>
                <p>Funil Retificação — em construção</p>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB: Faturamento (placeholder)                                          -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="rt-tab-panel" id="rt-panel-faturamento" style="display:none">
        <div class="rt-placeholder">
            <div class="rt-placeholder-inner">
                <i class="fas fa-file-invoice-dollar"></i>
                <p>Faturamento — em construção</p>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB: Dashboard (placeholder)                                            -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="rt-tab-panel" id="rt-panel-dashboard" style="display:none">
        <div class="rt-placeholder">
            <div class="rt-placeholder-inner">
                <i class="fas fa-tachometer-alt"></i>
                <p>Dashboard — em construção</p>
            </div>
        </div>
    </div>

</div><!-- /rt-wrap -->

<script src="/assets/js/relatorio-teste.js"></script>
<script>
if (typeof rtInit === 'function') {
    rtInit();
} else {
    // Fallback: re-executed via sidebar AJAX re-eval — rtInit already on window
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof rtInit === 'function') rtInit();
    });
}
</script>

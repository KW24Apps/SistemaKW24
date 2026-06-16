<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php'); exit;
}
?>

<style>
/* ===== FINANCEIRO-RELATORIOS — herda padrões de financeiro.php ===== */

/* ── Filtros ── */
.finrel-filter-bar {
    display: flex;
    align-items: center;
    gap: .75rem;
    flex-wrap: wrap;
    background: rgba(255,255,255,0.07);
    border: 1.5px solid rgba(13,194,255,0.20);
    border-radius: 12px;
    padding: .9rem 1.25rem;
    margin-bottom: 1.25rem;
}
.finrel-filter-group {
    display: flex;
    align-items: center;
    gap: .45rem;
}
.finrel-filter-label {
    font-size: .62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: rgba(255,255,255,.4);
    white-space: nowrap;
}
.finrel-select {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 7px;
    color: #fff;
    font-size: .68rem;
    padding: .35rem .65rem;
    cursor: pointer;
    outline: none;
    min-width: 130px;
}
.finrel-select:focus { border-color: rgba(13,194,255,0.5); }
.finrel-select option { background: #0d1e2d; color: #fff; }
.finrel-filter-btn {
    background: #0DC2FF;
    color: #061920;
    border: none;
    border-radius: 8px;
    padding: .4rem 1rem;
    font-size: .68rem;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s;
    white-space: nowrap;
}
.finrel-filter-btn:hover { background: #08aadd; }
.finrel-periodo-info {
    margin-left: auto;
    font-size: .68rem;
    color: rgba(255,255,255,.3);
    white-space: nowrap;
}

/* ── Top row: KPIs empilhados + Faturas inline ── */
.finrel-top-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    height: 180px;
    margin-bottom: 1.25rem;
}
.finrel-kpi-block {
    display: flex;
    flex-direction: column;
    gap: 6px;
    height: 100%;
}
.finrel-kpi-total {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: .75rem 1rem;
}
.finrel-kpi-sub-row {
    height: 62px;
    flex-shrink: 0;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
}
.finrel-kpi-sub {
    padding: .5rem .75rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.finrel-fat-panel {
    background: rgba(255,255,255,0.05);
    border: 1.5px solid rgba(255,255,255,0.10);
    border-radius: 12px;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
}
.finrel-fat-list {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    min-height: 0;
}
.finrel-fat-list::-webkit-scrollbar { width: 4px; }
.finrel-fat-list::-webkit-scrollbar-track { background: rgba(255,255,255,0.03); }
.finrel-fat-list::-webkit-scrollbar-thumb { background: rgba(13,194,255,0.25); border-radius: 2px; }
.finrel-fat-row {
    display: grid;
    grid-template-columns: 2.5rem 1fr auto;
    align-items: center;
    padding: .3rem .9rem;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    font-size: .68rem;
    gap: .5rem;
}
.finrel-fat-row:last-child { border-bottom: none; }
.finrel-fat-row:hover { background: rgba(255,255,255,0.03); }
.finrel-fat-id {
    color: rgba(255,255,255,.3);
    font-size: .62rem;
}
.finrel-fat-nome {
    font-weight: 600;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
}
.finrel-fat-val {
    font-family: 'Inter', monospace;
    font-size: .68rem;
    font-weight: 700;
    color: rgba(255,255,255,.9);
    white-space: nowrap;
}

/* ── KPI cards (iguais a financeiro.php) ── */
.fin-kpi-card {
    background: rgba(255,255,255,0.05);
    border: 1.5px solid rgba(255,255,255,0.10);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    position: relative;
    overflow: hidden;
}
.fin-kpi-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    border-radius: 12px 12px 0 0;
}
.fin-kpi-card.kpi-fatura::before  { background: linear-gradient(90deg,#f6ad55,#f6e05e); }
.fin-kpi-card.kpi-suporte::before { background: linear-gradient(90deg,#0DC2FF,#0080aa); }
.fin-kpi-card.kpi-dev::before     { background: linear-gradient(90deg,#b794f4,#805ad5); }
.fin-kpi-card.kpi-infra::before   { background: linear-gradient(90deg,#26FF93,#059669); }
.fin-kpi-label {
    font-size: .62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: rgba(255,255,255,.4);
    margin-bottom: .4rem;
    margin-top: .2rem;
}
.fin-kpi-value {
    font-size: 1.15rem;
    font-weight: 700;
    color: #fff;
    font-family: 'Inter', monospace;
}

/* ── Painéis de tabela ── */
.finrel-panel {
    background: rgba(255,255,255,0.05);
    border: 1.5px solid rgba(255,255,255,0.10);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.25rem;
}
.finrel-panel-header {
    padding: .75rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    gap: .6rem;
}
.finrel-panel-title {
    font-size: .62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: rgba(255,255,255,.5);
}
.finrel-panel-count {
    font-size: .62rem;
    color: rgba(255,255,255,.25);
    margin-left: auto;
}
.finrel-scroll {
    overflow-x: auto;
    overflow-y: auto;
    max-height: 360px;
}
.finrel-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
.finrel-scroll::-webkit-scrollbar-track { background: rgba(255,255,255,0.03); }
.finrel-scroll::-webkit-scrollbar-thumb { background: rgba(13,194,255,0.25); border-radius: 3px; }

/* Painéis com altura fixa e scroll interno (Serviços e Infra) */
.finrel-panel-scroll {
    height: 260px;
    display: flex;
    flex-direction: column;
}
.finrel-panel-scroll .finrel-scroll {
    flex: 1;
    min-height: 0;
    max-height: none;
}

/* ── Tabela base ── */
.finrel-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .68rem;
    min-width: 600px;
}
.finrel-table thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #0d1e2d;
    padding: .6rem .9rem;
    text-align: left;
    font-size: .62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: rgba(255,255,255,.4);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    white-space: nowrap;
}
.finrel-table thead th.num { text-align: right; }
.finrel-table td {
    padding: .65rem .9rem;
    color: rgba(255,255,255,.82);
    border-bottom: 1px solid rgba(255,255,255,0.05);
    vertical-align: middle;
    white-space: nowrap;
}
.finrel-table tbody tr:last-child td { border-bottom: none; }
.finrel-table tbody tr:hover > td { background: rgba(255,255,255,0.03); }
.finrel-table td.num {
    text-align: right;
    font-family: 'Inter', monospace;
    font-size: .68rem;
}
.finrel-table td.num-bold {
    text-align: right;
    font-family: 'Inter', monospace;
    font-size: .68rem;
    font-weight: 700;
    color: #fff;
}
.finrel-table td.nome-col { font-weight: 600; color: #fff; }

/* Linha de totais */
.finrel-table tr.totals-row td {
    border-top: 1px solid rgba(13,194,255,0.2);
    border-bottom: none;
    background: rgba(13,194,255,0.04);
    font-weight: 700;
    color: #fff;
}

/* Chevron expand */
.finrel-chevron-cell { width: 32px; padding: .4rem .4rem .4rem .9rem !important; }
.finrel-chevron {
    background: none;
    border: none;
    color: rgba(255,255,255,.28);
    cursor: pointer;
    padding: .2rem;
    border-radius: 5px;
    transition: color .15s, transform .18s;
    line-height: 1;
    display: flex;
    align-items: center;
}
.finrel-chevron:hover { color: rgba(255,255,255,.7); }
.finrel-chevron.open  { color: #0DC2FF; transform: rotate(90deg); }

/* Sub-linhas (departamento) */
.finrel-sub-row td { background: rgba(0,0,0,0.12) !important; }
.finrel-sub-row td.nome-col { padding-left: 2.25rem !important; font-weight: 500; color: rgba(255,255,255,.7); }
.finrel-sub-row td.num, .finrel-sub-row td.num-bold { color: rgba(255,255,255,.65); }

/* Linha com empresa aberta */
.finrel-table tbody tr.row-open > td { background: rgba(13,194,255,0.07) !important; }
.finrel-table tbody tr.row-open td.nome-col { color: #0DC2FF; }

/* Badge de contrato */
.badge-contrato {
    display: inline-block;
    font-size: .62rem;
    font-weight: 700;
    padding: .15rem .4rem;
    border-radius: 4px;
    background: rgba(13,194,255,0.15);
    color: #0DC2FF;
    margin-left: .45rem;
    vertical-align: middle;
}

@keyframes kwPop { from { transform:scale(.92); opacity:0; } to { transform:scale(1); opacity:1; } }

/* Detalhe inline (Demandas) */
.finrel-detail-row > td {
    padding: 0 !important;
    background: transparent;
    border-bottom: 1px solid rgba(255,255,255,0.05) !important;
}
#rl-dem-table td {
    padding: 7px 10px;
}

.finrel-detail-inner {
    padding: 10px 14px 10px 36px;
    background: rgba(13,194,255,0.025);
    border-top: 1px solid rgba(13,194,255,0.10);
    animation: finDetailIn .14s ease;
    font-size: .68rem;
    color: rgba(255,255,255,.6);
    line-height: 1.6;
}
@keyframes finDetailIn {
    from { opacity:0; transform:translateY(-3px); }
    to   { opacity:1; transform:translateY(0); }
}
.finrel-detail-inner strong { color: rgba(255,255,255,.9); }

/* Tempo pill */
.tempo-pill {
    font-size: .68rem;
    font-weight: 600;
    font-family: 'Inter', monospace;
    color: rgba(255,255,255,.7);
}

/* Empty state */
.finrel-empty {
    text-align: center;
    padding: 2.5rem 1rem;
    color: rgba(255,255,255,.28);
    font-size: .68rem;
}
.finrel-empty i { font-size: 1.6rem; display: block; margin-bottom: .65rem; color: rgba(13,194,255,.35); }

/* Loading */
.finrel-loading {
    text-align: center;
    padding: 3rem 1rem;
    color: rgba(255,255,255,.35);
    font-size: .68rem;
}
</style>

<!-- ── Filtros ──────────────────────────────────────────────────────────────── -->
<div class="finrel-filter-bar" id="finrel-filter-bar">
    <div class="finrel-filter-group">
        <span class="finrel-filter-label">Mês</span>
        <select class="finrel-select" id="finrel-sel-mes"></select>
    </div>
    <div class="finrel-filter-group">
        <span class="finrel-filter-label">Empresa</span>
        <select class="finrel-select" id="finrel-sel-empresa">
            <option value="">Todas</option>
        </select>
    </div>
    <div class="finrel-filter-group">
        <span class="finrel-filter-label">Depto</span>
        <select class="finrel-select" id="finrel-sel-depto">
            <option value="">Todos</option>
        </select>
    </div>
    <button class="finrel-filter-btn" onclick="finrelFiltrar()">
        <i class="fas fa-search" style="margin-right:.4rem"></i>Filtrar
    </button>
    <span class="finrel-periodo-info" id="finrel-periodo-info">Carregando…</span>
</div>

<!-- ── Top row: KPIs empilhados + Faturas inline ──────────────────────────── -->
<div class="finrel-top-row">
    <!-- Coluna esquerda: KPIs empilhados -->
    <div class="finrel-kpi-block">
        <div class="fin-kpi-card kpi-fatura finrel-kpi-total">
            <div class="fin-kpi-label">Total Geral</div>
            <div class="fin-kpi-value" id="rl-kpi-total">—</div>
        </div>
        <div class="finrel-kpi-sub-row">
            <div class="fin-kpi-card kpi-suporte finrel-kpi-sub">
                <div class="fin-kpi-label">Suporte</div>
                <div class="fin-kpi-value" id="rl-kpi-suporte">—</div>
            </div>
            <div class="fin-kpi-card kpi-dev finrel-kpi-sub">
                <div class="fin-kpi-label">Dev</div>
                <div class="fin-kpi-value" id="rl-kpi-dev">—</div>
            </div>
            <div class="fin-kpi-card kpi-infra finrel-kpi-sub">
                <div class="fin-kpi-label">Infra</div>
                <div class="fin-kpi-value" id="rl-kpi-infra">—</div>
            </div>
        </div>
    </div>
    <!-- Coluna direita: Faturas inline com scroll -->
    <div class="finrel-fat-panel">
        <div class="finrel-panel-header">
            <i class="fas fa-file-invoice-dollar" style="color:#f6ad55;font-size:.85rem"></i>
            <span class="finrel-panel-title">Faturas</span>
            <span class="finrel-panel-count" id="rl-fat-count"></span>
        </div>
        <div class="finrel-fat-list" id="rl-fat-list">
            <div class="finrel-loading"><i class="fas fa-circle-notch fa-spin"></i> Carregando…</div>
        </div>
    </div>
</div>

<!-- ── Tabela 2: Serviços ──────────────────────────────────────────────────── -->
<div class="finrel-panel finrel-panel-scroll">
    <div class="finrel-panel-header">
        <i class="fas fa-headset" style="color:#0DC2FF;font-size:.85rem"></i>
        <span class="finrel-panel-title">Serviços por empresa</span>
        <span class="finrel-panel-count" id="rl-svc-count"></span>
    </div>
    <div class="finrel-scroll">
        <table class="finrel-table" id="rl-svc-table">
            <thead>
                <tr>
                    <th class="finrel-chevron-cell"></th>
                    <th>Empresa</th>
                    <th class="num">Sup TI</th>
                    <th class="num">Sup B24</th>
                    <th class="num">Dev Impl.</th>
                    <th class="num">Dev Melh.</th>
                    <th class="num">Total</th>
                </tr>
            </thead>
            <tbody id="rl-svc-body">
                <tr><td colspan="7" class="finrel-loading"><i class="fas fa-circle-notch fa-spin"></i> Carregando…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Tabela 3: Infra ─────────────────────────────────────────────────────── -->
<div class="finrel-panel finrel-panel-scroll">
    <div class="finrel-panel-header">
        <i class="fas fa-server" style="color:#26FF93;font-size:.85rem"></i>
        <span class="finrel-panel-title">Infra por empresa</span>
        <span class="finrel-panel-count" id="rl-infra-count"></span>
    </div>
    <div class="finrel-scroll" id="rl-infra-scroll">
        <table class="finrel-table" id="rl-infra-table">
            <thead><tr id="rl-infra-thead-row"></tr></thead>
            <tbody id="rl-infra-body">
                <tr><td colspan="4" class="finrel-loading"><i class="fas fa-circle-notch fa-spin"></i> Carregando…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Tabela 4: Demandas ─────────────────────────────────────────────────── -->
<div class="finrel-panel">
    <div class="finrel-panel-header">
        <i class="fas fa-tasks" style="color:#b794f4;font-size:.85rem"></i>
        <span class="finrel-panel-title">Demandas faturáveis</span>
        <span class="finrel-panel-count" id="rl-dem-count"></span>
    </div>
    <div class="finrel-scroll">
        <table class="finrel-table" id="rl-dem-table">
            <thead>
                <tr>
                    <th class="finrel-chevron-cell"></th>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Departamento</th>
                    <th>Solicitante</th>
                    <th class="num">Tempo</th>
                    <th>Mês</th>
                </tr>
            </thead>
            <tbody id="rl-dem-body">
                <tr><td colspan="8" class="finrel-loading"><i class="fas fa-circle-notch fa-spin"></i> Carregando…</td></tr>
            </tbody>
        </table>
    </div>
</div>


<script>
(function () {
    'use strict';

    // ── Estado ──────────────────────────────────────────────────────────────
    var _data        = null;
    var _openSvc     = {};
    var _openInfra   = {};
    var _openDem     = {};
    var _infraCols   = ['rdp','vm','dados','sistemaDom','hospedagem','email','cnpj','clicksign','receita','whatsapp'];
    var _infraLabels = {rdp:'RDP',vm:'VM',dados:'Dados',sistemaDom:'Sist. Dom.',hospedagem:'Hospedagem',email:'E-mail',cnpj:'CNPJ',clicksign:'ClickSign',receita:'Receita Fed.',whatsapp:'WhatsApp'};

    // ── Helpers ──────────────────────────────────────────────────────────────
    function fmtBRL(v) {
        if (!v) return '<span style="color:rgba(255,255,255,.2)">—</span>';
        return 'R$ ' + Number(v).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    function fmtBRLplain(v) {
        return 'R$ ' + Number(v || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    function fmtTempo(mins) {
        mins = parseInt(mins) || 0;
        var h = Math.floor(mins / 60), m = mins % 60;
        if (!h && !m) return '<span style="color:rgba(255,255,255,.2)">—</span>';
        return '<span class="tempo-pill">' + (h ? h + 'h ' : '') + (m ? (m < 10 ? '0' : '') + m + 'min' : '') + '</span>';
    }
    function escHtml(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function qs(id) { return document.getElementById(id); }

    // ── Inicialização ────────────────────────────────────────────────────────
    function init() {
        // Popular meses com opções estáticas (último 12 meses) e aguardar API
        var sel = qs('finrel-sel-mes');
        // Será populado após resposta da API (mesesDisponiveis)
        finrelLoad();
    }

    // ── Carregar dados ───────────────────────────────────────────────────────
    window.finrelFiltrar = function () {
        finrelLoad(
            qs('finrel-sel-mes').value,
            qs('finrel-sel-empresa').value,
            qs('finrel-sel-depto').value
        );
    };

    function finrelLoad(mes, empresa, depto) {
        var params = [];
        if (mes)     params.push('mes='     + encodeURIComponent(mes));
        if (empresa) params.push('empresa=' + encodeURIComponent(empresa));
        if (depto)   params.push('depto='   + encodeURIComponent(depto));
        var url = '/api/relatorios-cards.php' + (params.length ? '?' + params.join('&') : '');

        // Loading state
        ['rl-svc-body','rl-infra-body','rl-dem-body'].forEach(function (id) {
            var cols = id === 'rl-infra-body' ? 4 : 7;
            qs(id).innerHTML = '<tr><td colspan="' + cols + '" class="finrel-loading"><i class="fas fa-circle-notch fa-spin"></i> Carregando…</td></tr>';
        });
        qs('rl-fat-list').innerHTML = '<div class="finrel-loading"><i class="fas fa-circle-notch fa-spin"></i> Carregando…</div>';
        qs('finrel-periodo-info').textContent = 'Carregando…';

        fetch(url).then(function (r) { return r.json(); }).then(function (d) {
            if (d.erro) { qs('finrel-periodo-info').textContent = 'Erro: ' + d.erro; return; }
            _data = d;
            _openSvc = {}; _openInfra = {}; _openDem = {};
            renderAll(d);
        }).catch(function (e) {
            qs('finrel-periodo-info').textContent = 'Erro de rede';
        });
    }

    // ── Render geral ─────────────────────────────────────────────────────────
    function renderAll(d) {
        // Período
        var p = d.periodo || {};
        qs('finrel-periodo-info').textContent = (p.referencia || '') + (p.inicio ? '  (' + fmtDate(p.inicio) + ' – ' + fmtDate(p.fim) + ')' : '');

        // KPIs
        qs('rl-kpi-total').textContent   = fmtBRLplain(d.kpis.total);
        qs('rl-kpi-suporte').textContent = fmtBRLplain(d.kpis.suporte);
        qs('rl-kpi-dev').textContent     = fmtBRLplain(d.kpis.dev);
        qs('rl-kpi-infra').textContent   = fmtBRLplain(d.kpis.infra);

        // Populares filtros (apenas na primeira carga)
        var selMes = qs('finrel-sel-mes');
        if (selMes.options.length === 0 && d.mesesDisponiveis) {
            d.mesesDisponiveis.forEach(function (m) {
                var o = document.createElement('option');
                o.value = m; o.textContent = m;
                selMes.appendChild(o);
            });
        }
        var selEmp = qs('finrel-sel-empresa');
        if (selEmp.options.length <= 1 && d.empresasDisponiveis) {
            d.empresasDisponiveis.forEach(function (e) {
                var o = document.createElement('option');
                o.value = e.id; o.textContent = escHtml(e.nome);
                selEmp.appendChild(o);
            });
        }
        var selDep = qs('finrel-sel-depto');
        if (selDep.options.length <= 1 && d.deptosDisponiveis) {
            d.deptosDisponiveis.forEach(function (dep) {
                var o = document.createElement('option');
                o.value = dep; o.textContent = escHtml(dep);
                selDep.appendChild(o);
            });
        }
        // Marcar mês atual no select
        if (selMes.options.length > 0 && !selMes.value && d.periodo) {
            selMes.value = d.periodo.referencia;
        }

        renderFaturas(d.faturas || []);
        renderServicos(d.servicos || []);
        renderInfra(d.infra || []);
        renderDemandas(d.demandas || []);
    }

    function fmtDate(s) {
        if (!s) return '';
        var parts = s.split('-');
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    // ── Lista Faturas (painel inline) ────────────────────────────────────────
    function renderFaturas(faturas) {
        qs('rl-fat-count').textContent = faturas.length + ' fatura' + (faturas.length !== 1 ? 's' : '');
        if (!faturas.length) {
            qs('rl-fat-list').innerHTML = '<div class="finrel-empty"><i class="fas fa-inbox"></i>Nenhuma fatura no período</div>';
            return;
        }
        var html = '';
        faturas.forEach(function (f) {
            html += '<div class="finrel-fat-row">'
                + '<span class="finrel-fat-id">#' + f.id + '</span>'
                + '<span class="finrel-fat-nome" title="' + escHtml(f.empresa) + '">' + escHtml(f.empresa) + '</span>'
                + '<span class="finrel-fat-val">' + fmtBRLplain(f.total) + '</span>'
                + '</div>';
        });
        qs('rl-fat-list').innerHTML = html;
    }

    // ── Tabela 2: Serviços ───────────────────────────────────────────────────
    function renderServicos(servicos) {
        qs('rl-svc-count').textContent = servicos.length + ' empresa' + (servicos.length !== 1 ? 's' : '');
        if (!servicos.length) {
            qs('rl-svc-body').innerHTML = '<tr><td colspan="7" class="finrel-empty"><i class="fas fa-inbox"></i>Nenhum serviço no período</td></tr>';
            return;
        }
        var html = '';
        var tots = {suporteTI:0,suporteB24:0,devImpl:0,devMelh:0,total:0};
        servicos.forEach(function (s, idx) {
            var hasExpand = s.multiploDepts;
            var chevron = hasExpand
                ? '<button class="finrel-chevron" id="svc-chev-' + idx + '" onclick="toggleSvc(' + idx + ')"><i class="fas fa-chevron-right" style="font-size:.68rem"></i></button>'
                : '';
            var totalVal = s.hasContract ? (s.vContSup + s.vContDev) : s.total.total;
            var badge = s.hasContract ? '<span class="badge-contrato">Contrato</span>' : '';
            tots.suporteTI  += s.total.suporteTI;
            tots.suporteB24 += s.total.suporteB24;
            tots.devImpl    += s.total.devImpl;
            tots.devMelh    += s.total.devMelh;
            tots.total      += totalVal;

            html += '<tr id="svc-row-' + idx + '">'
                + '<td class="finrel-chevron-cell">' + chevron + '</td>'
                + '<td class="nome-col">' + escHtml(s.empresa) + badge + '</td>'
                + '<td class="num">' + fmtBRL(s.total.suporteTI)  + '</td>'
                + '<td class="num">' + fmtBRL(s.total.suporteB24) + '</td>'
                + '<td class="num">' + fmtBRL(s.total.devImpl)    + '</td>'
                + '<td class="num">' + fmtBRL(s.total.devMelh)    + '</td>'
                + '<td class="num-bold">' + fmtBRLplain(totalVal) + '</td>'
                + '</tr>';

            if (hasExpand) {
                s.depts.forEach(function (dep, di) {
                    html += '<tr class="finrel-sub-row" id="svc-sub-' + idx + '-' + di + '" style="display:none">'
                        + '<td class="finrel-chevron-cell"></td>'
                        + '<td class="nome-col">' + escHtml(dep.nome) + '</td>'
                        + '<td class="num">' + fmtBRL(dep.suporteTI)  + '</td>'
                        + '<td class="num">' + fmtBRL(dep.suporteB24) + '</td>'
                        + '<td class="num">' + fmtBRL(dep.devImpl)    + '</td>'
                        + '<td class="num">' + fmtBRL(dep.devMelh)    + '</td>'
                        + '<td class="num">' + fmtBRL(dep.total)      + '</td>'
                        + '</tr>';
                });
            }
        });
        qs('rl-svc-body').innerHTML = html;
    }

    window.toggleSvc = function (idx) {
        var isOpen = _openSvc[idx];
        var chev   = qs('svc-chev-' + idx);
        var row    = qs('svc-row-'  + idx);
        var s      = (_data.servicos || [])[idx];
        if (!s) return;
        var show = !isOpen;
        _openSvc[idx] = show;
        if (chev) chev.classList.toggle('open', show);
        if (row)  row.classList.toggle('row-open', show);
        s.depts.forEach(function (_, di) {
            var sub = qs('svc-sub-' + idx + '-' + di);
            if (sub) sub.style.display = show ? '' : 'none';
        });
    };

    // ── Tabela 3: Infra ──────────────────────────────────────────────────────
    function renderInfra(infra) {
        qs('rl-infra-count').textContent = infra.length + ' empresa' + (infra.length !== 1 ? 's' : '');

        // Determinar colunas não-zero
        var activeCols = [];
        infra.forEach(function (co) {
            _infraCols.forEach(function (col) {
                if ((co.total[col] || 0) > 0 && activeCols.indexOf(col) === -1) activeCols.push(col);
            });
        });
        var colSpan = activeCols.length + 3; // chevron + empresa + total

        if (!infra.length) {
            qs('rl-infra-thead-row').innerHTML = '<th></th><th>Empresa</th><th class="num">Total</th>';
            qs('rl-infra-body').innerHTML = '<tr><td colspan="3" class="finrel-empty"><i class="fas fa-inbox"></i>Nenhuma infra no período</td></tr>';
            return;
        }

        // Header
        var thHtml = '<th class="finrel-chevron-cell"></th><th>Empresa</th>';
        activeCols.forEach(function (col) { thHtml += '<th class="num">' + _infraLabels[col] + '</th>'; });
        thHtml += '<th class="num">Total</th>';
        qs('rl-infra-thead-row').innerHTML = thHtml;

        var html = '';
        var tots = {};
        activeCols.forEach(function (col) { tots[col] = 0; });
        tots.total = 0;

        infra.forEach(function (co, idx) {
            var hasExpand = co.multiploDepts;
            var chevron = hasExpand
                ? '<button class="finrel-chevron" id="infra-chev-' + idx + '" onclick="toggleInfra(' + idx + ')"><i class="fas fa-chevron-right" style="font-size:.68rem"></i></button>'
                : '';
            activeCols.forEach(function (col) { tots[col] += (co.total[col] || 0); });
            tots.total += co.total.total;

            html += '<tr id="infra-row-' + idx + '">'
                + '<td class="finrel-chevron-cell">' + chevron + '</td>'
                + '<td class="nome-col">' + escHtml(co.empresa) + '</td>';
            activeCols.forEach(function (col) { html += '<td class="num">' + fmtBRL(co.total[col]) + '</td>'; });
            html += '<td class="num-bold">' + fmtBRLplain(co.total.total) + '</td></tr>';

            if (hasExpand) {
                co.depts.forEach(function (dep, di) {
                    html += '<tr class="finrel-sub-row" id="infra-sub-' + idx + '-' + di + '" style="display:none">'
                        + '<td class="finrel-chevron-cell"></td>'
                        + '<td class="nome-col">' + escHtml(dep.nome) + '</td>';
                    activeCols.forEach(function (col) { html += '<td class="num">' + fmtBRL(dep[col]) + '</td>'; });
                    html += '<td class="num">' + fmtBRL(dep.total) + '</td></tr>';
                });
            }
        });

        qs('rl-infra-body').innerHTML = html;
    }

    window.toggleInfra = function (idx) {
        var isOpen = _openInfra[idx];
        var chev = qs('infra-chev-' + idx);
        var row  = qs('infra-row-'  + idx);
        var co   = (_data.infra || [])[idx];
        if (!co) return;
        var show = !isOpen;
        _openInfra[idx] = show;
        if (chev) chev.classList.toggle('open', show);
        if (row)  row.classList.toggle('row-open', show);
        co.depts.forEach(function (_, di) {
            var sub = qs('infra-sub-' + idx + '-' + di);
            if (sub) sub.style.display = show ? '' : 'none';
        });
    };

    // ── Tabela 4: Demandas ───────────────────────────────────────────────────
    function renderDemandas(demandas) {
        qs('rl-dem-count').textContent = demandas.length + ' demanda' + (demandas.length !== 1 ? 's' : '');
        if (!demandas.length) {
            qs('rl-dem-body').innerHTML = '<tr><td colspan="8" class="finrel-empty"><i class="fas fa-inbox"></i>Nenhuma demanda no período</td></tr>';
            return;
        }
        var html = '';
        demandas.forEach(function (d, idx) {
            var solicitanteCell = d.solicitante
                ? '<td style="color:rgba(255,255,255,.5);font-size:.68rem">' + escHtml(d.solicitante) + '</td>'
                : '<td><span style="color:rgba(255,255,255,.2)">—</span></td>';
            html += '<tr id="dem-row-' + idx + '">'
                + '<td class="finrel-chevron-cell"><button class="finrel-chevron" id="dem-chev-' + idx + '" onclick="toggleDem(' + idx + ')"><i class="fas fa-chevron-right" style="font-size:.68rem"></i></button></td>'
                + '<td style="color:rgba(255,255,255,.4);font-size:.62rem">#' + d.id + '</td>'
                + '<td class="nome-col" style="max-width:240px;overflow:hidden;text-overflow:ellipsis" title="' + escHtml(d.nome) + '">' + escHtml(d.nome) + '</td>'
                + '<td><span style="font-size:.68rem;color:rgba(255,255,255,.55)">' + escHtml(d.tipo) + '</span></td>'
                + '<td style="color:rgba(255,255,255,.5);font-size:.68rem">' + escHtml(d.departamento) + '</td>'
                + solicitanteCell
                + '<td class="num">' + fmtTempo(d.tempoMinutos) + '</td>'
                + '<td style="color:rgba(255,255,255,.4);font-size:.68rem">' + escHtml(d.mesCobranca) + '</td>'
                + '</tr>'
                + '<tr class="finrel-detail-row" id="dem-detail-' + idx + '" style="display:none">'
                + '<td colspan="8"><div class="finrel-detail-inner">' + buildDemDetail(d) + '</div></td>'
                + '</tr>';
        });
        qs('rl-dem-body').innerHTML = html;
    }

    function buildDemDetail(d) {
        if (d.resumo) return escHtml(d.resumo);
        return '<span style="color:rgba(255,255,255,.3);font-style:italic">Sem resumo disponível</span>';
    }

    function fmtTempoPlain(mins) {
        mins = parseInt(mins) || 0;
        var h = Math.floor(mins / 60), m = mins % 60;
        if (!h && !m) return '—';
        return (h ? h + 'h ' : '') + (m ? (m < 10 ? '0' : '') + m + 'min' : '');
    }

    window.toggleDem = function (idx) {
        var isOpen = _openDem[idx];
        var show = !isOpen;
        _openDem[idx] = show;
        var chev   = qs('dem-chev-'   + idx);
        var row    = qs('dem-row-'    + idx);
        var detail = qs('dem-detail-' + idx);
        if (chev)   chev.classList.toggle('open', show);
        if (row)    row.classList.toggle('row-open', show);
        if (detail) detail.style.display = show ? '' : 'none';
    };


    // ── Iniciar ──────────────────────────────────────────────────────────────
    init();

})();

(function() {
    var s = document.createElement('style');
    s.innerHTML = [
        '.app-layout { grid-template-rows: auto !important; height: auto !important; min-height: 100vh !important; }',
        '.main-area { overflow: visible !important; grid-template-rows: var(--topbar-height) auto !important; height: auto !important; }',
        '.content-area { overflow: visible !important; height: auto !important; min-height: 0 !important; flex: none !important; }',
        '.finrel-panel { flex-shrink: 0 !important; }',
        '.finrel-panel-scroll { height: 260px !important; min-height: 260px !important; max-height: 260px !important; flex-shrink: 0 !important; }'
    ].join(' ');
    document.head.appendChild(s);
})();
</script>
